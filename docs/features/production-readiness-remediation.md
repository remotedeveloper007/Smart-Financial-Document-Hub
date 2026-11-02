# Production Readiness Remediation

## Purpose

Record the release-blocker fixes made after the repository-wide implementation audit. This is an engineering remediation note, not a declaration that the entire SaaS is production-ready.

## Changes

- Registered routes for document upload/list, team member management, transaction listing/export, search, and audit log pages/endpoints.
- Registration now delegates to `RegisterUserAction`, which creates the user, primary team, membership, default roles, and owner assignment in one database transaction.
- Added missing view permissions to the default role matrix for invoices, receipts, contracts, and transactions.
- Added a `view_audit_logs` permission and policy. Audit list/page routes now authorize before reading tenant records.
- Queue status endpoints are restricted to admin users and queue navigation links are only displayed to admin users.
- Integration credentials use Laravel encrypted-array casting and are hidden from JSON serialization. A migration converts old plaintext JSON to encrypted values; preserve `APP_KEY` and take a verified backup before production deployment.
- Sample extraction keys for transactions, invoices, receipts, and contracts are deterministic per document/source record so retries target stable identifiers.
- Tenant/date index migration now uses Laravel schema operations; a duplicate audit-log index was removed because the table migration already owns it.
- Vite now builds the TSX app referenced from the Blade root, and legacy layout imports delegate to the typed implementation.
- Fixed TypeScript errors and aligned the React Vite plugin with Vite 8. Migrated the frontend build from Tailwind 3 to Tailwind 4 and upgraded Vitest to Vite-8-compatible Vitest 5.
- Updated Composer dependencies within Laravel 13 and npm dependencies without forced dependency resolution.

## Important Deployment Notes

1. Back up the database and test restoration before deploying.
2. Preserve `APP_KEY`; encrypted integration credentials become unreadable if the key changes.
3. Review whether any external backup/reporting system expects plaintext `integrations.credentials`; the new values are Laravel-encrypted ciphertext.
4. Deploy code and migrations in the team's approved sequence. Do not run `migrate:rollback` against production as a test.
5. Confirm actual production storage, queue, virus scanner, OAuth callbacks, AI provider, mail, backup, monitoring, TLS, and secret-management configuration independently.

## Verification Recorded During Remediation

- Route-dependent tests: 14 passed, 36 assertions.
- Registration and document policy tests: 5 passed, 7 assertions.
- Integration feature/policy/OAuth tests: 13 passed, 54 assertions before the Composer dependency refresh.
- Extraction feature tests: 13 passed, 22 assertions.
- Audit/queue/financial-read tests: 18 passed, 33 assertions.
- Chunk upload/resume tests: 2 passed, 11 assertions.
- Frontend build: passed after TypeScript/layout/entry/toolchain fixes.
- Frontend Vitest: 2 tests passed after upgrading to Vitest 5.
- Full backend command: `php artisan test` -> 107 passed, 1 skipped, 0 failures (MySQL test database).
- Final frontend command: `npm run build` -> passed.
- Final frontend tests: `npm run test:unit -- --run` -> 2 tests passed.
- Composer command: `composer audit --no-interaction` -> no advisories after dependency update to Laravel Framework 13.37.0 and patched compatible dependencies.
- npm command: `npm audit --audit-level=high` -> 0 vulnerabilities.

## Remaining Release Gates

- Run the full PHP suite on the supported MySQL version and add a PostgreSQL CI job to validate portability; the index migration was corrected, but a full PostgreSQL run has not been performed here.
- Review every database migration for idempotence, duplicate indexes, destructive changes, and MySQL/PostgreSQL support.
- Replace fake transaction/invoice/receipt/contract extractors with a real validated extraction service before using results for business decisions. Sample values are not production financial data.
- Add usage metering and per-tenant cost controls for OCR, AI, storage, and exports.
- Review queue worker separation, backoff/timeouts, rate limits, scan failure behavior, and Horizon deployment settings.
- Replace heuristic-only virus scanning fallback with an explicit fail-closed production scanner policy and security tests.
- Verify integrations against sandbox providers; add refresh-token lifecycle, scopes, revocation, and safe error handling.
- Add monitoring/alerting, tested backup restoration, deploy rollback, CI, and deployment automation.
- Perform security review, privacy/retention review, penetration testing, load testing, and manual UI QA on staging.

Production readiness is not achieved until all release gates are completed in the target deployment environment.

## Reverification After the Follow-up Fixes

The following local checks supersede the older test counts above:

- `php artisan test` — 128 tests, 127 passed, 1 skipped, 332 assertions. The one skip is the ClamAV integration test because the scanner service is not available locally.
- `npm.cmd run test:unit -- --run` — 3 test files and 4 tests passed.
- `npm.cmd run build` — TypeScript type-check and production frontend build passed.
- `php artisan route:list --except-vendor` — 80 application routes registered.
- `composer audit --no-interaction` — no advisories; Symfony Mime is patched at 7.4.22.
- `npm.cmd audit --audit-level=high` — zero vulnerabilities after compatible dependency updates.
- `vendor\bin\pint --test database\seeders\DatabaseSeeder.php tests\Feature\DatabaseSeederSafetyTest.php tests\Feature\AiFeatureTest.php tests\Feature\IntegrationExportsTest.php` — passed.
- `php artisan db:seed --class=AiDemoSeeder --force` — synthetic AI records seeded into the local MySQL database.

Follow-up fixes recorded:

- Added Laravel's CSRF meta token and a tested `csrfFetch` helper. Direct mutating fetch requests for AI, documents, team removal, billing, integrations, imports, and chunk uploads now use the helper.
- Restricted the default demo-account seeder to `local` and `testing` to prevent known-password demo users from being created in production.
- Corrected the integration-export test fixture to match the real job-generated filename shape; application export filtering was already expecting that shape.

### Current release decision

**Not production-ready.** The repo still contains deterministic fake transaction, invoice, receipt, and contract extractors; the AI assistant only simulates responses and has no provider, financial-record retrieval, usage/cost metering, or audit trail. The production malware-scanner failure policy, live OAuth/provider sandbox behavior, PostgreSQL compatibility, actual production PHP version, browser E2E, backups/restoration, deployment rollback, and monitoring/alerting remain unverified. Local passing tests and clean dependency audits do not close these launch gates.

The project constitution calls for PHP 8.4+ and `composer.json` now requires `^8.4`. This workstation still runs PHP 8.3.14, so local PHP test results are useful diagnostics but do not prove supported-runtime compatibility. The new PHP 8.4 CI run must pass before launch.

## Latest verification after private document scanning changes

- `php artisan test` — 133 passed, 1 skipped, 354 assertions. The skipped test requires a live ClamAV daemon.
- `php artisan test --filter="IntegrationChunkUploadTest|DocumentScanPipelineTest"` — 7 passed, 27 assertions.
- `npm.cmd run test:unit -- --run` — 3 files and 4 tests passed; Vitest emitted worker termination timeout warnings after completion.
- `npm.cmd run build` — TypeScript check and Vite production build passed.
- `vendor\bin\pint --test app\Jobs\ScanUploadedFileJob.php tests\Feature\DocumentScanPipelineTest.php tests\Feature\IntegrationChunkUploadTest.php` — passed.
- `composer audit --no-interaction` — no advisories.
- `npm.cmd audit --audit-level=high` — zero vulnerabilities.
- `php artisan route:list --path=documents` and `php artisan route:list --path=upload-scan-status` — document and chunk scan-status routes registered.

The scanner job now rejects any result other than the explicit `clean` and `infected` values. Regression coverage confirms an unknown result marks the document scan-failed and does not dispatch extraction. These checks do not replace live ClamAV, staging UI, queue, database-portability, backup/restore, or deployment validation. The release decision remains **not production-ready** until the documented external release gates are closed.

## Verification and changes on 2026-11-02

- `php artisan test` - 137 passed, 1 skipped, 391 assertions. The PHPUnit configuration selects MySQL. The skipped test requires a live ClamAV daemon.
- Focused AI/upload/import regressions - 15 passed, 70 assertions.
- `npm.cmd run test:unit -- --run` - 3 files, 4 tests passed when run in isolation.
- `npm.cmd run build` - TypeScript and Vite build passed; test files are excluded from generated production chunks.
- `vendor\bin\pint --test ...` - changed PHP files passed formatting.
- `composer validate --strict --no-interaction` - valid; `composer audit --no-interaction` - no advisories.
- `npm.cmd audit --audit-level=high` - zero high-severity advisories.
- `php artisan route:list --except-vendor` - 81 routes registered.
- Added `.github/workflows/ci.yml` for PHP 8.4 with MySQL 8.4 and PostgreSQL 16 plus frontend checks and dependency audits. The workflow has not yet executed on GitHub; PostgreSQL compatibility remains unverified.

Follow-up changes:

- `composer.json` now requires PHP `^8.4`; this local machine is PHP 8.3.14, so its successful PHP tests are diagnostic, not proof on a supported runtime.
- Browser JSON requests to web routes now receive JSON validation/error responses when they send an `Accept: application/json` header.
- Chunked upload session ownership, filename, checksum, size, and assembled-file integrity are checked. File-based Tally imports require a clean matching ClamAV sidecar both before enqueue and in the queue job.
- The simulator is restricted to local/testing. Production AI requests require a team-configured supported provider and key; OpenAI settings are now available on the AI page.
- Developer/tester/customer manuals now list registered routes and explicitly state unfinished functionality.

**Release verdict remains not production-ready:** fake document extraction, no AI financial-data retrieval, no payment-provider billing, absent usage metering, missing Horizon/Pulse/Pennant/Scout/search service dependencies, no production Docker stack, and unverified external services/operations are not resolved by these local tests.

## Team AI provider milestone

The AI assistant now supports a team-selected OpenAI provider behind an `AiProvider` contract, plus a simulator restricted to local/testing. Team API keys are encrypted at rest, never sent to the frontend or queue payload, and provider/model choices are snapshotted for requests. This does not add financial-record retrieval, usage/cost limits, privacy/retention controls, platform billing, or external staging verification. The full backend suite reports 140 passed, 1 skipped, and 422 assertions. The release verdict remains **not production-ready**.