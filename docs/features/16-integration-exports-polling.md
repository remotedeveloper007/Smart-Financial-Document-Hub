# Integration Exports — Polling & Notifications

Purpose
- Provide an export progress UX by polling for export completion and showing a notification toast when finished.

Files changed
- Backend: `app/Jobs/TallyExportJob.php` — sets cache-based export status markers and writes exports to tenant-scoped paths.
- Backend: `app/Http/Controllers/Integrations/IntegrationController.php` — added `exportStatus()` to return cached status; listing/download endpoints updated to tenant-scoped paths.
- Frontend: `resources/js/Pages/Integrations/Index.tsx` — export button now triggers an export and polls `integrations.exports.list` until a new file appears; shows a toast notification.

How polling works
1. When the user clicks `Export`, the UI fetches the current export file list for the integration.
2. The UI calls `POST /integrations/{id}/export` to enqueue the export job.
3. The UI polls `GET /integrations/{id}/exports` every 2 seconds (up to 30s) looking for a new filename.
4. When a new file is detected, the UI shows a toast with the filename and refreshes the exports list.

Server-side status markers
- `TallyExportJob` writes cache keys under `integration_export_status:{integrationId}` tracking `running`, `completed` (with path), or `failed` states. This endpoint can be read via `GET /integrations/{id}/export-status` if desired.

Testing
- Backend tests updated to use tenant-scoped storage and passed locally:
  - `tests/Feature/IntegrationExportsTest.php` — listing & download tests.
  - `tests/Feature/IntegrationJobDispatchTest.php` — import/export job dispatch tests.

Manual QA steps
1. As a user with `Manage integrations` permission, open Integrations page.
2. For a given integration, click `Export`.
3. Observe a toast when export completes (filename shown). If the export is slow, the UI will show a timeout message.
4. Download the export via the exports list.

Notes and future improvements
- Currently polling watches for new filenames; for long-running exports a more robust pub/sub (WebSockets/Horizon events) or an explicit `export-status` polling endpoint could be used.
- The cache-based status key supports additional telemetry and can be surfaced to the UI if desired.

*** End of document ***
