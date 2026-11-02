# Tally-Compatible REST Integration (Backend)

## Status and Scope

This implementation is a **generic, team-configured REST adapter**. It is not a verified native TallyPrime connector and does not implement Tally's native XML-over-HTTP protocol, voucher XML schemas, GST/tax rules, ledger reconciliation, or accounting-semantic validation. Do not interpret a successful HTTP response as proof that imported values are accepted or financially correct in a real Tally company.

Provider endpoints expected by this adapter:

- `GET {url}/ping`
- `GET {url}/companies`
- `GET {url}/ledgers`
- `GET {url}/transactions`
- `POST {url}/transactions/import`
- `GET {url}/transactions/export`

These endpoints must be provided by the customer's configured compatible REST service or a tested integration gateway.

## Credentials and Tenant Security

- Integration records belong to a `team_id` and credentials are Laravel-encrypted at rest through the `Integration` model cast.
- Credentials are excluded from JSON serialization. Queue jobs carry `integrationId` and `teamId`; workers reload the integration and decrypt credentials at execution time instead of placing secrets in serialized queue payloads.
- The migration `2026_10_17_000001_encrypt_integration_credentials.php` converts old JSON credentials. Keep the same `APP_KEY` and take a verified database backup before deployment.
- The service accepts `token` or the UI's `access_token`; requests use `Authorization: Bearer ...`. It also supports `X-Security-Code`.
- URLs must use HTTP or HTTPS, include a host, must not include embedded user/password credentials, and the placeholder `api.example.com` is rejected. Requests have bounded connection and total timeouts.
- Never expose provider tokens or security codes in logs, frontend props, API responses, or support tickets.

## Import Accuracy and Retry Behavior

### CSV

`TallyCsvImportParser` uses PHP's CSV parser rather than splitting lines. It preserves quoted commas, escaped quotes, and embedded newlines; strips a UTF-8 BOM from the first header; and rejects empty files, empty/duplicate headers, files with no data rows, and rows with the wrong number of columns. It preserves cell values as strings so decimal formatting and dates are not silently transformed before the customer REST adapter receives them.

The React field mapper sends the field names selected by the user. Example file:

```csv
date,description,amount,reference
2026-10-01,"Office, supplies",25.75,INV-7
2026-10-02,"Two lines
continued",10.00,INV-8
```

Expected parsed payload:

```json
{
  "transactions": [
    {"date":"2026-10-01","description":"Office, supplies","amount":"25.75","reference":"INV-7"},
    {"date":"2026-10-02","description":"Two lines\ncontinued","amount":"10.00","reference":"INV-8"}
  ]
}
```

This checks faithful parsing and field mapping only. It does not establish that the external accounting API interprets or books those values correctly.

### Idempotency

Imports receive a stable `Idempotency-Key` derived from the integration and mapped payload (or file checksum for file uploads). The compatible remote API must honor this header to prevent duplicate vouchers when Laravel retries a job. Without remote idempotency support, the local application cannot guarantee the remote system will not create duplicate entries after a timeout where the remote accepted the request but the response was lost.

Queue jobs throw on remote request failure so Laravel retry/failed-job behavior works. They log integration/team IDs, row counts, and exception classes, not credential values or full CSV contents.

## Export Behavior

Exports run in a queue job, reload credentials from the team-owned integration, and write JSON under `tenant-{teamId}/exports/tally/`. Export status cache keys include both team ID and integration ID. Unique filenames avoid same-second overwrites. The UI's export list and download endpoints are authorized against the same team integration.

## Files

- `app/Services/Integrations/TallyService.php`: bounded REST calls, authentication headers, import idempotency header, and response checks.
- `app/Services/Integrations/TallyCsvImportParser.php`: strict, quote-aware CSV parsing.
- `app/Jobs/TallyImportJob.php`: queued import for mapped JSON rows.
- `app/Jobs/TallyImportFromFileJob.php`: tenant-checked CSV load, parse, and queued remote import.
- `app/Jobs/TallyExportJob.php`: queued remote export and tenant-specific JSON storage.
- `app/Http/Controllers/Integrations/IntegrationController.php`: authorization, request validation, and job dispatch.
- `resources/js/Pages/Integrations/Index.tsx`: team integration form; Tally is available in its provider selector.
- `resources/js/Pages/Integrations/ImportCsvMapper.tsx`: explicit column-to-target mapping and preview.
- `resources/js/Pages/Integrations/ImportCsvChunkedUploader.tsx`: resumable chunked CSV upload with checksums.

## Test Commands and Results

```bash
php artisan test --filter='TallyServiceTest|TallyCsvImportParserTest|TallyImportFromFileJobTest|TallyExportJobTest|IntegrationJobDispatchTest'
```

Latest focused result on the configured MySQL test database: **21 tests passed, 65 assertions**.

The tests verify request authentication headers, placeholder URL rejection without outbound requests, exact CSV parsing for quoted commas/newlines, invalid row shape rejection, exact import payload forwarding, idempotency header delivery, cross-tenant file rejection, export contents/team prefix/listing, and retryable remote failure behavior. They use `Http::fake()` and therefore do not verify behavior against a live Tally installation.

## Manual Sandbox Verification

1. Configure a dedicated sandbox-compatible REST endpoint, never a production accounting endpoint for QA.
2. Sign in as a user with `manage_integrations` and open `/integrations`.
3. Select `Tally`; enter the sandbox URL, a sandbox token, and/or security code; save the integration.
4. Click **Verify connection**. Expected request: `POST /integrations/test-connection`; the fake/sandbox `/ping` endpoint should return 2xx and the UI should display `Connection verified`.
5. Select the Tally integration's CSV uploader and provide the example CSV above. Map `date`, `description`, `amount`, and `reference` to the exact contract expected by the target REST adapter. Preview and compare every field to the original file.
6. Finalize the upload. The scan must report clean before the CSV content appears in the mapping UI.
7. Start import. Expected request: `POST /integrations/{id}/import`, response `202 Accepted`; worker logs should record the team/integration IDs and row count without credentials.
8. Confirm the remote sandbox's resulting rows/vouchers, including decimal scale, date interpretation, Unicode, quoting, and any external reference. A job's success alone is not sufficient evidence of accounting accuracy.
9. Start export and verify `GET /integrations/{id}/exports` exposes a file only to the same team. Download and compare its data with the sandbox source.
10. Repeat as a second team; the second team must not read, download, or see the first team's integration files/status.

## Remaining Tally Release Work

- Implement or procure a separately tested TallyPrime native XML connector if that is the target product requirement.
- Obtain representative Tally company/voucher samples and define a versioned field mapping with explicit date, decimal, currency, debit/credit, ledger, tax, and reference semantics.
- Run sandbox contract tests and reconciliation checks against the customer's actual compatible endpoint.
- Confirm the remote system honors `Idempotency-Key`; otherwise add a remote lookup/deduplication strategy before claiming retry-safe import.
- Add durable per-import/export status and user-visible error reporting; current queue failures are surfaced through Laravel failed-job operations.

Until these tasks are complete, describe this feature as a tested generic REST integration foundation, not a production-validated Tally accounting connector.
