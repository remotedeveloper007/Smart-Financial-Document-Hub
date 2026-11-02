Feature: Frontend Chunked CSV Uploader

Files:
- resources/js/Pages/Integrations/ImportCsvChunkedUploader.tsx

Purpose:
- Perform client-side chunking of large CSV files with resumability and per-chunk controls (retry/cancel).
- Provide accurate progress by bytes and visual per-chunk status to aid users during large uploads.

High-level flow:
1. User selects or drags a CSV file into the uploader component.
2. Client computes SHA-256 checksum of the full file and requests an upload session from server.
3. Client splits file into 5MB chunks and uploads them in parallel (concurrency=3).
4. Component tracks per-chunk status and bytes uploaded; user can cancel an individual chunk or entire upload.
5. After all chunks uploaded, client requests server to finalize/assemble the file.
6. If server scan returns clean, assembled CSV is returned and handed to `ImportCsvMapper` for mapping.

Key implementation notes (line-by-line highlights):
- `digestHex` (top of file): uses Web Crypto `subtle.digest` to compute SHA-256 over ArrayBuffer; returns hex string.
- `toBase64`: converts Uint8Array to base64 string for JSON upload. This avoids multipart complexity on the server.
- Component state:
  - `chunks`: array of chunk meta objects `{index, status, attempts}`.
  - `uploadedBytes`: total bytes successfully uploaded; used to compute percent progress.
  - `controllersRef`: map of per-chunk `AbortController` instances allowing cancel of individual chunk requests.
- `createSession` and `resumeSession`: call server endpoints to create upload session and query already-received chunks.
- `uploadChunk(sessionId, index, b64, checksum, signal?)`: POSTs chunk base64 with optional `AbortSignal` to allow abort.
- Worker pool: splits chunk indices into `concurrency` queues and runs `worker` which uploads chunks in FIFO order. Each worker updates `chunks` state and `uploadedBytes`.
- `retryChunk(index)`: retries a failed chunk using the stored session id.
- `cancelChunk(index)`: aborts a specific chunk's `AbortController` and marks it canceled.

Testing (manual):
- Open browser devtools Console and drag a small CSV file into uploader. Expect:
  - UI shows chunk rows equal to calculated number of chunks.
  - Progress increases as chunks upload; uploaded bytes display updates.
  - You can press Cancel for an uploading chunk and see its status change to Canceled.
- Edge cases to test:
  - Network interruption: abort and then retry — `resumeSession` should detect uploaded chunks and skip them.
  - Upload a file with size not divisible by chunk size — final chunk smaller; progress computation should be accurate.

Troubleshooting (Browser):
- If progress stays at 0% but chunks show uploaded, ensure `file.size` is available (some browsers may restrict file APIs in cross-origin contexts).
- If requests fail with CORS errors, ensure server accepts `Content-Type: application/json` and CORS policy allows endpoint.

Sample input for manual test:
- Create a small CSV text:
  ```csv
  ref,amount
  T1,100
  T2,200
  ```
- Drag file into uploader and click `Upload (chunked)`.
- Expected: progress reaches 100% and mapping UI opens with CSV content.

Why this helps:
- Improves reliability for large uploads over flaky networks.
- Gives users fine-grained control and observability of upload progress for compliance and trust.

Maintenance notes:
- For production, consider switching from base64 JSON uploads to multipart uploads to save bandwidth and CPU.
- Consider using `navigator.sendBeacon` for small metadata endpoints or resumable protocols like tus.io for robust resumability.

---
