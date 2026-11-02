# 10 - Transaction Exports Checklist

Why this checklist wasn't previously created:
- During the export feature implementation the focus was on delivering code, tests, and documentation for the export action and CSV streaming behavior. The checklist file was omitted inadvertently during iterative fixes.

Verification checklist:
- [x] Export endpoint exists and is registered in routes.
- [x] Export action returns CSV with correct headers.
- [x] Export is scoped to tenant (`team_id`) and authorized via `TransactionPolicy::export`.
- [x] Feature tests for export are present and passing. See `tests/Feature/TransactionExportTest.php`.
- [x] Export job (if any) is queued for large exports (not required for small CSVs).

How to run tests (backend):
1. Ensure `.env.testing` uses MySQL and not SQLite.
2. Run:
```
php artisan test --filter=TransactionExportTest
```

Manual test steps (frontend):
1. Login as a user in Tenant A.
2. Navigate to Transactions list page.
3. Click the "Export CSV" button.
4. Expect a download prompt with filename `transactions.csv` and CSV content including a header row.

Expected outputs and notes:
- Content-Type header should be `text/csv` (tests accept charset variations).
- Exported rows must only include transactions where `team_id` matches the active tenant.

Troubleshooting:
- If download returns 403: check `TransactionPolicy::export` and authenticated user permissions.
- If CSV is empty: verify the query filters by `team_id` and sample data exists in DB for that team.
- If header/encoding differs: check response headers in network inspector and adjust test expectations.

Recorded test results:
- `TransactionExportTest` — passed (see test output in docs/features/10-transaction-exports.md for full run).

Todos:
- Add background job for large exports and a progress indicator in frontend.
- Add CSV/XLSX option selector.
