# 08 - Transaction Extraction Frontend

## Purpose

Document the frontend page that displays extracted transactions for the authenticated user.

## Business Reason

Operators need a clear interface to verify whether uploaded financial documents have been transformed into structured transaction records.

## Architecture

- `resources/js/Pages/Transactions/Index.tsx` renders the transactions table.
- The page uses `AuthenticatedLayout` for tenant and session context.
- Transactions are passed as page props from `TransactionController`.

## Folder Structure

- `resources/js/Pages/Transactions/Index.tsx`

## Dependencies

- `@inertiajs/react`
- `react`
- `tailwindcss`
- `AuthenticatedLayout`

## Flow

1. User visits `/transactions`.
2. The server renders `Transactions/Index` with transaction data.
3. The page displays the latest extracted transactions in a table.

## Testing Procedure

### Manual test steps

1. Login with a user who has `view_transactions` permission.
2. Navigate to `/transactions`.
3. Confirm the page title is `Transactions`.
4. Verify the table shows rows with `Date`, `Description`, `Amount`, `Type`, and `Reference`.
5. If no records exist, confirm the empty state message is shown.

### Sample input for manual testing

- Upload a PDF using `/documents/upload`.
- Use `Bank Statement` as document name.
- Confirm after processing, transactions appear.

### Expected output

- Each row displays a date in `YYYY-MM-DD` format.
- Amount values display with two decimal places.
- Reference text appears or shows `—` if unavailable.
- No JavaScript errors appear in browser console.

## Troubleshooting

- If the page is blank, check the network request for the `transactions` page.
- If rows are not displayed, confirm the controller is sending `transactions` props.
- If `AuthenticatedLayout` fails, verify user authentication and session state.

## Why this is added and useful

This frontend page gives users immediate visibility into extracted transaction data, which is the core value of the platform.

## Line-by-line explanation

- `import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';`
  - Uses the shared authenticated page wrapper.
- `import { Head } from '@inertiajs/react';`
  - Sets the browser page title.
- `type Transaction = { ... }`
  - Defines the shape of the transaction data passed from the server.
- `type Props = { transactions: Transaction[]; }`
  - Declares the page props expected from the controller.
- `export default function Index({ transactions }: Props) { ... }`
  - Renders the transaction list page.
- `AuthenticatedLayout header={...}`
  - Displays the page heading inside the app shell.
- `Head title="Transactions"`
  - Sets the browser tab text.
- `transactions.length === 0 ? ... : ...`
  - Shows an empty state message when no transactions exist.
- `transaction.amount.toFixed(2)`
  - Ensures amounts always show two decimals.
- `{transaction.reference ?? '—'}`
  - Shows a placeholder when the reference field is empty.
- This page does not perform client-side filtering yet.

## Todo

- [ ] Add sorting and filtering controls.
- [ ] Add pagination for large transaction sets.
- [ ] Add a link from document detail pages to related transactions.
