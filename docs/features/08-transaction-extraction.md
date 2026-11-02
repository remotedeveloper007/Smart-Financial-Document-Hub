# 08 - Transaction Extraction

## Purpose

Implement the first transaction extraction stage and connect it to the document processing pipeline.

## Business Reason

Financial documents become valuable only after their transaction data is extracted, stored, and made available for reporting. This feature sets up the extraction pipeline so uploaded documents become structured transaction records.

## Architecture

- `ProcessDocumentJob` now invokes extraction and storage actions.
- `ExtractTransactionsAction` provides a fake extraction engine for initial development.
- `StoreTransactionsAction` persists extracted transactions in a MySQL-backed `transactions` table.
- `TransactionController` and frontend page show extracted records.
- `TransactionPolicy` guards transaction visibility.

## Database Changes

- Added `transactions` table with:
  - `team_id`
  - `document_id`
  - `external_id`
  - `description`
  - `amount`
  - `transaction_date`
  - `type`
  - `reference`
  - `created_at`
  - `updated_at`

## Eloquest Relationship

- `Document` has many `Transaction` records.
- `Transaction` belongs to `Document` and `Team`.
- Shared `team_id` ensures tenant isolation using the global `BelongsToTeam` scope.

## Folder Structure

- `app/Actions/Documents/ExtractTransactionsAction.php`
- `app/Actions/Transactions/StoreTransactionsAction.php`
- `app/Jobs/ProcessDocumentJob.php`
- `app/Models/Transaction.php`
- `app/Policies/TransactionPolicy.php`
- `app/Http/Controllers/Transactions/TransactionController.php`
- `database/migrations/0001_01_01_000008_create_transactions_table.php`
- `resources/js/Pages/Transactions/Index.tsx`
- `tests/Feature/TransactionExtractionTest.php`
- `docs/features/08-transaction-extraction.md`
- `docs/features/08-transaction-extraction-frontend.md`
- `docs/features/08-transaction-extraction-checklist.md`

## Dependencies

- Laravel 13
- Inertia.js
- React
- TypeScript
- MySQL
- Tailwind CSS

## Flow

1. User uploads a document via `/documents/upload`.
2. `UploadDocumentAction` stores metadata and dispatches `ProcessDocumentJob`.
3. `ProcessDocumentJob` marks the document as processing.
4. `ExtractTransactionsAction` returns sample transactions.
5. `StoreTransactionsAction` saves transactions in the `transactions` table.
6. The document status updates to `processed`.

## Testing Procedure

### Manual frontend test

1. Login as an authenticated user with `create_documents` permission.
2. Visit `/documents/upload`.
3. Upload a PDF file and submit.
4. Confirm upload returns success and page reloads.
5. Visit `/transactions` and confirm extracted records appear.
6. Confirm negative cases: without permission, upload is blocked.

### Expected output

- Upload success returns HTTP 201.
- `transactions` table receives 3 sample records after processing.
- `/transactions` displays transactions in a table with date, description, amount, type, and reference.

## Tests included

- `tests/Feature/TransactionExtractionTest.php`

## Commands used

- `php artisan make:model Transaction -m`
- `php artisan make:policy TransactionPolicy --model=Transaction`
- `php artisan make:controller Transactions/TransactionController`
- `php artisan make:job ProcessDocumentJob`
- `php artisan make:action Documents/ExtractTransactionsAction`
- `php artisan make:action Transactions/StoreTransactionsAction`
- `php artisan migrate`
- `php artisan test --filter=TransactionExtractionTest`

## Actual test results

- `php artisan test --filter=TransactionExtractionTest` passed with 3 tests.
- `php artisan test --filter=DocumentUploadTest` passed with 2 tests.

## Troubleshooting

- If transactions do not appear, confirm `ProcessDocumentJob` ran and document status became `processed`.
- If extraction fails, inspect the queue worker logs for job exceptions.
- If route permission fails, confirm the user has `view_transactions` permission.

## Maintenance

- Replace `ExtractTransactionsAction` fake logic with a real parser or OCR integration.
- Add tenant-scoped search and filters on the transaction list.
- Add detailed failed transaction logging.

## Todo

- [ ] Add transaction filters by date, amount, and type.
- [ ] Add a transaction detail view.
- [ ] Add search index integration.
- [ ] Add audit log entries for extracted transactions.
