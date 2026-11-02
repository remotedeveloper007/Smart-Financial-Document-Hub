# 14 - Receipts Processing

## Purpose

Add tenant-aware receipt extraction, persistence, and listing to the document processing pipeline.

## Test Results

(Will be recorded after running tests)

Recorded test run (local):

- `php artisan test --filter=ReceiptFeatureTest --no-ansi` -> PASSED: 3 tests, 3 passed, 5 assertions (duration ~58s)
- `php artisan test --filter=ReceiptPolicyTest --no-ansi` -> PASSED: 3 tests, 3 passed, 3 assertions (duration ~62s)

## Notes

- Routes: `/receipts` (page), `/receipts/list` (API)
- Frontend: `resources/js/Pages/Receipts/Index.tsx`
- Controller: `app/Http/Controllers/Receipts/ReceiptController.php`
- Model: `app/Models/Receipt.php`
- Factory: `database/factories/ReceiptFactory.php`
- Actions: `app/Actions/Documents/ExtractReceiptsAction.php`, `app/Actions/Documents/StoreReceiptsAction.php`

## How to run tests locally

```bash
php artisan test --filter=ReceiptFeatureTest
php artisan test --filter=ReceiptPolicyTest
```

## Verification Checklist

- [ ] Model created and tenant-aware
- [ ] Migration present (create_receipts_table)
- [ ] Receipt extraction wired into `ProcessDocumentJob`
- [ ] Store action implemented
- [ ] Routes registered under `receipts.`
- [ ] Frontend page created
- [ ] Feature and policy tests added and passing
 - [x] Model created and tenant-aware
 - [x] Migration present (create_receipts_table)
 - [x] Receipt extraction wired into `ProcessDocumentJob`
 - [x] Store action implemented
 - [x] Routes registered under `receipts.`
 - [x] Frontend page created
 - [x] Feature and policy tests added and passing

## Retry safety update

The sample receipt extractor uses a stable per-document receipt key instead of a fresh random UUID. Reprocessing a document can therefore address the same receipt row rather than creating a duplicate. Replace this synthetic key with the extraction provider's stable source identifier when real extraction is connected.

---

Stop after review.
