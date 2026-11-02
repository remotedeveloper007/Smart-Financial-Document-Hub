# 07 - Queue Processing Frontend

## Purpose

Document the queue status page and how it presents background processing metrics.

## Business Reason

Junior developers and operators need a clear UI to verify that queued jobs are running and to inspect failures.

## Architecture

- `resources/js/Pages/Queue/Status.tsx` loads queue metrics from the backend.
- It uses the shared `AuthenticatedLayout` for navigation and page shell.
- It displays pending jobs, failed jobs, and recent failed job metadata.

## Folder Structure

- `resources/js/Pages/Queue/Status.tsx`

## Dependencies

- `@inertiajs/react`
- `react`
- `tailwindcss`

## Flow

1. The page mounts and calls `loadQueueStatus()`.
2. The backend endpoint returns JSON metrics.
3. The page stores `pendingJobs`, `failedJobs`, and `recentFailedJobs`.
4. The UI renders counts and a refreshed failed jobs table.

## Testing Procedure

### Manual frontend test

1. Login and open `/queue/status`.
2. Confirm the page header is `Queue Status`.
3. Confirm counts are displayed for pending and failed jobs.
4. Click the `Refresh` button.

Expected output:
- The page refreshes the job counts.
- The failed jobs table displays up to five records.
- No fetch or console errors occur.

## Troubleshooting

- If the page does not load, verify the route `queue.status.page` exists.
- If the request fails, inspect browser `Network` and confirm `route('queue.status.index')` returns JSON.
- If no failed jobs appear, confirm `failed_jobs` table contains recent entries.

## Why this is useful

Operators can quickly see whether queue processing is healthy and whether any jobs have failed.

## Line-by-line explanation

- `useEffect(() => { loadQueueStatus(); }, []);`: loads metrics when the component mounts.
- `loadQueueStatus`: fetches metrics from the backend and updates state.
- `pendingJobs` / `failedJobs`: show counts for current queue status.
- `recentFailedJobs.map(...)`: displays a table of failed job records.
- `Refresh` button: reloads the metrics on demand.
