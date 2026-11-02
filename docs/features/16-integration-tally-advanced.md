# Tally-Compatible REST Integration — Advanced

## Scope

This is a generic REST adapter configured per team. It is not a native TallyPrime XML/voucher connector, and this repository has not been verified against a live Tally/TallyPrime instance. It does not currently encode voucher XML, ledger semantics, tax/GST handling, debit/credit conversion, or reconciliation rules. See [backend Tally guide](16-integration-tally.md) and [frontend Tally guide](16-integration-tally-frontend.md) for scope, manual test data, and deployment limits.

## Service Contract

File: `app/Services/Integrations/TallyService.php`

- `testConnection()` calls `GET {url}/ping` and returns `false` on connection or configuration failure.
- `listCompanies()` calls `GET {url}/companies`.
- `listLedgers()` calls `GET {url}/ledgers` with an optional `company_id` query parameter.
- `listTransactions()` calls `GET {url}/transactions` with optional filters.
- `importTransactions()` calls `POST {url}/transactions/import` with mapped rows and `Idempotency-Key`. It throws for remote HTTP failures so the queue can retry.
- `exportTransactions()` calls `GET {url}/transactions/export`; unsuccessful HTTP responses and invalid JSON shapes throw, so the export job records failure and remains retryable.
- The service accepts `token` or `access_token` for Bearer auth and supports an `X-Security-Code` header.
- Requests use bounded connection/request timeouts. Placeholder, non-HTTP(S), and URL-with-embedded-credentials endpoints are rejected.

## Queue and Tenancy Design

Import/export jobs serialize the integration ID and team ID, not credential secrets. Before working, each job sets team context, loads the integration using both IDs, then decrypts its credentials from the database. File-based import only accepts an assembled path under that team’s `tenant-{teamId}/uploads/assembled/` directory. Export files are stored below `tenant-{teamId}/exports/tally/` and filenames are unique. Export status cache keys include team and integration IDs.

The remote service must honor the `Idempotency-Key` header to guarantee it does not create duplicates if a request succeeds remotely but the response is lost and Laravel retries the job. Local code alone cannot ensure remote exactly-once behavior.

## CSV Mapping and Accuracy

`TallyCsvImportParser` uses PHP CSV parsing, preserving quoted commas and embedded newlines. It strips a UTF-8 BOM from the first header and rejects empty files, empty/duplicate column names, header-only files, and rows with a mismatched column count. Values remain strings to preserve decimal formatting until the destination service applies its documented schema.

The UI mapper sends the field names selected by the user. Correct parsing is not equivalent to correct accounting: the team must document the target REST adapter's date, currency, amount, debit/credit, tax, ledger, and reference conventions and reconcile results in its sandbox.

## Files and Their Roles

- `app/Services/Integrations/TallyService.php`: HTTP/authentication/timeout/error contract.
- `app/Services/Integrations/TallyCsvImportParser.php`: deterministic CSV validation and row parsing.
- `app/Jobs/TallyImportJob.php`: queued import of already-mapped rows.
- `app/Jobs/TallyImportFromFileJob.php`: tenant path validation, CSV parsing, and file-based import.
- `app/Jobs/TallyExportJob.php`: tenant-scoped export, status, storage, and retry behavior.
- `app/Http/Controllers/Integrations/IntegrationController.php`: request authorization and job dispatch.
- `resources/js/Pages/Integrations/Index.tsx`: select Tally, maintain team connection settings, launch import/export.
- `resources/js/Pages/Integrations/ImportCsvMapper.tsx`: map source CSV headers to destination fields and inspect preview.
- `resources/js/Pages/Integrations/ImportCsvChunkedUploader.tsx`: resumable chunk upload and checksum workflow.

## Verification

Run against the configured MySQL test database:

```bash
php artisan test --filter='TallyServiceTest|TallyCsvImportParserTest|TallyImportFromFileJobTest|TallyExportJobTest|IntegrationJobDispatchTest|IntegrationChunkUploadTest|IntegrationChunkResumeTest'
```

Latest result on the configured MySQL test database: **21 tests passed, 65 assertions**. The tests use fake HTTP responses. They verify exact outgoing payloads, auth/idempotency headers, quoted CSV parsing, malformed CSV rejection, team-bound storage, and error retry behavior. They do not establish live provider compatibility or accounting accuracy.

## Troubleshooting and Maintenance

- Connection check fails: verify sandbox URL has `/ping`, auth field is correct, the service is reachable, and the endpoint is not the placeholder host.
- Rows are rejected: compare the mapped JSON keys with the actual destination API contract; do not infer field semantics from labels alone.
- Duplicate rows after retry: verify that the remote service accepts and enforces `Idempotency-Key`; otherwise implement lookup/deduplication using a stable remote reference.
- Export is missing: inspect failed jobs and the team-scoped export status key/file prefix. Do not inspect or copy another tenant's path.
- Job fails: review sanitized error type and target API response; never log credentials, full payloads, or complete financial files.
- Before changing field mappings, update the parser/service tests, sandbox fixtures, integration documentation, and customer instructions together.

## Remaining Release Requirements

- Verify with a real provider sandbox and representative chart-of-accounts/company data.
- Implement native TallyPrime protocol separately if native TallyPrime is the required target.
- Document supported voucher types and tax/debit/credit semantics and prove them with round-trip reconciliation.
- Add a browser E2E test for choosing Tally, saved settings, connection verification, chunk resume, mapping preview, import, and export download.
- Confirm remote idempotency semantics and provider rate limits before production traffic.
