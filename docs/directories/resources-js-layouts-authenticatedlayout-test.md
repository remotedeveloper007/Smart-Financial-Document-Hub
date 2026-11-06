# `resources/js/Layouts/AuthenticatedLayout.test.tsx`

## Purpose

This Vitest file checks the sidebar, theme, and active-team controls without needing a Laravel server or a real login.

## Dependencies

- Vitest runs the tests.
- React Testing Library renders the layout and simulates button clicks.
- Inertia and shared layout components are mocked so each test stays focused on layout behavior.
- `localStorage` and `document.documentElement` are provided by the jsdom test environment.

## Step-by-step behavior

1. The Inertia mock supplies one test user and a small set of permissions.
2. The route mock provides route names and marks Dashboard as the current page.
3. Before each test, the mock route, browser storage, and root theme are reset.
4. The sidebar test clicks **Collapse sidebar**, checks the expand button and active link, then checks that the collapsed setting was saved.
5. The theme test clicks **Switch to dark mode**, checks the root HTML theme, and checks that the selection was saved. It also checks both responsive theme buttons use the same state.
6. The restore test starts with saved settings and confirms the layout reads them.
7. The team-switch test supplies two allowed teams, selects one, and verifies that the Inertia router posts its ID to the active-team route.
8. After each test, rendered React elements and mocked globals are cleaned up.

## Inputs and outputs

- Input: the layout with mock user, permission, and route data.
- Output: assertions about visible controls, accessible labels, active state, and saved preferences.

## Verification

Run only this test file with:

```bash
npm run test:unit -- resources/js/Layouts/AuthenticatedLayout.test.tsx
```
