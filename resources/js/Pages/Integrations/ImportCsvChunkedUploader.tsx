import React, { useState, useRef } from 'react';
import { csrfFetch } from '@/lib/csrfFetch';

async function digestHex(buffer: ArrayBuffer, algo = 'SHA-256') {
  const hash = await crypto.subtle.digest(algo, buffer);
  const hex = Array.from(new Uint8Array(hash)).map(b => b.toString(16).padStart(2, '0')).join('');
  return hex;
}

function toBase64(bytes: Uint8Array) {
  let binary = '';
  const chunk = 0x8000;
  for (let i = 0; i < bytes.length; i += chunk) {
    binary += String.fromCharCode.apply(null, Array.from(bytes.subarray(i, i + chunk)) as unknown as number[]);
  }
  return btoa(binary);
}

export default function ImportCsvChunkedUploader({ integrationId, onComplete, onAssembled, chunkSize: propChunkSize }: { integrationId: number; onComplete?: () => void; onAssembled?: (csv: string, filename?: string) => void; chunkSize?: number }) {
  const [file, setFile] = useState<File | null>(null);
  const [progress, setProgress] = useState(0);
  const [running, setRunning] = useState(false);
  const [status, setStatus] = useState<'idle'|'running'|'completed'|'failed'|'canceled'>('idle');
  const [chunks, setChunks] = useState<Array<{ index: number; status: 'pending'|'uploaded'|'uploading'|'failed'|'skipped'|'canceled'; attempts: number }>>([]);
  const [uploadedBytes, setUploadedBytes] = useState(0);
  const controllersRef = useRef<Record<number, AbortController>>({});
  const chunkSize = propChunkSize ?? 5 * 1024 * 1024; // 5MB
  const concurrency = 3;
  const maxRetries = 3;
  const controllerRef = useRef<AbortController | null>(null);
  const sessionIdRef = useRef<string | null>(null);

  async function createSession(filename: string, checksum: string, size: number) {
    // Previous direct upload requests omitted Laravel's CSRF header and were rejected with HTTP 419.
    const res = await csrfFetch(`/integrations/${integrationId}/upload-session`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ filename, checksum, size }),
    });
    return res.json();
  }

  async function resumeSession(sessionId: string) {
    const res = await fetch(`/integrations/${integrationId}/upload-resume?session_id=${encodeURIComponent(sessionId)}`);
    return res.json();
  }

  async function uploadChunk(sessionId: string, index: number, b64: string, checksum: string, signal?: AbortSignal) {
    const form = new FormData();
    form.append('session_id', sessionId);
    form.append('index', String(index));
    form.append('chunk_checksum', checksum);
    // convert base64 to blob if provided
    if (b64 && b64.startsWith('data:')) {
      // data URL
      const res = await fetch(b64);
      const blob = await res.blob();
      form.append('chunk', blob, `chunk_${index}`);
    } else {
      // assume b64 is plain base64 string
      try {
        const binary = atob(b64);
        const arr = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) arr[i] = binary.charCodeAt(i);
        const blob = new Blob([arr]);
        form.append('chunk', blob, `chunk_${index}`);
      } catch (e) {
        // fallback: send as raw field
        form.append('chunk', b64 as any);
      }
    }

    const res = await csrfFetch(`/integrations/${integrationId}/upload-chunk`, {
      method: 'PUT',
      body: form,
      signal,
    });
    if (!res.ok) throw new Error('upload_failed');
    return res.json();
  }

  async function finalize(sessionId: string) {
    const res = await csrfFetch(`/integrations/${integrationId}/upload-finalize`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ session_id: sessionId }),
    });
    return res;
  }

  async function handleStart() {
    if (!file || running) return;
    const activeFile = file;
    setRunning(true);
    setStatus('running');
    controllerRef.current = new AbortController();
    try {
      const arrayBuffer = await activeFile.arrayBuffer();
      const fileChecksum = await digestHex(arrayBuffer, 'SHA-256');
      const session = await createSession(activeFile.name, fileChecksum, activeFile.size);
      const sessionId = session.session_id;
      sessionIdRef.current = sessionId;

      const resumed = await resumeSession(sessionId);
      const received = new Set((resumed.received || []).map((r: any) => r.index));

      const total = Math.ceil(activeFile.size / chunkSize);
      const indices = Array.from({ length: total }, (_, i) => i + 1);

      // initialize chunk state
      setChunks(indices.map(i => ({ index: i, status: received.has(i) ? 'uploaded' : 'pending', attempts: 0 })));

      // Worker pool
      let completed = 0;
      let bytesUploaded = 0;

      async function worker(queue: number[]) {
        while (queue.length > 0) {
          const idx = queue.shift();
          if (!idx) break;
          if (received.has(idx)) {
            completed++;
            setProgress(Math.round((completed / total) * 100));
            continue;
          }

          const start = (idx - 1) * chunkSize;
          const end = Math.min(start + chunkSize, activeFile.size);
          const slice = new Uint8Array(arrayBuffer.slice(start, end));
          const chunkChecksum = await digestHex(slice.buffer, 'SHA-256');
          const b64 = toBase64(slice);

          // update chunk status to uploading
          setChunks(prev => prev.map(c => c.index === idx ? { ...c, status: 'uploading', attempts: c.attempts + 1 } : c));
          // create AbortController for this chunk
          const aborter = new AbortController();
          controllersRef.current[idx] = aborter;

          let attempt = 0;
          while (attempt < maxRetries) {
            try {
              await uploadChunk(sessionId, idx, b64, chunkChecksum, aborter.signal);
              // mark uploaded
              setChunks(prev => prev.map(c => c.index === idx ? { ...c, status: 'uploaded' } : c));
              completed++;
              bytesUploaded += slice.byteLength;
              setUploadedBytes(bytesUploaded);
              setProgress(Math.round((bytesUploaded / activeFile.size) * 100));
              break;
            } catch (e) {
              attempt++;
              // mark failed attempt
              setChunks(prev => prev.map(c => c.index === idx ? { ...c, status: 'failed' } : c));
              const backoff = 200 * attempt;
              await new Promise(r => setTimeout(r, backoff));
              if (attempt >= maxRetries) throw e;
            }
          }
          // cleanup controller
          delete controllersRef.current[idx];
        }
      }

      // split indices into worker queues
      const queues: number[][] = Array.from({ length: concurrency }, () => []);
      indices.forEach((v, i) => queues[i % concurrency].push(v));

      await Promise.all(queues.map(q => worker(q)));

      const fin = await finalize(sessionId);
      const finJson = await fin.json();
      if (!finJson.ok) {
        throw new Error('finalize_failed');
      }

      // if scanning, poll for scan status until ready
      if (finJson.status === 'scanning' && finJson.assembled_path) {
        setStatus('running');
        // poll
        let attempts = 0;
        while (attempts < 60) { // max ~60 attempts
          await new Promise(r => setTimeout(r, 500));
          const poll = await fetch(`/integrations/${integrationId}/upload-scan-status?session_id=${encodeURIComponent(sessionId)}`);
          if (!poll.ok) throw new Error('scan_poll_failed');
          const pollJson = await poll.json();
          if (pollJson.status === 'scanning') {
            attempts++;
            continue;
          }
          if (pollJson.status === 'quarantined') {
            setStatus('failed');
            throw new Error('file_quarantined');
          }
          if (pollJson.status === 'clean') {
            setProgress(100);
            setStatus('completed');
            onAssembled && onAssembled(pollJson.assembled_csv, pollJson.filename);
            onComplete && onComplete();
            break;
          }
        }
      } else if (finJson.assembled_csv) {
        setProgress(100);
        setStatus('completed');
        onAssembled && onAssembled(finJson.assembled_csv, finJson.filename);
        onComplete && onComplete();
      }
    } finally {
      setRunning(false);
    }
  }

  async function retryChunk(index: number) {
    if (!file) return;
    const arrayBuffer = await file.arrayBuffer();
    const start = (index - 1) * chunkSize;
    const end = Math.min(start + chunkSize, file.size);
    const slice = new Uint8Array(arrayBuffer.slice(start, end));
    const chunkChecksum = await digestHex(slice.buffer, 'SHA-256');
    const b64 = toBase64(slice);
    setChunks(prev => prev.map(c => c.index === index ? { ...c, status: 'uploading', attempts: c.attempts + 1 } : c));
    try {
      const sessionId = sessionIdRef.current;
      if (!sessionId) throw new Error('no_session');
      await uploadChunk(sessionId, index, b64, chunkChecksum);
      setChunks(prev => prev.map(c => c.index === index ? { ...c, status: 'uploaded' } : c));
    } catch (e) {
      setChunks(prev => prev.map(c => c.index === index ? { ...c, status: 'failed' } : c));
    }
  }

  function handleCancel() {
    // abort all active chunk controllers
    Object.values(controllersRef.current).forEach(ctrl => ctrl.abort());
    setStatus('canceled');
    setRunning(false);
  }

  function cancelChunk(index: number) {
    const ctrl = controllersRef.current[index];
    if (ctrl) {
      ctrl.abort();
      setChunks(prev => prev.map(c => c.index === index ? { ...c, status: 'canceled' } : c));
    }
  }

  function handleRetry() {
    setStatus('idle');
    setProgress(0);
    handleStart();
  }

  return (
    <div className="inline-block">
      <div
        onDrop={(e) => { e.preventDefault(); if (e.dataTransfer.files && e.dataTransfer.files[0]) setFile(e.dataTransfer.files[0]); }}
        onDragOver={(e) => e.preventDefault()}
        className="inline-block rounded border-dashed border-2 p-2"
      >
        <input type="file" accept=".csv" onChange={e => setFile(e.target.files ? e.target.files[0] : null)} />
        <div className="text-xs text-gray-500">Or drag & drop CSV here</div>
      </div>
      <div className="mt-2 inline-block">
        <button className="rounded-md bg-green-600 px-3 py-1 text-white" onClick={handleStart} disabled={!file || running}>Upload (chunked)</button>
        {status === 'running' && (
          <button onClick={handleCancel} className="ml-2 rounded-md bg-yellow-500 px-3 py-1 text-white">Cancel</button>
        )}
        {(status === 'failed' || status === 'canceled') && (
          <button onClick={handleRetry} className="ml-2 rounded-md bg-blue-500 px-3 py-1 text-white">Retry</button>
        )}
      </div>
      <div className="mt-2 inline-block ml-3">Progress: {progress}%</div>

      {chunks.length > 0 && (
        <div className="mt-4">
          <div className="text-sm font-medium">Chunks</div>
          <table className="w-full text-sm mt-2 table-auto">
            <thead>
              <tr>
                <th className="text-left">#</th>
                <th className="text-left">Status</th>
                <th className="text-left">Attempts</th>
                <th className="text-left">Action</th>
              </tr>
            </thead>
            <tbody>
              {chunks.map(c => (
                <tr key={c.index} className="odd:bg-gray-50">
                  <td>{c.index}</td>
                  <td>{c.status}</td>
                  <td>{c.attempts}</td>
                  <td>
                    {c.status === 'failed' && (
                      <button onClick={() => retryChunk(c.index)} className="rounded bg-blue-500 px-2 py-1 text-white">Retry</button>
                    )}
                    {c.status === 'uploading' && (
                      <>
                        <span className="text-xs text-gray-500">Uploading...</span>
                        <button onClick={() => cancelChunk(c.index)} className="ml-2 rounded bg-red-500 px-2 py-1 text-white">Cancel</button>
                      </>
                    )}
                    {c.status === 'uploaded' && (
                      <span className="text-xs text-green-600">Uploaded</span>
                    )}
                    {c.status === 'canceled' && (
                      <span className="text-xs text-yellow-600">Canceled</span>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          <div className="mt-2 text-xs text-gray-600">Uploaded bytes: {uploadedBytes} / {file?.size ?? 0}</div>
        </div>
      )}
    </div>
  );
}
