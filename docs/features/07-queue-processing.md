# 07 - Queue Processing

## Purpose

Implement queue processing visibility and support for background worker jobs.

## Business Reason

Queue processing is required to keep document extraction asynchronous and prevent long-running tasks from blocking the user experience.

## Architecture

- `QueueStatusController` provides queue metrics and recent failures.
- `Queue/Status.tsx` displays queue health in the authenticated dashboard.
- The upload flow dispatches `ProcessDocumentJob` to the queue.
- Queue metrics are derived from `jobs` and `failed_jobs` tables.

## Folder Structure

- `app/Http/Controllers/Queue/QueueStatusController.php`
- `resources/js/Pages/Queue/Status.tsx`
- `tests/Feature/QueueStatusTest.php`
- `docs/features/07-queue-processing.md`
- `docs/features/07-queue-processing-frontend.md`
- `docs/features/07-queue-processing-checklist.md`

## Dependencies

- `Inertia.js`
- `React`
- `TypeScript`
- `tailwindcss`
- `Illuminate\Support\Facades\DB`

## Flow

1. User navigates to `/queue/status`.
2. `QueueStatusController::page()` renders the queue dashboard.
3. The frontend fetches metrics from `queue.status.index`.
4. The backend counts pending and failed queue entries.
5. Recent failed jobs are displayed for operator review.

## Testing Procedure

### Manual UI test

1. Login as any authenticated user.
2. Open `/queue/status`.
3. Confirm the page loads and shows pending and failed job counts.
4. Use the Refresh button to reload the latest metrics.

Expected output:
- The page displays a pending jobs number and failed jobs number.
- The failed jobs table shows recent failed job records.
- No JavaScript or fetch errors appear in the console.

### Console / Network expectations

- `GET /queue/status` returns HTTP 200.
- `GET /queue/status/list` returns JSON containing `pending_jobs`, `failed_jobs`, and `recent_failed_jobs`.

## Tests included

- `tests/Feature/QueueStatusTest.php`

## Troubleshooting

- If the dashboard fails, confirm authentication is valid.
- If metrics do not load, inspect the network request to `queue.status.index`.
- If job counts are incorrect, confirm the queue tables `jobs` and `failed_jobs` contain records.

## Why this is added and useful

Queue processing visibility helps operators monitor background work and verify that document jobs are being processed as expected.

## Todo

- [ ] Add a full failed-job detail view.
- [ ] Add queue worker health checks.
- [ ] Add alerting for failed job spikes.
