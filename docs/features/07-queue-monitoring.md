# 07 - Queue Monitoring

## 1. Purpose

Provide a simple, tenant-aware queue monitoring page so support and junior developers can quickly detect stuck or failed jobs. This keeps the platform observable and makes it easier to react before a backlog impacts customers.

## 2. Business Reason

Background job failures are hard to detect when they happen silently. A queue status page helps teams spot pending and failed jobs and investigate issues before the processing pipeline breaks.

## 3. Architecture

- `QueueStatusController` renders the monitoring page and returns JSON metrics.
- `QueueStatusTest` verifies the page and JSON endpoint work correctly.
- `resources/js/Pages/Queue/Status.tsx` fetches the metrics and displays them in cards and a table.
- Routes are registered in `routes/web.php`.

## 4. Database Changes

No schema changes were required for this feature because it reads from existing Laravel queue tables:
- `jobs`
- `failed_jobs`

## 5. Relationship Explanation

This feature does not create a new domain model. It reads operational queue tables and exposes them to the application. It is a monitoring layer above the queue system and is deliberately thin and read-only.

## 6. Folder Structure

- `app/Http/Controllers/Queue/QueueStatusController.php`
- `resources/js/Pages/Queue/Status.tsx`
- `routes/web.php`
- `tests/Feature/QueueStatusTest.php`

## 7. File Paths

- [app/Http/Controllers/Queue/QueueStatusController.php](../..//app/Http/Controllers/Queue/QueueStatusController.php)
- [resources/js/Pages/Queue/Status.tsx](../..//resources/js/Pages/Queue/Status.tsx)
- [routes/web.php](../..//routes/web.php)
- [tests/Feature/QueueStatusTest.php](../..//tests/Feature/QueueStatusTest.php)

## 8. Flow Diagram

1. A logged-in user opens `/queue/status`.
2. The controller renders the page.
3. The frontend loads `/queue/status/metrics`.
4. Laravel reads counts from `jobs` and `failed_jobs`.
5. The page shows pending and failed job totals plus recent failed jobs.

## 9. Testing Procedure

### Automated tests

```bash
cd "c:\Users\Mazhar\Documents\github.com\remotedeveloper007\Smart Financial Document Hub"
php artisan test --filter=QueueStatusTest
```

### Manual browser test

1. Log in to the app.
2. Visit `/queue/status`.
3. Confirm the page loads with queue summary cards.
4. Trigger a failed job or insert a failed job record in the queue table.
5. Refresh the page and confirm the `failed jobs` count updates.

## 10. Troubleshooting Guide

- If the route returns 404, ensure `routes/web.php` contains the `queue.status.page` and `queue.status.index` names.
- If the page loads but shows no data, verify that `jobs` and `failed_jobs` tables exist and are populated.
- If the frontend request fails, check browser dev tools and ensure the route resolves to the correct JSON endpoint.

## 11. Maintenance Guide

- Keep this page read-only and operational; it should not trigger queue actions unless needed later.
- Add alerting or Grafana integration when this feature evolves into a production monitoring system.
- Keep the controller thin; business logic belongs in queue workers or services.

## 12. Artisan Commands Used

```bash
php artisan test --filter=QueueStatusTest
```

## 13. Verification Results

Command run:
```bash
cd "c:\Users\Mazhar\Documents\github.com\remotedeveloper007\Smart Financial Document Hub" && php artisan test --filter=QueueStatusTest
```

Result:
- 2 tests passed
- 3 assertions
- Exit code 0

## 14. Notes

This feature is intentionally minimal but production-ready. It gives the project its first reliable queue observability layer and is an appropriate next step after the AI assistant foundation.
