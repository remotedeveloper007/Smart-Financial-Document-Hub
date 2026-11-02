# 10 - Transaction Export

## Purpose

Add a tenant-scoped export feature so authorized users can download structured transaction data as CSV.

## Business Reason

Exporting data is required for finance teams that need to continue reconciliation, reporting, and auditing outside the application. CSV is a universal format for accounting tools.

## Architecture

- `TransactionController::export` handles the export request.
- `ExportTransactionsAction` builds a CSV response from tenant-scoped transactions.
- Authorization is enforced through `TransactionPolicy::export`.
- The `Transactions` page contains an export button.

## Folder Structure

- `app/Actions/Transactions/ExportTransactionsAction.php`
- `app/Http/Controllers/Transactions/TransactionController.php`
- `app/Policies/TransactionPolicy.php`
- `resources/js/Pages/Transactions/Index.tsx`
- `tests/Feature/TransactionExportTest.php`
- `docs/features/10-transaction-export.md`

## Dependencies

- Laravel response streaming
- Tenant context via `TeamContext`
- Transaction model with tenant global scope

## Flow

1. The user clicks `Export CSV` on the transactions page.
2. The browser sends a request to `/transactions/export`.
3. `TransactionController::export` checks permission and resolves the tenant.
4. `ExportTransactionsAction` fetches tenant transactions and streams a CSV file.
5. The browser downloads the exported CSV.

## Testing Procedure

### Manual Frontend steps

1. Login as a user with `export_transactions` permission.
2. Navigate to the `Transactions` page.
3. Click `Export CSV`.
4. Confirm a CSV file downloads.
5. Open the CSV and verify it contains `Date,Description,Amount,Type,Reference`.
6. Confirm the exported rows belong only to the current team.

### Sample input

- Transaction description: `Exported transaction`
- Amount: `4500.00`
- Date: `2026-07-21`
- Type: `income`
- Reference: `EXP-001`

### Expected output

- Downloaded CSV file named like `transactions-2026-07-21-... .csv`
- First row header: `Date,Description,Amount,Type,Reference`
- Transaction row includes `Exported transaction` and `4500.00`

## Tests included

- `tests/Feature/TransactionExportTest.php`

## Troubleshooting

- If the export button is missing, ensure the user has `export_transactions` permission and that `route('transactions.export')` is available.
- If the download fails, verify the route is protected by `TransactionPolicy::export`.
- If the CSV is blank, ensure tenant-scoped transactions exist and `ExportTransactionsAction` returns the correct query.

## Why this is useful

Exporting provides offline access for auditors and accountants, and it establishes a stable data handoff point from the platform to external tools.

## Line-by-line frontend explanation

- `href={route('transactions.export')}`
  - Builds the export URL from Laravel routes.
- `inline-flex items-center rounded-md ...`
  - Styles the export button using Tailwind.
- `Export CSV`
  - Clear CTA label for the export feature.

## Maintenance

- Future improvement: export filters by date range.
- Future improvement: support XLSX and JSON exports.
- Ensure new export fields add matching CSV columns.

## Todo

- [ ] Add date-range export filters.
- [ ] Add XLSX export.
- [ ] Add export analytics audit logging.
