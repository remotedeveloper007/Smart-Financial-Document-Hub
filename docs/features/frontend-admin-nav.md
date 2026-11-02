**Admin Nav (Frontend)**
- **Purpose:** Use Inertia shared props to show/hide the Admin navigation link based on server-side authorization.
- **Files:**
  - `resources/js/Layouts/AuthenticatedLayout.tsx` (layout with nav guard)
  - `resources/js/Layouts/AuthenticatedLayout.jsx` (JSX fallback)
- **Behavior:** The server shares `can.viewAdmin` via `Inertia::share`. The layout reads `usePage().props.can.viewAdmin` and conditionally renders the Admin link.
- **How to test (manual):**
  1. Log in as a user authorized for Admin (Gate::allows('viewAdmin') true) — Admin link appears.
  2. Log in as a non-authorized user — Admin link hidden.
- **How to test (automated):** See `tests/Feature/InertiaSharedPropsTest.php`.
- **Notes:** Keep server-side route/middleware protections in place; client-side hiding is UX-only and not a substitute for server authorization.
