# 13 - Invoice Processing

## Purpose

Add tenant-aware invoice extraction, persistence, and listing to the document processing pipeline.

## Test Results

- `php artisan test --filter=InvoiceFeatureTest` -> 3 tests, 3 passed, 5 assertions
- `php artisan test --filter=InvoicePolicyTest` -> 3 tests, 3 passed, 3 assertions
 
Recorded test run (local):

- `php artisan test --filter=InvoiceFeatureTest --no-ansi` -> PASSED: 3 tests, 3 passed, 5 assertions (duration ~40s)
- `php artisan test --filter=InvoicePolicyTest --no-ansi` -> PASSED: 3 tests, 3 passed, 3 assertions (duration ~38s)

## Notes

- Routes: `/invoices` (page), `/invoices/list` (API)
- Frontend: `resources/js/Pages/Invoices/Index.tsx`
- Controller: `app/Http/Controllers/Invoices/InvoiceController.php`
- Model: `app/Models/Invoice.php`
- Factory: `database/factories/InvoiceFactory.php`
- Policy: `app/Policies/InvoicePolicy.php`

## How to run tests locally

```bash
# Run only invoice-related feature tests
php artisan test --filter=InvoiceFeatureTest
php artisan test --filter=InvoicePolicyTest
```

## Verification Checklist

- [x] Model created and tenant-aware
- [x] Migration present (create_invoices_table)
- [x] Invoice extraction wired into `ProcessDocumentJob`
- [x] Store action implemented
- [x] Routes registered under `invoices.`
- [x] Frontend page created
- [x] Feature and policy tests added and passing
 - [x] Verification checklist finalized and documented (2026-08-24)

## Next steps / Todos

- [ ] Add invoice export and filtering UI
- [ ] Improve extraction from real OCR processor
- [ ] Add docs for frontend file line-by-line explanation

## Retry safety update

The sample extractor now derives `external_id` from the document ID, record type, and source record number. Re-running the same sample extraction for the same document therefore produces the same key, allowing the persistence action and database uniqueness constraint to update rather than duplicate the record. This deterministic sample key does not replace a stable source identifier from a future OCR/provider integration.
