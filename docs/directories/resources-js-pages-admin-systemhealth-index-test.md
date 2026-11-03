# `resources/js/Pages/Admin/SystemHealth/Index.test.tsx`

## Purpose

This Vitest file checks the System Health page's automatic refresh setup, manual refresh button, and visible error handling without requiring a Laravel server.

## Dependencies

- Vitest runs the test cases.
- React Testing Library renders the page and clicks the Refresh button.
- Inertia's router and polling hook are mocked so the tests can inspect their options without starting a timer or sending HTTP requests.
- The authenticated layout is mocked because these tests cover the page and its data-refresh behavior.

## Step-by-step behavior

1. The test mocks `usePoll`, `router.reload`, and `router.on`.
2. The polling test renders the page with sample summary data and checks the 30-second interval, only-summary request, preserved scroll, and no-overlap mode.
3. The manual refresh test clicks the button and confirms the same summary-only reload options are sent.
4. The failure test calls the registered Inertia exception listener and confirms the page renders a readable alert.
5. Cleanup removes rendered pages and clears mock calls between tests.

## Inputs and outputs

- Input: mock summary values and simulated Inertia callbacks.
- Output: assertions about polling settings, manual refresh, and visible error feedback.

## Verification

```bash
npm run test:unit -- resources/js/Pages/Admin/SystemHealth/Index.test.tsx
```
