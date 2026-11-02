# Tally-Compatible Integration (Frontend Guide)

## Purpose and User Value

The Integrations page lets an authorized team configure a Tally-compatible REST endpoint and map CSV columns to the transaction fields expected by that endpoint. It provides settings and data-transfer controls, not accounting advice or a guarantee that TallyPrime will accept or correctly post the rows.

## Architecture and Flow

```text
Team user -> /integrations page -> save encrypted team integration settings
           -> select Tally -> upload CSV chunks -> checksum/finalize -> scan
           -> map CSV columns and preview -> POST import -> queued worker
           -> team-compatible REST endpoint
```

The browser handles settings and mapping only. Server-side policies validate team access, integration ownership, file storage path, CSV syntax, and queue dispatch. The queued worker reloads the encrypted credentials from the owning integration.

## UI Entry Point and Permissions

- Page URL: `/integrations`
- Named page route: `integrations.page`
- Integration list endpoint: `GET /integrations/list`
- Save/update endpoints: `POST /integrations`, `PUT /integrations/{integration}`
- Test endpoint: `POST /integrations/test-connection`
- Chunk workflow: `POST /integrations/{integration}/upload-session`, `PUT /integrations/{integration}/upload-chunk`, `GET /integrations/{integration}/upload-resume`, `POST /integrations/{integration}/upload-finalize`
- Import: `POST /integrations/{integration}/import`
- Export: `POST /integrations/{integration}/export`; status/list/download endpoints use the matching integration routes.
- Page access requires the configured integration-view policy; modifying settings, testing credentials, uploading, and importing require the corresponding create/update capability.

## Dependencies

- React and TypeScript.
- Inertia React layout and Ziggy route helper.
- `ImportCsvChunkedUploader.tsx` for resumable chunk upload/checksum handling.
- `ImportCsvMapper.tsx` and PapaParse for CSV preview and selected field mapping.
- Backend Tally service is a generic REST adapter; it requires a sandbox-compatible service implementing the documented endpoints.

## Manual Test Data

Create or use a sandbox REST adapter that supports:

- `GET https://tally-sandbox.example.test/api/ping`
- `POST https://tally-sandbox.example.test/api/transactions/import`
- `GET https://tally-sandbox.example.test/api/transactions/export`

Use synthetic credentials only:

- Provider: `tally`
- URL: `https://tally-sandbox.example.test/api`
- Token: sandbox-issued token
- External ID: `qa-company-01`
- Status: enabled

Sample CSV:

```csv
date,description,amount,reference
2026-10-01,"Office, supplies",25.75,INV-7
2026-10-02,"Two lines
continued",10.00,INV-8
```

Map the columns to the sandbox's documented target field names. The sample mapping in this repository preserves the selected headers as object keys; the external service must define its exact field names and semantics.

## Expected Results

1. Saving settings returns success and the integration list shows provider `tally`, enabled state, and external ID. Credentials must never appear in the list JSON or browser UI.
2. Verify connection sends the saved URL and authentication headers to `/ping`. The page shows verified only for a 2xx response; an invalid/placeholder URL is rejected without a request.
3. Chunk upload reports progress and accepts resume only for the current team/session. Finalize reports clean before displaying assembled CSV content.
4. Preview preserves `Office, supplies` as one cell, preserves the embedded newline in `Two lines\ncontinued`, and preserves the amount strings `25.75` and `10.00` exactly.
5. Import request returns `202 Accepted`; queue execution sends the exact mapped rows and stable idempotency key to `/transactions/import`.
6. Export result is available only through the matching team integration and stored under `tenant-{teamId}/exports/tally/`.

A `202` means work was accepted by the queue; it does not mean the accounting provider posted correct entries. Verify posted results in the sandbox and compare values with the source CSV.

## Browser and Network Troubleshooting

- **Provider missing in selector:** verify the React bundle is rebuilt and loaded; selector includes `tally`.
- **Connection check fails:** inspect `POST /integrations/test-connection` response and the sandbox's `/ping` response. Verify URL, token, and security-code setup without exposing secrets in screenshots.
- **Upload stalls:** inspect session creation, chunk indices, checksum values, and resume response in DevTools Network. A resumed session must belong to the same team and integration.
- **CSV preview changes values:** download/open the original and compare exact cell strings. Do not submit if amount/date/reference fields have changed or were mapped incorrectly.
- **Import job fails:** inspect admin failed-job tooling and sanitized Laravel logs by integration/team ID. Check the target API response and queue retry state; never log credentials or raw financial CSV data.
- **Export missing:** check admin queue state, export status, and team-prefixed file list. Do not search another team's storage path.

## Why These Controls Matter

Financial data can be damaged by broken quoting, lost decimal precision, wrong field mapping, duplicate retry submissions, or cross-tenant access. The UI provides a preview and the backend parser enforces CSV shape, but final correctness still depends on a documented mapping and verification against the configured accounting system.

## Maintenance Notes

- Keep provider-specific field mapping visible and versioned; never silently infer ambiguous debit/credit or tax rules.
- Add browser tests for choosing Tally, saving configuration, chunk resume, mapping, preview, import acceptance, error states, and mobile layout.
- Update this guide whenever route names, request payloads, CSV target fields, status responses, or credential handling change.
- Test against a provider sandbox before a production integration rollout. A mock HTTP test is not a live Tally accuracy test.

## Line-by-Line Orientation

### `resources/js/Pages/Integrations/Index.tsx`

- Imports the authenticated layout, Inertia page title, React state hooks, CSV import dialogs, mapper, chunk uploader, and export list.
- `Credentials` describes fields maintained in the form; secret values are held only in component state and sent to authorized backend endpoints.
- `Integration` describes list-safe display fields. The backend deliberately excludes `credentials` from integration JSON; do not add them to the returned list payload.
- State variables hold the current integrations, chosen provider, external ID, status, form credential inputs, editing row, connection-test state, toast message, and assembled CSV by integration.
- `useEffect` loads team-scoped integrations once after page mount.
- `saveIntegration` sends provider settings to create/update, refreshes the list, and tests connectivity. The backend performs validation and encryption; the UI must not assume that successful save proves remote accuracy.
- `editIntegration` fills the form from fields returned by the API. Because credentials are redacted, existing secrets should be left unchanged unless the user enters replacements; empty inputs must not clear stored values.
- The provider select contains QuickBooks, Xero, Zoho, and Tally.
- `ImportCsvMapper` displays parsed headers, lets the user map target names, previews rows, then submits the mapped objects.
- `ImportCsvChunkedUploader` creates a server session, transfers chunks, resumes and checksums them, then waits for scan completion before passing CSV text to the mapper.
- Export controls submit work asynchronously and poll for the matching integration's export file.

### `resources/js/Pages/Integrations/ImportCsvMapper.tsx`

- PapaParse reads CSV with headers and skips blank rows.
- The component stores parsed header names, original rows, selected mapping, a preview of up to 20 mapped rows, and validation/error state.
- The mapping table assigns each CSV column to a target field or Ignore.
- Preview is for human inspection; it does not validate accounting semantics.
- Start Import sends mapped rows to the authenticated integration import endpoint and expects `202` for queue acceptance.
- If mapping fields, numeric normalization, or date semantics are changed, update the backend/API tests and sandbox verification guide together.

### `resources/js/Pages/Integrations/ImportCsvChunkedUploader.tsx`

- `digestHex` computes SHA-256 checksums using the Web Crypto API.
- `toBase64` encodes byte chunks for transport.
- `chunkSize`, concurrency, retry count, active file, status, chunk state, and abort controllers control upload behavior.
- Session creation sends file name, whole-file checksum, and size; chunk upload sends an index and per-chunk checksum.
- Resume skips chunks already present for that server session.
- Finalization validates whole-file checksum and starts scanning; the component only forwards assembled CSV after server status is clean.
- Cancel/retry controls manage in-flight transfers, but must not claim a failed scan is safe to import.

## Current Automated Verification

- `php artisan test --filter='TallyServiceTest|TallyCsvImportParserTest|TallyImportFromFileJobTest|TallyExportJobTest|IntegrationJobDispatchTest|IntegrationChunkUploadTest|IntegrationChunkResumeTest'`
- Latest result: 21 passed, 65 assertions on the MySQL test database.
- `npm run test:unit -- --run`: existing frontend suite is green, but dedicated Tally page end-to-end browser coverage has not yet been added.

## Production Accuracy Limitation

No live Tally/TallyPrime sandbox credentials or representative accounting company data were available for this verification. The tests prove the application sends and preserves values according to its generic REST contract; they do not prove native Tally compatibility or accounting correctness. Keep the feature labeled as a Tally-compatible REST foundation until a real sandbox round-trip and ledger reconciliation are recorded.
