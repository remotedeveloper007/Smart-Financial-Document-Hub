# 08 - Transaction Extraction Verification Checklist

## Scope

- Transaction extraction engine
- Transaction persistence
- Transaction listing page
- Policy protection

## Completed

- [x] Created `transactions` database migration
- [x] Created `Transaction` model with tenant scope
- [x] Added fake extraction action
- [x] Added transaction storage action
- [x] Updated `ProcessDocumentJob` to extract and store transactions
- [x] Created transaction controller and frontend page
- [x] Added feature tests for extraction and storage
- [x] Added transaction permission constant and policy
- [x] Added documentation for backend and frontend

## Verification

1. Ensure XAMPP MySQL is running and `.env` points to:
   - `DB_CONNECTION=mysql`
   - `DB_DATABASE=smart_financial_document_hub`
   - `DB_USERNAME=root`
   - `DB_PASSWORD=`
2. Run `php artisan migrate`.
3. Confirm `transactions` table exists in MySQL.
4. Run `php artisan test --filter=TransactionExtractionTest` and confirm success.
5. Upload a document using `/documents/upload`.
6. Confirm document status reaches `processed` after queue execution.
7. Visit `/transactions` and confirm extracted rows appear.
8. Confirm `view_transactions` permission is required to access the page.

## Notes

- This feature currently uses a fake transaction extractor to seed data.
- Future work should replace the fake extractor with a real parser or OCR integration.
- The `transactions` table is tenant-scoped by `team_id`.
