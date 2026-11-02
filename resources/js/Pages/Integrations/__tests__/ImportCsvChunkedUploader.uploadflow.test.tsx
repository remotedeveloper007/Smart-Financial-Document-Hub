import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { vi, test, expect } from 'vitest';
import ImportCsvChunkedUploader from '../ImportCsvChunkedUploader';

// Mock crypto.subtle.digest to return deterministic buffer
const fakeDigest = async (_algo: any, data: ArrayBuffer) => {
  // return a small ArrayBuffer
  return new Uint8Array([1,2,3,4]).buffer;
};

test('full chunked upload flow with multiple chunks', async () => {
  // The application shell provides this token; include it so the unsafe upload calls use the production request path.
  document.head.innerHTML = '<meta name="csrf-token" content="test-token">';

  // stub global crypto.subtle.digest
  vi.stubGlobal('crypto', { subtle: { digest: vi.fn().mockImplementation(fakeDigest) } });

  // polyfill File.arrayBuffer in older jsdom environments
  // @ts-ignore
  if (!File.prototype.arrayBuffer) {
    // @ts-ignore
    File.prototype.arrayBuffer = function () { return new Blob([this]).arrayBuffer(); };
  }

  const assembledCsv = 'ref,amount\nT1,100\nT2,200\n';

  // spy on fetch to simulate server endpoints
  const fetchMock = vi.fn().mockImplementation(async (input: RequestInfo, init?: RequestInit) => {
    const url = String(input);
    if (url.includes('upload-session')) {
      return new Response(JSON.stringify({ session_id: 'sess-1' }), { status: 200 });
    }
    if (url.includes('upload-resume')) {
      return new Response(JSON.stringify({ received: [] }), { status: 200 });
    }
    if (url.includes('upload-chunk')) {
      return new Response(JSON.stringify({ ok: true, index: 1 }), { status: 200 });
    }
    if (url.includes('upload-finalize')) {
      // return scanning status and assembled_path
      return new Response(JSON.stringify({ ok: true, status: 'scanning', assembled_path: 'tenant-1/uploads/assembled/sess-1_transactions.csv', filename: 'test.csv' }), { status: 200 });
    }
    if (url.includes('upload-scan-status')) {
      return new Response(JSON.stringify({ status: 'clean', filename: 'test.csv', assembled_csv: assembledCsv }), { status: 200 });
    }
    return new Response(null, { status: 404 });
  });

  // @ts-ignore
  global.fetch = fetchMock;

  const onAssembled = vi.fn();

  render(<ImportCsvChunkedUploader integrationId={1} onAssembled={onAssembled} chunkSize={10} />);

  const fileContent = 'ref,amount\nT1,100\nT2,200\n';
    // create a lightweight file-like object with arrayBuffer
    const fileLike = {
      name: 'transactions.csv',
      size: fileContent.length,
      arrayBuffer: async () => new TextEncoder().encode(fileContent).buffer,
    } as any;

  // find input[type=file]
  const inputs = document.querySelectorAll('input[type=file]');
  expect(inputs.length).toBeGreaterThan(0);
  const fileEl = inputs[0] as HTMLInputElement;

  // simulate file selection
    fireEvent.change(fileEl, { target: { files: [fileLike] } });

  const btn = screen.getByText(/Upload \(chunked\)/i);
  fireEvent.click(btn);

  // wait for assembled callback
  await waitFor(() => {
    expect(onAssembled).toHaveBeenCalledWith(assembledCsv, 'test.csv');
  }, { timeout: 3000 });

  // ensure fetch was called for session, resume, chunk(s), finalize
  expect(fetchMock.mock.calls.some(c => String(c[0]).includes('upload-session'))).toBeTruthy();
  expect(fetchMock.mock.calls.some(c => String(c[0]).includes('upload-finalize'))).toBeTruthy();
});
