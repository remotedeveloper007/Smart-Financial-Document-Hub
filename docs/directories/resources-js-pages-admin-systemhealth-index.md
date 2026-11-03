# `resources/js/Pages/Admin/SystemHealth/Index.tsx`

## Purpose

This React page displays the admin System Health summary, recent faults, and recent failed jobs. It also shows when the data was last refreshed and provides a manual refresh button.

## Dependencies

- `AuthenticatedLayout` provides the signed-in application frame.
- Inertia `Head` sets the browser page title.
- `useSystemHealth` controls automatic and manual summary refresh state.
- The controller supplies the summary from the existing `fault_logs` and `failed_jobs` tables.

## Inputs and outputs

- Input: `summary`, including category totals, severity totals, and recent fault/job rows.
- Output: summary cards and recent activity panels.

## Step-by-step behavior

1. The Laravel controller sends the initial `summary` to this page.
2. The page defines TypeScript shapes for summary fields, fault rows, and failed-job rows. This prevents silently accepting arbitrary data.
3. `useSystemHealth` starts an Inertia poll and returns the last update time, refresh state, error message, and manual refresh action.
4. The summary cards use the provided counts. Missing counts display as zero.
5. Recent faults and failed jobs are rendered as lists. If a list is empty, the page displays a helpful empty state.
6. During a refresh, the status text and button change, and the button is disabled to prevent duplicate manual requests.
7. If a refresh request fails, a visible alert explains the issue and the last successfully loaded summary remains on screen.
8. The route and permission checks remain in Laravel; this page does not grant admin access.

## Usage

The route loads the page through `SystemHealthController@index`. Do not mount this page on a public route or move authorization into the React component.

## Verification

```bash
npm run test:unit -- resources/js/Pages/Admin/SystemHealth/Index.test.tsx
npm run build
```
