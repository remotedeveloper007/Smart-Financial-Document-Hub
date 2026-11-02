# `app/Jobs/TallyImportFromFileJob.php`

## Purpose

Parses a previously uploaded CSV and imports its mapped transaction rows through the tenant's Tally-compatible integration.

## Dependencies

- `App\Services\Integrations\TallyService` - performs the external HTTP request.
- `App\Services\Integrations\TallyCsvImportParser` - validates and parses CSV fields.
- `App\Models\Integration` - reloads the integration by both integration and team IDs.
- Laravel private `local` storage, queue middleware, and logging.

## Inputs and outputs

- Inputs: integration ID, team ID, and a private assembled CSV path.
- The path must be inside `tenant-{team-id}/uploads/assembled/`.
- The matching `.scan.json` must identify that exact path, status `clean`, and scanner `clamav`; otherwise the job throws and must not send data externally.
- Successful parsing sends the values using a deterministic idempotency key and logs sanitized identifiers and row count.

## Usage

The integration controller dispatches this job only after validating session ownership, scan metadata, file size, and the original checksum. The job repeats the scan-status check to protect the queue boundary. It is not a native TallyPrime connector; verify provider field mappings in a sandbox.
