# 10 - Transaction Exports (Frontend)

File: resources/js/Pages/Transactions/Index.tsx

Purpose
- Explain the frontend page that displays recent transactions and provides an "Export CSV" action.

Business Reason
- Allows users to review recently extracted transactions and export them for offline analysis or bookkeeping.

Architecture
- Inertia + React page component rendered inside `AuthenticatedLayout`.
- Uses server-provided `transactions` prop and a direct anchor to the export route.

Folder structure
- `resources/js/Pages/Transactions/Index.tsx` — page component shown to authenticated users.

Dependencies
- React, Inertia, Tailwind CSS, project `AuthenticatedLayout`.

High-level flow
1. Laravel controller renders the page and passes `transactions` as a prop via Inertia.
2. The page renders a table and an Export button linking to `route('transactions.export')`.
3. Clicking Export triggers a backend CSV response (download).

Manual testing procedure (frontend)
1. Login as a user belonging to a team with extracted transactions.
2. Visit the Transactions page (UI route used by your app).
3. Confirm the table lists transactions with Date/Description/Amount/Type/Reference.
4. Click "Export CSV" — the browser should start a CSV download containing only your team's transactions.

Sample data (JSON) to seed for manual testing
```
{
  "transactions": [
    {"id":1,"description":"Office Supplies","amount":42.5,"transaction_date":"2026-07-01","type":"debit","reference":"INV-001"},
    {"id":2,"description":"Client Payment","amount":1500.0,"transaction_date":"2026-07-02","type":"credit","reference":null}
  ]
}
```

Expected frontend output
- Table with two rows showing the sample data. Amounts formatted with two decimals. Missing reference shown as `—`.

Troubleshooting (frontend)
- 401/419 on export click: the export anchor should hit an authenticated route. If CSRF/session issues occur during AJAX-based exports, use Inertia.post or ensure cookies are sent.
- Empty table: verify the server passes `transactions` prop and filters by `team_id`.

Line-by-line explanation (simple English)
- Lines 1-2: Import `AuthenticatedLayout` and `Head` for page layout and document title.
- Lines 4-13: Define `Transaction` TypeScript type describing the shape of each transaction.
- Lines 15-18: Props type expects an array of `transactions`.
- Line 20: Export default React function component `Index` that accepts `transactions`.
- Lines 21-29: `AuthenticatedLayout` wrapper with a header showing "Transactions". This ensures the page inherits the app layout and auth guard.
- Line 30: `<Head title="Transactions" />` sets the browser title.
- Lines 32-60: Layout container and card UI — standard Tailwind classes for spacing and box styles.
- Lines 61-73: Top bar containing a duplicated description block and the Export button. The Export anchor uses `route('transactions.export')` (Laravel route helper exposed to JS) to download CSV.
- Lines 74-101: Responsive table wrapper and header row columns: Date, Description, Amount, Type, Reference.
- Lines 102-127: Table body. If `transactions.length === 0` render a single-row empty state message. Otherwise map over `transactions` and render each row.
- Line 113: `transaction.transaction_date` — date as provided by backend. Consider formatting on server or use a client formatter (e.g., `Intl.DateTimeFormat`) for local display.
- Line 119: `transaction.amount.toFixed(2)` — formats numeric amount with two decimal places.
- Line 125: `transaction.reference ?? '—'` — show em-dash when reference is null.
- Lines 128-138: Close layout and export component.

Files touched by this frontend page
- Backend: controller that returns Inertia render for this page and provides `transactions` prop.

Why this is useful
- Simple, readable UI for verifying extraction results and exporting tenant-scoped data.
