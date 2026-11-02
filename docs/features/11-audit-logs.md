# 11 - Audit Logs

## Purpose

Provide an audit log system capturing important tenant-scoped events for observability and troubleshooting.

## Business Reason

Audit logs help auditors and developers trace actions, investigate incidents, and support compliance requirements.

## Architecture

- `AuditLog` model stores tenant-scoped audit records.
- `RecordAuditLogAction` provides a simple service to record events.
- `RecordAuditLogJob` enables optional queued audit writes.
- `AuditLogController` exposes a frontend page and a JSON list endpoint.
- The frontend page displays recent events for the current tenant.

## Folder Structure

- `app/Models/AuditLog.php`
- `app/Actions/Audit/RecordAuditLogAction.php`
- `app/Jobs/RecordAuditLogJob.php`
- `app/Http/Controllers/Audit/AuditLogController.php`
- `config/audit.php`
- `database/migrations/*_create_audit_logs_table.php`
- `resources/js/Pages/Audit/Index.tsx`
- `tests/Feature/AuditLogTest.php`
- `docs/features/11-audit-logs.md`

## Database Changes

- Added `audit_logs` table with `team_id`, `user_id`, `action`, `model_type`, `model_id`, `changes`, `ip`, `severity`, `error_code`, `message`, timestamps.

## Testing Procedure

### Manual UI test

1. Login as a user.
2. Use the app to trigger events (upload document) or use tinker to create audit logs.
3. Visit `/audit/logs`.
4. Confirm recent audit events appear and only tenant events are shown.

### Expected output

- Table lists audit events with date, action, model, message and severity.

## Tests included

- `tests/Feature/AuditLogTest.php`

## Commands

- `php artisan migrate`
- `php artisan test --filter=AuditLogTest`

## Troubleshooting

- If no logs appear, confirm `team_id` is set when creating logs.
- If unauthorized, ensure user is resolved to a team via `ResolveTeam` middleware.

## Todo

- [ ] Add UI filters for severity and date range.
- [x] Integrate audit recording into core actions (uploads, exports, auth events).
- [ ] Add export and retention policies for logs.

## Automatic recording

Automatic audit recording has been integrated for the following core actions:
- Document uploads (`document.upload`) — recorded in `UploadDocumentAction`.
- Transaction exports (`transactions.export`) — recorded in `ExportTransactionsAction`.
- Authentication events (`auth.login`, `auth.logout`, `auth.registered`, `auth.password_reset`) — recorded via `RecordAuthEventListener` wired in `EventServiceProvider`.

Recorded test results (run locally):
- `php artisan test --filter=AuditLogTest` — passed (2 tests, 6 assertions).
- Upload/export smoke tests: `DocumentUploadTest`, `TransactionExportTest` — passed.

Notes:
- Audit logging is intentionally best-effort; failures in audit recording do not block the primary action.
- Optional queue mode is available via `config/audit.php`.
- When queue mode is enabled, audit records are dispatched to `RecordAuditLogJob` and will only appear after the queue worker processes the job.
