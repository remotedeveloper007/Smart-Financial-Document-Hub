# `tests/Feature/DocumentScanPipelineTest.php`

## Purpose

Checks that upload processing fails closed when scanning is disabled or fails, that clean and infected results have the correct outcomes, and that simulated extraction cannot create production financial records.

## Dependencies

- Laravel feature test framework and `RefreshDatabase`.
- Fake local storage and queue.
- Mockery mocks of the `MalwareScanner` contract.
- Document and team factories.

## Inputs, outputs, and expected results

- Synthetic stored file and tenant-owned document.
- Clean scanner result: document becomes queued and extraction is dispatched with matching IDs.
- Infected result: document becomes quarantined; no extraction dispatch.
- Disabled/throwing scanner: scan fails closed; no extraction dispatch.
- Production environment with simulated driver: document fails and no transaction is persisted.

## Usage

Run `php artisan test --filter=DocumentScanPipelineTest` after editing scanner or extraction state transitions.
