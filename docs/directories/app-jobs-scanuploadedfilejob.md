# `app/Jobs/ScanUploadedFileJob.php`

## Purpose

Scans uploaded files before extraction, records a private JSON sidecar, updates document status, and fails closed.

## Dependencies

- `App\Contracts\MalwareScanner` - scanner service resolved by Laravel.
- `App\Models\Document` - tenant-scoped status updates.
- Laravel queue, storage, and logging facades.
- `ProcessDocumentJob` - dispatched only after a clean scan for a known document/team pair.

## Inputs and outputs

- Constructor inputs: private-disk-relative file path, optional document ID, optional team ID.
- Queue policy: three attempts, configured increasing backoff, 120-second timeout.
- Clean: records `clean`; queues extraction only when both IDs are supplied.
- Infected: records `quarantined`, updates the document, and does not extract.
- Disabled scanner: records `unscanned`, marks the document scan-failed, and does not extract.
- Exception: records failure, logs the cause, then rethrows so Laravel can retry.

## Usage

`UploadDocumentAction` dispatches this job to the `ocr` queue. Production must run that queue and configure a reachable ClamAV daemon.
