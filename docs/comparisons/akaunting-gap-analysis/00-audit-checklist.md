# Constitution and Comparison Audit Checklist

Audit date: 2026-10-17

This checklist records the scope actually inspected and prevents the comparison from being mistaken for full live-provider or production certification.

## Source and Documentation Review

- [x] Read `docs/PROJECT-CONSTITUTION.md`.
- [x] Read `docs/COPILOT-QUICK-CONTEXT.md` and `docs/DEVELOPMENT-ROADMAP.md`.
- [x] Inspected current `routes/web.php` route inventory.
- [x] Inspected representative backend actions, jobs, models, policies, migrations, configs and frontend pages.
- [x] Inspected feature test inventory and Tally/integration/document test slices.
- [x] Inspected current feature docs and manuals; identified that `docs/manuals/manual-index.md` is stale about several routes now registered.
- [x] Compared public Akaunting README, public help topic index, and upstream repository excerpts for accounting domain modules.
- [x] Excluded vendor stubs and generated build output as product implementation evidence.

## Current Verification Evidence

- [x] `php artisan test` -> 107 passed, 1 skipped, 0 failures on configured MySQL test database.
- [x] `npm run build` -> passed.
- [x] `npm run test:unit -- --run` -> 2 passed.
- [x] `composer audit --no-interaction` -> no advisories.
- [x] `npm audit --audit-level=high` -> zero vulnerabilities.
- [ ] PostgreSQL full migration and test matrix executed.
- [ ] Live OCR/provider sandbox tests executed.
- [ ] Production staging, backup restore, load, and penetration tests executed.

## Interpretation Rules

- A route, page, model, or test file proves code exists, not that an entire product workflow is production-ready.
- Mock HTTP tests prove the local request contract, not compatibility or accounting accuracy against a real provider.
- Sample extractor output is not evidence of OCR accuracy.
- Akaunting optional apps/cloud plan features are marked optional/edition-dependent, not assumed to exist in the open-source core.
- Priorities in `02-missing-features-backlog.md` are recommendations; implementation still requires following `docs/DEVELOPMENT-ROADMAP.md` and approval one feature at a time.
