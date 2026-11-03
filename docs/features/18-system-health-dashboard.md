# 18 - System Health Dashboard

## Purpose

Provide a clear, admin-level dashboard that summarizes the current health of the platform. The dashboard helps junior developers and admins quickly see whether the system has errors, warnings, failed jobs, queue problems, storage issues, permission issues, or database failures.

## Business Reason

Production systems fail quietly if there is no clear monitoring summary. This dashboard gives support teams and developers a single place to review operational health, understand the severity of issues, and react faster before a small problem becomes a larger outage.

## Architecture

The system health feature follows the existing admin pattern:

- a controller gathers counts and recent records;
- a Blade stub is used during tests for HTML-based assertions;
- an Inertia React page renders the dashboard for real app usage;
- an Inertia polling hook reloads only the summary prop every 30 seconds and also supports manual refresh;
- the model data comes from `fault_logs` and `failed_jobs`.

This is intentionally simple, maintainable, and focused on observability rather than business logic.

## Database Changes

This feature reads from existing tables rather than adding a new schema change:

- `fault_logs`
- `failed_jobs`

It depends on the data captured by the fault middleware and queue worker records.

## Eloquent / Data Model

The dashboard reads:

- `FaultLog` records for service health and error severity
- `failed_jobs` records for queue reliability

The dashboard groups issues into categories such as:

- errors
- warnings
- failed jobs
- slow queries
- queue issues
- storage issues
- permission issues
- API failures
- database failures

## File Paths

- Controller: `app/Http/Controllers/Admin/SystemHealthController.php`
- Route: `routes/web.php`
- Frontend: `resources/js/Pages/Admin/SystemHealth/Index.tsx`
- Frontend state hook: `resources/js/Pages/Admin/SystemHealth/useSystemHealth.ts`
- Frontend test: `resources/js/Pages/Admin/SystemHealth/Index.test.tsx`
- Stub view: `resources/views/admin/system_health/index_stub.blade.php`

## Flow Diagram

```text
Admin opens /admin/system-health
        ↓
SystemHealthController@index
        ↓
Counts faults and failed jobs
        ↓
Builds summary payload
        ↓
Inertia renders SystemHealth/Index
        ↓
Dashboard displays recent faults and failed jobs
        ↓
useSystemHealth polls only the summary every 30 seconds
        ↓
Inertia updates the dashboard and the last-updated time
```

## Testing Procedure

### Manual browser testing

1. Log in as an admin or owner-level user.
2. Visit `/admin/system-health`.
3. Confirm the page shows summary cards.
4. Confirm the page shows the last-updated time and an accessible refresh icon.
5. Add a fault log record to `fault_logs` and wait up to 30 seconds, or select Refresh.
6. Verify that counts and recent faults update without a full page navigation.
7. Add a failed job record to `failed_jobs` and check Recent Failed Jobs in the same way.
8. Disconnect the browser temporarily, select Refresh, and confirm the page displays an error instead of silently appearing current.

### Expected result

- System Health header is visible.
- Summary card counts render correctly.
- Recent fault and failed job panels populate with data.
- The dashboard refreshes its summary every 30 seconds and offers a manual refresh icon.
- Refresh status and the last successful update are visible.
- A failed refresh displays an error while keeping the last available summary visible.
- No data from unrelated teams is shown.

## Feature Tests

Implemented test:

- `tests/Feature/SystemHealthDashboardTest.php`
- `resources/js/Pages/Admin/SystemHealth/Index.test.tsx`

The backend feature test verifies that the protected dashboard renders. The frontend test verifies the polling interval and partial-prop request, manual refresh, and visible connection-error state.

## Verification Commands

```bash
cd "c:\Users\Mazhar\Documents\github.com\remotedeveloper007\Smart Financial Document Hub"
php artisan test --filter='FaultCaptureTest|AdminLogsTest|SystemHealthDashboardTest'
```

## Test Result

Backend test result from the prior verification:

- 4 tests
- 13 assertions

Run the focused frontend test:

```bash
npm run test:unit -- resources/js/Pages/Admin/SystemHealth/Index.test.tsx
```

Run the full frontend suite:

```bash
npm run test:unit
```

## Troubleshooting Guide

### Issue: page returns 403

- Confirm the user has `viewAdmin` access through the owner/admin gate.
- Check the `AuthServiceProvider` gate configuration.

### Issue: counts are zero while data exists

- Confirm `fault_logs` and `failed_jobs` rows are present in the database.
- Check whether the query patterns are matching text in the records.
- Ensure the middleware is correctly writing faults on exceptions.

### Issue: page loads but no recent faults appear

- Verify the `fault_logs` table contains rows with valid `message` and `created_at` values.
- Check if the query is filtering unexpectedly.

### Issue: the dashboard does not refresh

- Confirm the browser remains on the System Health page and has an active network connection.
- Check the browser console and network panel for a failed Inertia request to `/admin/system-health`.
- Confirm the request is made with only the `summary` prop.
- Use the Refresh button to request an update immediately.
- Polling is set to 30 seconds to avoid unnecessary requests every few seconds.

### Issue: the data is old after a failed refresh

- The page deliberately keeps the most recent summary on screen and shows an error.
- Restore the connection and use Refresh, or wait for the next polling attempt.
- The last-updated time changes only after a successful response.

## Maintenance Guide

- Keep the dashboard focused on summary and diagnosis.
- Extend with better filters and export options later.
- For a larger production environment, move summary calculations into a dedicated service or cache layer.
- Keep severity labels consistent with other admin monitoring features.
- Keep polling scoped to the `summary` prop so unrelated page props are not repeatedly requested.
- Keep the refresh interval reasonable for the database query cost.
- Preserve the Laravel `viewAdmin` route protection; client-side polling is not an authorization boundary.

## Checklist

- [x] Admin summary page exists
- [x] Fault counts are displayed
- [x] Failed job counts are displayed
- [x] Recent fault entries are listed
- [x] Recent failed jobs are listed
- [x] Route is protected by admin access
- [x] Automated test is included
- [x] Dashboard summary is polled every 30 seconds
- [x] Manual refresh and refresh-error feedback are implemented
- [x] Verification command is recorded
