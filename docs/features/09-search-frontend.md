# 09 - Search Frontend

## Purpose

Document the transaction search interface and explain how the page works for junior developers.

## Business Reason

Search improves productivity by allowing users to find transactions quickly without navigating long lists.

## Architecture

- `resources/js/Pages/Search/Index.tsx` renders the search UI.
- The page sends the search term to the backend and receives transaction results.
- The page uses `AuthenticatedLayout` so only logged-in users can access it.

## Folder Structure

- `resources/js/Pages/Search/Index.tsx`

## Dependencies

- `@inertiajs/react`
- `react`
- `tailwindcss`

## Flow

1. User visits `/search`.
2. User types a query into the search input.
3. On form submit, the frontend calls `/search/transactions?q={query}`.
4. The backend returns matching transactions.
5. The frontend renders the results table.

## Testing Procedure

### Manual steps

1. Login as a user with `view_transactions` permission.
2. Visit `/search`.
3. Enter a term like `office`, `payment`, or `INV-1234`.
4. Click `Search`.
5. Confirm that matching rows appear.
6. Confirm the page handles empty results and shows a meaningful message.

### Sample input

- Query: `office`
- Query: `payment`
- Query: `expense`

### Expected output

- Rows matching the search term appear.
- The date is formatted as `YYYY-MM-DD`.
- The amount shows two decimals.
- The reference field shows `—` if empty.
- No console errors appear.

## Line-by-line frontend explanation

- `import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';`
  - Reuses the application layout for authenticated users.
- `import { Head } from '@inertiajs/react';`
  - Allows page title control.
- `useState('')`
  - Stores the search term.
- `useState<Transaction[]>([])`
  - Stores search results.
- `useState(false)` and `useState('')`
  - Track loading and error state.
- `const submit = async (event)`
  - Prevents form default and executes the search request.
- `fetch(route('search.transactions', { q: query }))`
  - Calls the backend search API.
- `if (!response.ok)`
  - Handles backend errors gracefully.
- `setTransactions(json.transactions)`
  - Saves returned results.
- `loading ? ... : ...`
  - Shows searching or no-results state.
- `transaction.amount.toFixed(2)`
  - Always shows two decimal places.
- `{transaction.reference ?? '—'}`
  - Shows a placeholder for missing reference data.

## Troubleshooting

- If the search button does nothing, open the browser console to see JS errors.
- If `/search/transactions` returns 401, confirm the user is logged in.
- If no results appear, search for another term or verify records exist in the database.

## Maintenance

- Add debounce to reduce request frequency.
- Add pagination and filters.
- Replace the backend search with full-text indexes for large datasets.
