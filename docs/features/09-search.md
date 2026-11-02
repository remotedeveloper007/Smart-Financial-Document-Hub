# 09 - Search

## Purpose

Implement tenant-scoped search for extracted transactions as the next step in the roadmap.

## Business Reason

Search is essential for users to quickly find extracted transaction records without manually browsing large lists. It improves productivity and makes the financial data actionable.

## Architecture

- `SearchController` renders the search page and returns JSON results.
- `TransactionSearchAction` performs tenant-scoped database search.
- The search endpoint uses `view_transactions` authorization.
- The frontend page sends search queries and displays results dynamically.

## Folder Structure

- `app/Actions/Search/TransactionSearchAction.php`
- `app/Http/Controllers/Search/SearchController.php`
- `resources/js/Pages/Search/Index.tsx`
- `tests/Feature/TransactionSearchTest.php`
- `docs/features/09-search.md`
- `docs/features/09-search-frontend.md`
- `docs/features/09-search-checklist.md`

## Database Changes

No new tables were added for search. The feature uses existing `transactions` records and tenant filtering.

## Eloquest Relationship

- Search is performed over `Transaction` records.
- `Transaction::query()` is tenant-scoped by `team_id`.
- No new relationship changes were required.

## Testing Procedure

### Manual UI test

1. Login with a user who has `view_transactions` permission.
2. Open `/search`.
3. Enter `office`, `payment`, `expense`, or other terms.
4. Submit the search form.
5. Confirm matching transactions appear.
6. Confirm only transactions from your team are returned.

### Expected output

- Search results appear in the table.
- Date, description, amount, type, and reference display correctly.
- No JavaScript errors appear in browser console.

## Tests included

- `tests/Feature/TransactionSearchTest.php`

## Commands used

- `php artisan make:controller Search/SearchController`
- `php artisan make:action Search/TransactionSearchAction`
- `php artisan test --filter=TransactionSearchTest`

## Actual test results

- `php artisan test --filter=TransactionSearchTest` passed with 3 tests.

## Troubleshooting

- If search returns no results, verify the query string is sent to `/search/transactions`.
- If the page does not load, confirm the route `search.page` exists.
- If unauthorized, verify the user has `view_transactions` permission.

## Todo

- [ ] Add search across documents and invoices.
- [ ] Add full-text indexing for larger datasets.
- [ ] Add search filters by date range and amount.
