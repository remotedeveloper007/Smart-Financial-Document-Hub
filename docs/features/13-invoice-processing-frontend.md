# 13 - Invoice Processing (Frontend)

Document for: `resources/js/Pages/Invoices/Index.tsx`

## Purpose

Explain the frontend invoice listing page, how it works, and how to test it manually. This file contains a line-by-line explanation to help junior developers maintain and troubleshoot the page.

## Business Reason

Provide a tenant-scoped view of extracted invoices so users can review vendor invoices, due dates, totals, and payment status.

## Architecture

- Thin Inertia page component that fetches data from an API endpoint (`/invoices/list`).
- Uses the authenticated application layout (`resources/js/Layouts/AuthenticatedLayout.tsx`).
- UI is purely read-only for now; additional actions (export, filter) are future work.

## Folder Structure

- Frontend page: `resources/js/Pages/Invoices/Index.tsx`
- Layout: `resources/js/Layouts/AuthenticatedLayout.tsx`
- Routes: Laravel route names `invoices.page` (page) and `invoices.list` (JSON API)

## Dependencies

- Inertia (`@inertiajs/react`) for page rendering
- React hooks (`useEffect`, `useState`)
- The global `route()` helper (via Ziggy) to build endpoint URLs

## Flow

1. User navigates to `/invoices` which is handled by the `InvoiceController::page()` route.
2. Laravel returns an Inertia response that mounts `Invoices/Index.tsx`.
3. The page component calls `fetch(route('invoices.list'))` to load paginated invoices.
4. The table renders invoices or shows messages when loading or empty.

## Testing Procedure (Manual)

1. Create a team with invoices in the database (or upload a document that produces invoices).
2. Log in as a user with the `view_invoices` permission.
3. Visit `/invoices` in the browser.
4. Confirm:
   - The page header shows `Invoices`.
   - When invoices exist, rows appear with correct `issue_date`, `vendor_name`, `invoice_number`, `total_amount` (2 decimals), `currency`, `status`, `reference`.
   - When none exist, the message `No invoices found yet.` is visible.
   - When the API is slow, `Loading...` appears while fetching.

Network/console expectations:
- Network call: `GET /invoices/list` returns JSON with `invoices` key and `data` array.
- Successful response code: `200 OK`.
- If `403` returned, ensure the user has the `view_invoices` permission.

## Tests included (backend)

- `tests/Feature/InvoiceFeatureTest.php` — checks page access and tenant isolation.
- `tests/Feature/Authorization/InvoicePolicyTest.php` — validates policy permissions.

## Troubleshooting (Browser & Console)

- Symptom: Page shows `No invoices found yet.` but DB has invoices.
  - Confirm the logged-in user belongs to the same team as the invoices.
  - Open the Network tab and inspect `/invoices/list` response JSON — ensure `invoices.data` contains records.
  - Confirm backend `InvoiceController::list()` filters by `team_id`.

- Symptom: Page shows `403 Forbidden`.
  - Confirm the user has `view_invoices` permission and roles correctly assigned.
  - Seed permissions with `AuthorizationSeeder` or run the role/permission setup used in tests.

- Symptom: UI shows `Loading...` forever.
  - Check for JS errors in console (CORS, authentication cookie issues, Ziggy `route()` missing).
  - Confirm the `route('invoices.list')` helper is available in page context.

## Sample Inputs (manual)

- Browser GET: `/invoices`
- API GET: `/invoices/list` → expects JSON with paginated invoices

Example JSON response shape (simplified):

{
  "invoices": {
    "data": [
      {"id":1,"vendor_name":"Acme Ltd","invoice_number":"INV-2026-001","issue_date":"2026-07-01","due_date":"2026-07-31","total_amount":1200.50,"currency":"USD","status":"unpaid","reference":"REF-1234"}
    ],
    "links": {...},
    "meta": {...}
  }
}

## Expected Output

- Table rows rendered with formatted numbers (two decimals) and readable dates.
- Pagination controls (backend-provided links) may be implemented later.

## Line-by-line explanation (`resources/js/Pages/Invoices/Index.tsx`)

Below, each block is explained so a junior developer can maintain the file.

1. Imports

- `import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';`
  - Uses the shared authenticated layout that provides nav and page chrome.

- `import { Head } from '@inertiajs/react';`
  - Allows setting the page title for the browser and Inertia.

- `import { useEffect, useState } from 'react';`
  - React hooks used for lifecycle and state management.

2. Type definitions

- `type Invoice = { ... }` — help TypeScript ensure correct property names and types.
  - `id`: invoice primary key
  - `vendor_name`: vendor/company name
  - `invoice_number`: vendor invoice number
  - `issue_date`, `due_date`: ISO date strings
  - `total_amount`: numeric amount (two decimals expected)
  - `currency`: ISO currency code e.g., `USD`
  - `status`: `paid|unpaid|overdue`
  - `reference`: optional additional reference

3. Component state

- `const [invoices, setInvoices] = useState<Invoice[]>([]);`
  - Holds the array of invoices to display.

- `const [loading, setLoading] = useState(false);`
  - Tracks whether a fetch is in progress.

4. `useEffect` and data fetching

- `useEffect(() => { fetchInvoices(); }, []);`
  - Calls `fetchInvoices` once when the component mounts.

- `const fetchInvoices = async () => { ... }`
  - Implementation details:
    - `setLoading(true)` before the request so the UI shows a loading state.
    - Calls `fetch(route('invoices.list'))` — Ziggy provides the `route` helper.
    - Parses JSON and updates `setInvoices(json.invoices.data || [])`.
    - `finally { setLoading(false); }` ensures loading resets on success or failure.

5. Render structure

- `AuthenticatedLayout` wraps the page; pass `header` prop for the title.
- `<Head title="Invoices" />` sets the browser title.
- The main container renders a card with a header and the table.

6. Table rendering

- When `loading` is true: show a single row with `Loading...`.
- When `invoices.length === 0`: show `No invoices found yet.` message.
- Otherwise map `invoices.map((invoice) => ( ... ))` and render each invoice row.
  - `invoice.total_amount.toFixed(2)` ensures two decimal places.
  - `invoice.reference ?? '—'` shows em dash when reference is null.

7. Extensibility points (where to extend)

- Add filtering controls (date range, vendor search) above the table.
- Add export button (CSV) that calls a backend export route.
- Add links from invoice rows to a detail page when implemented.

## Maintenance notes

- Keep the component thin: heavy tasks (filtering large datasets, exports) should be server-side and queued where appropriate.
- If you change the API shape, update the TypeScript `Invoice` type and UI mapping accordingly.
- For large datasets, implement server-side pagination and lazy loading.

## How a junior developer can test a change locally

1. Seed or create a team and a user with `view_invoices` permission.
2. Run migrations and seeds:

```bash
php artisan migrate --seed
```

3. Start the dev server and visit `/invoices`.

## Files to review when debugging

- Backend: `app/Http/Controllers/Invoices/InvoiceController.php`
- Backend: `app/Policies/InvoicePolicy.php`
- Frontend: `resources/js/Pages/Invoices/Index.tsx`
- Shared layout: `resources/js/Layouts/AuthenticatedLayout.tsx`

---

If you'd like, I can now:
- Add a small unit test for a UI helper (JS/TS test) or
- Expand this doc with a line-by-line explanation for the layout changes I made earlier.

## Layout changes: `resources/js/Layouts/AuthenticatedLayout.tsx` (Line-by-line)

This section explains the layout changes made to the authenticated app shell so a junior developer can understand and maintain the file.

1. Imports

- `import { Link } from '@inertiajs/react';`
  - Provides navigation helpers that preserve SPA behavior when navigating between Inertia pages.

- `import React from 'react';`
  - Standard React import for JSX support.

2. Component signature

- `type Props = { children: React.ReactNode; header?: React.ReactNode }`
  - `children`: the page content injected into the layout.
  - `header`: optional top header content (used by pages to set the page title area).

- `export default function AuthenticatedLayout({ children, header }: Props)`
  - The default export is a functional component that composes the authenticated UI.

3. Top navigation markup

- The top nav contains:
  - Brand/logo link to the dashboard.
  - Primary navigation links (e.g., `Invoices`) added for quick access.
  - A user menu (profile, sign out) on the right.

- Reason: keep global navigation consistent across protected pages and allow adding feature links (like `Invoices`) in one place.

4. Mobile navigation handling

- A toggle button controls mobile nav visibility. The layout renders a condensed nav for small screens.

- Reason: ensure responsive behavior — the same links are available on mobile and desktop.

5. `header` slot and page title

- The layout renders the optional `header` prop inside a page title container if provided.

- Reason: pages can pass a small title or controls (buttons) to appear consistently below the nav.

6. Main content wrapper

- The `children` are wrapped in a centered container with padding and max width.

- Reason: keep consistent spacing and readable line length across pages.

7. Accessibility and semantic elements

- The layout uses semantic tags (`nav`, `main`, `header`) and visible focus states on links.

- Reason: improve keyboard navigation and screen-reader behavior for accessibility.

8. Auth and sign-out flow (thin controller behavior)

- The sign-out link calls a route that triggers server-side logout (thin action). The layout itself does not run business logic — it only links to the route.

- Reason: keep the layout presentation-only and delegate business logic to controllers or actions.

9. Nav link additions for invoices

- Where to add: locate the primary nav links array and add an `Invoices` item that points to `route('invoices.page')`.

- Example (JSX):

  - `<Link href={route('invoices.page')} className="nav-link">Invoices</Link>`

- Reason: adding links here makes the feature discoverable across the app without modifying each page.

10. Maintenance tips

- Keep layout thin: avoid fetching feature data inside the layout. If you need user metadata, inject it via the Inertia root response so the layout only reads props.

- When adding new nav items, ensure they are permission-guarded server-side and that the UI shows/hides links based on the current user's permissions (pass a `canViewInvoices` boolean in the Inertia props if needed).

11. Debugging checklist

- Symptom: nav link missing on the page.
  - Check: `resources/js/Layouts/AuthenticatedLayout.tsx` contains the link.
  - Check: the route name `invoices.page` exists in `routes/web.php`.
  - Check: Inertia root props include the signed-in user and team context if link visibility is conditional.

- Symptom: mobile nav toggle not working.
  - Check: confirm the toggle state uses `useState` and that the button `onClick` toggles the state.
  - Check: no JS runtime errors in console.

Files to review when debugging layout issues

- `resources/js/Layouts/AuthenticatedLayout.tsx`
- `resources/js/Pages/Invoices/Index.tsx`
- `routes/web.php`
- `app/Providers/InertiaServiceProvider.php` (or wherever Inertia root props are set)

---

If you'd like, I can also insert inline code comments into `resources/js/Layouts/AuthenticatedLayout.tsx` to document each JSX block directly in the source file. Tell me if you'd like that and whether to add comments or separate JSDoc-style blocks.

Stop after review.