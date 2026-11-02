# Admin UI Verification Checklist

Purpose: Verify the Admin UI for `fault_logs` and `failed_jobs` works correctly and performs acceptably.

Preconditions
- Application running with MySQL test/dev database.
- `php artisan migrate` has run and indexes present.
- Frontend assets built via Vite for manual testing (`npm run dev`).

Checklist

- [ ] Admin navigation visible to authorized users.
  - Login as an Owner or Admin role.
  - Confirm `Admin` link shows in the top nav.

- [ ] Fault logs list page loads.
  - Visit `/admin/fault-logs`.
  - Verify table displays fault messages and severities.
  - Confirm pagination works for large datasets.

- [ ] Fault log detail page loads.
  - Click `View` on a fault log.
  - Confirm stack trace and metadata display.

- [ ] Failed jobs list page loads.
  - Visit `/admin/failed-jobs`.
  - Confirm exceptions and failed_at times display.

- [ ] Failed job retry action works.
  - Click `View` and press `Retry`.
  - Verify the queue retry command is executed (Horizon or queue worker receives job).
  - Check user-facing status flash shows success.

- [ ] Failed job delete action works.
  - Click `Delete` on a failed job.
  - Confirm record is removed from `failed_jobs` table.

- [ ] Performance checks
  - Seed 10k fault_logs entries and measure page load times under realistic DB host.
  - Confirm index usage: `EXPLAIN` shows index on `team_id, created_at` for typical queries.

- [ ] Security checks
  - Attempt to access `/admin/*` as non-authorized user — must be 403 or redirect.
  - Ensure CSRF token is enforced for POST/DELETE actions.

- [ ] Logging and observability
  - Verify audit entry or log exists when retry/delete actions occur.

Notes & Remediation
- If performance is poor, consider:
  - Adding server-side pagination with a smaller page size.
  - Adding caching for list endpoints (Redis) with short TTL and invalidation on write.
  - Offloading old fault_logs to an archive table or S3 storage.

Runbook snippets

- Rebuild frontend assets:

```bash
npm ci
npm run build
```

- Run tests focusing on admin UI:

```bash
php artisan test --filter AdminLogsTest
php artisan test --filter AdminFailedJobsActionsTest
```

Contact
- For infra-related performance issues, check DB slow query log and `storage/logs/laravel.log` for errors.
