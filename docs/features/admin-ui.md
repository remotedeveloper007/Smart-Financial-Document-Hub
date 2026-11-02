# Admin UI (Inertia + React)

## Purpose

Replace Blade admin pages for `failed_jobs` and `fault_logs` with Inertia + React pages.

## Files Added

- `resources/js/Pages/Admin/FaultLogs/Index.tsx`
- `resources/js/Pages/Admin/FaultLogs/Show.tsx`
- `resources/js/Pages/Admin/FailedJobs/Index.tsx`
- `resources/js/Pages/Admin/FailedJobs/Show.tsx`
- Controllers updated to return Inertia responses: `app/Http/Controllers/Admin/FaultLogController.php`, `app/Http/Controllers/Admin/FailedJobController.php`.

## How to build

Run the frontend build/watch:

```bash
npm install
npm run dev
# or
npm run build
```

Ensure `vite` is running for Inertia pages to load during development.

## How to test

Backend feature tests exercise the admin routes. Run:

```bash
php artisan test --filter AdminLogsTest
```

The tests disable middleware to focus on page rendering.

## Caching

The admin list endpoints use a short Redis-backed cache (configurable via `admin.cache_ttl_seconds`, default 30s). Cache keys are per-team and per-page to avoid cross-tenant leakage.

Invalidation: cache is flushed on delete and retry actions using cache tags when supported by the cache driver (Redis). If tags are not available, the controller falls back to explicit key invalidation for the first 5 pages.

Config example in `.env`:

```
CACHE_DRIVER=redis
ADMIN_CACHE_TTL_SECONDS=30
```

## Manual QA

1. Log in as an admin user.
2. Visit `/admin/fault-logs` — you should see the Fault Logs table.
3. Click `View` on a fault log to open details.
4. Visit `/admin/failed-jobs` — you should see Failed Jobs table and view details.

## Notes

- The components are intentionally minimal and use Tailwind classes. They can be extended with pagination controls and action buttons (retry/delete) using Inertia `post`/`delete` requests.
- Ensure Vite is configured and the JS assets are compiled for production.
