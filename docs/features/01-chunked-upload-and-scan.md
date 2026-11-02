# Chunked CSV Upload and Malware Scanning

## Purpose and current scope

The integration page accepts a CSV in resumable chunks, verifies chunk and full-file checksums, stores the file in tenant-specific private storage, and waits for a ClamAV scan before exposing its contents for mapping. This is a Tally-compatible CSV import path, not a generic accounting-provider file importer.

The backend uses ClamAV's TCP `INSTREAM` protocol through `ClamAvScanner`. It does not call `clamscan`/`clamdscan` and has no heuristic "clean" fallback. If scanning is disabled or fails, file import must remain blocked.

## User flow

1. A user with integration-update permission selects a CSV from `/integrations`.
2. The browser computes SHA-256 for the file and requests an upload session.
3. The browser uploads chunks (current default: 5 MiB, concurrency: three) with per-chunk SHA-256 checksums. `upload-resume` reports chunks already received for that session.
4. Finalization assembles the chunks and verifies the whole-file checksum.
5. In testing, the scan job is executed inline to make the HTTP test deterministic. In other environments it is queued. The browser polls `upload-scan-status`.
6. Only a clean scan returns CSV contents to the mapping UI. Failed, disabled, or infected scans do not return file contents for mapping.
7. A file-path import request is accepted only when the session metadata belongs to the same team and integration, the clean ClamAV sidecar matches the assembled file path, and the original size/checksum still match. `TallyImportFromFileJob` repeats the scan-result check immediately before parsing.
8. The mapping UI may submit mapped transaction rows directly. That JSON route is separately authorized and validates its payload; it is not the file-path import route.

## Files and responsibilities

- `app/Http/Controllers/Integrations/IntegrationController.php` - authorized session, chunk, resume, finalize, scan-status, and import endpoints.
- `app/Contracts/MalwareScanner.php` - scanner interface used by jobs.
- `app/Services/Security/ClamAvScanner.php` - ClamAV TCP scanner. Configure `CLAMAV_ENABLED`, `CLAMD_HOST`, `CLAMD_PORT`, and `CLAMD_TIMEOUT`.
- `app/Jobs/ScanUploadedFileJob.php` - scans a private file, stores scan metadata, and fails closed.
- `app/Jobs/TallyImportFromFileJob.php` - validates tenant path and clean scan metadata before parsing/import.
- `app/Services/Integrations/TallyCsvImportParser.php` and `TallyService.php` - parse CSV values and send the integration request.
- `resources/js/Pages/Integrations/ImportCsvChunkedUploader.tsx` - chunking, resumable state, retries, progress, and scan polling.
- `resources/js/Pages/Integrations/ImportCsvMapper.tsx` - mapping preview and mapped-row submission.
- `resources/js/Pages/Integrations/Index.tsx` - integration settings and import/export UI.
- `tests/Feature/IntegrationChunkUploadTest.php`, `IntegrationChunkResumeTest.php`, `TallyImportFromFileJobTest.php` - endpoint and job coverage.

## Storage and request contract

Files remain on the configured private `local` disk under paths such as:

```text
tenant-{team-id}/uploads/sessions/{session-uuid}.json
tenant-{team-id}/uploads/tmp/{session-uuid}/chunk_{number}
tenant-{team-id}/uploads/assembled/{session-uuid}_{filename}.csv
tenant-{team-id}/uploads/assembled/{session-uuid}_{filename}.csv.scan.json
```

The session metadata binds the filename, original size/checksum, team, and integration. Never serve these paths as public URLs. The session endpoint accepts MD5 and SHA-256 checksums for current client compatibility; SHA-256 is the browser default. Configure web server request limits consistently with the maximum supported file size.

## Configuration and running

Set these variables in the web and queue-worker environments:

```dotenv
CLAMAV_ENABLED=true
CLAMD_HOST=clamav
CLAMD_PORT=3310
CLAMD_TIMEOUT=10
QUEUE_CONNECTION=redis
```

The host name above is an example for a private Docker network; do not copy it unless the daemon is reachable under that name. Start the `ocr` queue worker/Horizon supervisor and verify the daemon is reachable from that worker. The disabled default blocks file processing and is not an acceptable production configuration.

## Verification commands and recorded results

Run the backend tests with the configured disposable MySQL test database:

```bash
php artisan test --filter="IntegrationChunkUploadTest|IntegrationChunkResumeTest|TallyImportFromFileJobTest"
```

The full test suite is:

```bash
php artisan test
```

Frontend checks:

```bash
npm run test:unit -- --run
npm run build
```

Latest local verification: `php artisan test` passed 137 tests, skipped 1 live ClamAV test, and made 391 assertions on the configured MySQL test database. The focused AI/upload/import regression selection passed 15 tests and 70 assertions. `npm.cmd run test:unit -- --run` passed 4 tests; `npm.cmd run build` passed and emitted no test modules. The ClamAV daemon and external Tally sandbox were not available, and local PHP 8.3.14 is below the project's newly declared PHP 8.4 minimum. The new GitHub Actions matrix has not yet been run on GitHub.

## Manual QA checklist

1. Use a disposable team, integration, and synthetic CSV.
2. Upload a CSV and verify each chunk reaches the expected session and checksum.
3. Interrupt and resume the same browser session; confirm uploaded chunks are not resent unnecessarily.
4. Confirm assembly rejects a missing/corrupt chunk or mismatched checksum.
5. With ClamAV available, confirm a clean CSV becomes available to the mapping step.
6. In an isolated test environment, scan an approved malware test sample and confirm infected/failed results do not expose data or dispatch import.
7. Submit the file-path import before scan completion and verify it returns a conflict and does not enqueue a Tally import job.
8. Complete a clean file-based import against a mocked or sandbox Tally endpoint; verify the idempotency key and mapped values.
9. Confirm another team or integration cannot access the session, file, scan status, or export.
10. Inspect production build output and ensure test-only modules are not included as Inertia page chunks.

## Troubleshooting

- **Checksum mismatch:** verify client uses the exact original bytes and the same checksum algorithm declared by session metadata.
- **Scan remains pending:** verify `CLAMAV_ENABLED`, queue workers, network route, daemon readiness, timeout, and queue failure records.
- **Scan failed/quarantined:** do not override the status or import the file; inspect sanitized logs and follow the incident process.
- **File import returns 409:** scan may be incomplete, not clean, have a sidecar mismatch, or fail the stored size/checksum integrity check.
- **Import job rejects a path:** ensure it belongs to the expected tenant's assembled directory and has a clean sidecar from ClamAV.
- **Resume has no chunks:** verify the same team, integration, session ID, and private storage disk are used across requests.

## Operational limits and release gates

- Live ClamAV connectivity and capacity have not been verified in this workspace.
- The full PostgreSQL CI job and external Tally sandbox flow must pass before production use.
- Configure retention/cleanup for abandoned chunks, assembled CSVs, and scan sidecars; do not delete files while a queued job references them.
- Add concurrency/race and size-limit tests before increasing file-size or worker-concurrency limits.
- Treat provider credentials as secrets; do not log them or expose the encrypted integration credentials in JSON.
