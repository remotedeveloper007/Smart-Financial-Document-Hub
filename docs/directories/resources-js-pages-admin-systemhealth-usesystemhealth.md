# `resources/js/Pages/Admin/SystemHealth/useSystemHealth.ts`

## Purpose

This React hook manages the System Health page's live refresh behavior. It asks Inertia to reload the summary every 30 seconds, supports a manual refresh, and exposes loading and error state to the page.

## Dependencies

- Inertia React `usePoll` schedules partial page reloads.
- Inertia `router.reload` starts the manual partial reload.
- Inertia `router.on('exception', ...)` reports a request or connection exception.
- React state and effects store the last successful update, loading state, and visible error.

## Inputs and outputs

- Input: none. The hook always targets the current page and the `summary` prop.
- Output:
  - `lastUpdatedAt`: time of the page's initial data or latest successful refresh.
  - `isRefreshing`: whether an update is in progress.
  - `refreshError`: readable error message, or `null` when the last update succeeded.
  - `refresh()`: starts a manual refresh.

## Step-by-step behavior

1. The hook sets a 30-second refresh interval.
2. `usePoll` requests only `summary`, rather than reloading every page prop.
3. Poll mode `rest` waits for an in-flight poll to finish instead of overlapping more summary queries.
4. `onStart` shows the updating state and clears an old error.
5. `onSuccess` records the successful update time and clears the error.
6. `onError`, `onFinish`, and the global Inertia exception listener update the displayed error/loading state.
7. `refresh()` uses the same partial-prop options as the timer, so manual refresh does not use a separate endpoint or data format.
8. The effect returns Inertia's unsubscribe function when the page unmounts.

## Usage

Call the hook from the System Health page and use its returned values to render status. The hook does not calculate the health data; the existing Laravel controller remains the source of truth.

## Maintenance

- Keep the requested prop name aligned with the controller's Inertia response.
- Avoid very short intervals because each poll runs the summary database queries.
- If the page's summary becomes expensive, optimize or cache the backend calculation before increasing polling frequency.
- Do not remove route authorization; an Inertia partial request still reaches the protected Laravel route.

## Verification

```bash
npm run test:unit -- resources/js/Pages/Admin/SystemHealth/Index.test.tsx
npm run build
```
