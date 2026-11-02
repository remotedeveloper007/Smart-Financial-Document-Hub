# `tests/Feature/IntegrationChunkUploadTest.php`

## Purpose

Exercises integration CSV chunk creation, upload, assembly, checksum validation, and scan-result handling through authenticated HTTP routes.

## Dependencies

- Laravel feature test framework with `RefreshDatabase`.
- Integration, team, user, role, and permission factories/models.
- Fake local storage and queue.
- A `MalwareScanner` contract mock registered in the service container.

## Inputs and outputs

- Input: a synthetic CSV split into two base64 chunks and a checksum.
- Expected output: upload session and chunk endpoints return success; finalization verifies checksum and returns the assembled CSV only after an explicit clean scanner result.
- The scanner mock must be registered under `MalwareScanner::class`, because queued-job dependency injection resolves the interface.

## Usage

Run `php artisan test --filter=IntegrationChunkUploadTest` when changing chunk ordering, checksum validation, finalization, or scan integration.
