# 12 - Billing Foundation (Frontend)

File: resources/js/Pages/Billing/Plans.tsx

Purpose
- Document the frontend plans page used to display subscription plans and initiate subscription creation.

Business Reason
- Provides a simple UI for tenants to view pricing and subscribe, enabling monetization flow and manual QA of billing endpoints.

Architecture
- React + Inertia page component that fetches plan data via `fetch()` and calls a subscription endpoint with JSON POST.

Folder structure
- `resources/js/Pages/Billing/Plans.tsx` — billing Plans page.

Dependencies
- React, Inertia, Tailwind CSS, `AuthenticatedLayout`.

High-level flow
1. On mount (`useEffect`) the page calls `fetchPlans()` to load available plans from `route('billing.plans')`.
2. Plans are stored in local state and rendered as cards.
3. The Subscribe button calls `subscribe(planId)` which POSTs to `route('billing.subscriptions.store')`.

Manual testing procedure (frontend)
1. Ensure at least one plan exists in DB (`plans` table).
2. Login as a team admin or owner.
3. Visit the Billing → Plans page.
4. Verify plan cards show `name`, `description`, and `price`.
5. Click `Subscribe` on a plan; expect a success alert and a subscription created for your team.

Sample plan JSON (for manual testing/simulating the API)
```
{
  "plans": [
    {"id":1,"slug":"starter","name":"Starter","description":"Basic plan","price":0,"currency":"USD"},
    {"id":2,"slug":"pro","name":"Pro","description":"Full features","price":299,"currency":"USD"}
  ]
}
```

Expected frontend output
- Plan cards listed in a responsive grid. Clicking `Subscribe` triggers a POST and shows an alert on success/failure.

Troubleshooting
- 419 CSRF: using `fetch()` to POST may trigger CSRF protection. Options:
  - Use `Inertia.post(route('billing.subscriptions.store'), { plan_id: planId })` to let Inertia handle CSRF and redirects.
  - Include `X-CSRF-TOKEN` header from `<meta name="csrf-token" content="...">`.
- 401 Unauthorized: ensure user session/cookies are present and the route is protected correctly.
- Empty plans: ensure `billing.plans` endpoint returns JSON `{ plans: [...] }`.

Line-by-line explanation (simple English)
- Lines 1-3: Import `AuthenticatedLayout`, `Head`, and React hooks.
- Lines 5-13: Define `Plan` type describing a plan record (id, slug, name, price, currency).
- Lines 15-17: Component `Plans` uses state: `plans` array and `loading` boolean.
- Lines 19-22: `useEffect` runs `fetchPlans()` once when component mounts.
- Lines 24-33: `fetchPlans` sets `loading`, requests `route('billing.plans')`, parses JSON and sets `plans`. Errors are silently ignored (noop).
- Lines 35-47: `subscribe` function sends a JSON `POST` to the subscription store route. On success, an alert is shown. In production replace `alert` with proper UI feedback.
- Lines 49-70: JSX that renders the header, document title and a responsive grid of plan cards.
- Lines 71-79: For each `plan` render name, description, price and a Subscribe button that calls `subscribe(plan.id)`.
- Line 81: Conditional render: if `loading` show a `Loading plans...` message.

Why this is useful
- Provides a minimal, testable frontend for subscription flows while backend payment integration is implemented separately.

Frontend maintenance notes
- Replace `fetch()`+`alert()` with Inertia helpers and proper UX (modals, toasts).
- Add optimistic UI state and error messages instead of `alert()`.
- Add client-side validation for plan IDs and user role checks.
