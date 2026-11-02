# 06 - Secure Document Upload and Scanning

## 1. Purpose

Accept a tenant-owned PDF, store it privately, scan it for malware, and only queue document extraction after the scanner reports a clean result.

## 2. Business reason

Financial statements and receipts contain sensitive information. They must not be exposed through public storage or processed before a malware scan. A scan failure must stop processing rather than be treated as a clean result.

## 3. Architecture

1. `DocumentController` authorizes the user and receives a validated request.
2. `UploadDocumentAction` writes the file to the private `local` disk under `tenant-{team-id}/documents/`, creates a `Document`, and dispatches `ScanUploadedFileJob` to the `ocr` queue.
3. `ScanUploadedFileJob` resolves `MalwareScanner` from the service container. `AppServiceProvider` binds it to `ClamAvScanner`.
4. `ClamAvScanner` streams file bytes to the configured ClamAV daemon. It returns `clean` or `infected`; connection/protocol failures throw and are retried by Laravel's queue worker.
5. A clean result records scan metadata and dispatches `ProcessDocumentJob` with the document and team IDs. Infected files are quarantined. Missing or unavailable scanning is recorded as a failure and does not dispatch extraction.
6. `ProcessDocumentJob` reloads the document with both IDs to enforce tenant ownership. Its simulated extraction driver is only allowed in local/testing environments.

```text
Browser -> POST /documents -> StoreDocumentRequest -> DocumentController
  -> UploadDocumentAction -> private tenant storage + Document(status=scanning)
  -> ScanUploadedFileJob(queue=ocr) -> ClamAvScanner
     -> clean: ProcessDocumentJob(queue worker)
     -> infected: quarantined; no extraction
     -> unavailable/error: scan_failed; retry/fail; no extraction
```

## 4. Database and storage changes

- Uses the existing `documents` table; no migration is required for this change.
- `documents.file_path` contains a private-disk-relative path, not a public URL.
- The `status` column records scanning, queued, processing, processed, scan-failed, quarantined, or failed states. Confirm the existing migration supports the status values before changing the database schema.
- A JSON sidecar at `{file_path}.scan.json` records path, scanner, result, and timestamp. It is stored on the same private disk.
- Configure `CLAMAV_ENABLED=true`, `CLAMD_HOST`, `CLAMD_PORT`, and `CLAMD_TIMEOUT` in the deployment environment. The daemon must be reachable from the queue worker. The default disabled setting intentionally blocks processing.
- Keep uploaded and quarantined files private. Do not expose the `local` disk via a public symlink.

## 5. Relationships and tenant isolation

- `Document` belongs to a `Team` through `team_id`.
- The `BelongsToTeam` trait applies the active team scope and assigns the current team on create.
- The processing job includes `teamId` and `documentId`; it queries by both values, rather than trusting a document ID alone.
- The upload route is authenticated and `DocumentPolicy::create` guards both the page and upload action.

## 6. Important files

- `app/Http/Controllers/DocumentController.php` - authorized page, list, and upload endpoints.
- `app/Http/Requests/StoreDocumentRequest.php` - request validation.
- `app/Actions/Documents/UploadDocumentAction.php` - tenant-private storage, document creation, scan dispatch, and audit event.
- `app/Models/Document.php` - tenant relationship and state names.
- `app/Contracts/MalwareScanner.php` - scanner boundary.
- `app/Services/Security/ClamAvScanner.php` - ClamAV TCP INSTREAM implementation.
- `app/Jobs/ScanUploadedFileJob.php` - fail-closed scan, result recording, status updates, retries, and extraction dispatch.
- `app/Jobs/ProcessDocumentJob.php` - tenant-bound processing and production guard for simulated extraction.
- `config/services.php` - scanner and extraction driver settings.
- `routes/web.php` - upload, listing, and chunk-scan-status routes.
- `resources/js/Pages/Documents/Upload.tsx` - browser upload form and feedback.
- `resources/js/Layouts/AuthenticatedLayout.tsx` - authenticated desktop sidebar and responsive navigation.

## 7. Request and queue flow, step by step

1. A signed-in user visits `documents.upload`. The page route is protected by authentication and the controller checks the document-create policy.
2. The React form gathers a display name and PDF, prevents duplicate submission while sending, and posts `FormData` to `documents.store`.
3. `csrfFetch` adds the current CSRF token. The request asks for JSON so validation errors can be displayed in the form.
4. `StoreDocumentRequest` validates the name and file before the action runs.
5. The action creates a UUID-based filename under the current tenant's directory on the private local disk.
6. The action creates the `Document` in `scanning` state and dispatches its scan job with team/document identifiers.
7. The scan job writes a sidecar result. It dispatches extraction only after a `clean` scan. `infected`, disabled, missing, or failed scanner cases never dispatch extraction.
8. The UI displays the returned document ID and says private scanning was queued. This confirms acceptance, not completion of scanning or extraction.

## 8. Backend and frontend file notes

- `DocumentController::page()` authorizes before rendering the Inertia page.
- `DocumentController::index()` returns only the globally scoped documents visible to the active team.
- `DocumentController::store()` relies on the form request for validation, invokes the action, and returns JSON with HTTP 201.
- `UploadDocumentAction::execute()` stores privately, creates the row, queues scanning, and attempts an audit record. Audit write failures are logged; they do not make a successfully stored document look like a failed upload.
- `ScanUploadedFileJob::handle()` checks private storage first. When scanning is disabled it writes `unscanned` metadata and marks the document failed. Exceptions are logged and rethrown so Laravel's retry policy applies.
- `ClamAvScanner::scan()` verifies the path, opens a TCP connection, sends length-prefixed file chunks, and accepts only explicit ClamAV `OK` or `FOUND` responses.
- `Upload.tsx` builds multipart form data, handles field-level 422 validation, displays request errors, and clears the form only after a successful response.
- `AuthenticatedLayout.tsx` provides the desktop left sidebar and retains its compact responsive menu for smaller screens.

## 9. Testing procedure

Run the focused backend tests:

```bash
php artisan test --filter="IntegrationChunkUploadTest|DocumentScanPipelineTest"
```

Run the complete backend suite:

```bash
php artisan test
```

Run the frontend unit suite and production build:

```bash
npm run test:unit -- --run
npm run build
```

Manual staging check:

1. Configure a reachable ClamAV daemon and verify the queue worker uses the same network and environment variables.
2. Log in with an account that has document-create permission and open `/documents/upload`.
3. Upload a test PDF. Confirm the response is accepted and the file is absent from public URLs.
4. Inspect the document row and private storage. Confirm the row belongs to the active team and starts in `scanning`.
5. Confirm the scanner writes a `.scan.json` result. A clean file should proceed to processing; an EICAR test file in an isolated staging environment should be quarantined and never extracted.
6. Stop the scanner in staging and retry with a harmless test file. Confirm it remains blocked, is marked failed, and does not produce financial records.

## 10. Recorded verification

- `php artisan test --filter="IntegrationChunkUploadTest|DocumentScanPipelineTest"` - 7 tests passed, 27 assertions at the time of the focused scanner check.
- `php artisan test` - 137 passed, 1 skipped, 391 assertions on the configured MySQL test database. The skipped live ClamAV test requires an available scanner daemon.
- `npm.cmd run test:unit -- --run` - 3 files and 4 tests passed. Vitest printed worker-termination timeout warnings after completion; investigate if the suite becomes flaky.
- `npm.cmd run build` - TypeScript check and Vite production build passed; test-only files are excluded from Inertia page discovery and production assets.
- No live ClamAV, production queue, staging browser, or cloud-storage validation was performed in this workspace.

## 11. Troubleshooting

- **Upload returns 419:** reload the page and inspect the CSRF meta tag and `csrfFetch` helper.
- **Upload returns 422:** display the specific `name` or `file` error; confirm the request contains a PDF within the request validation size limit.
- **Document stays in scanning:** inspect the `ocr` queue worker and `storage/logs/laravel.log`; ensure it is running the deployed code and can access the private file.
- **Status becomes scan_failed:** inspect scanner exception logs, then check `CLAMAV_ENABLED`, daemon address/port, firewall rules, timeout, and queue retry/failure records. Do not mark the file clean manually without scanning.
- **Status becomes quarantined:** do not make the file downloadable or pass it to extraction; follow the organization's incident process.
- **No extracted records in production:** simulated extraction is intentionally blocked outside local/testing. A real extraction provider is a release gate.

## 12. Maintenance and safety

- Keep the scanner's accepted results explicit; unknown responses must throw.
- Preserve `teamId` and `documentId` through queue jobs and tenant-aware storage paths.
- Do not switch sensitive documents to a public disk or bypass scanning in production.
- Configure and monitor queue retries and the dead-letter/failed-jobs table.
- Replace the simulated extraction driver with a validated provider before relying on extracted financial data.
- For schema changes, create migrations using `php artisan make:migration ...`; for tests, use `php artisan make:test ...`.
