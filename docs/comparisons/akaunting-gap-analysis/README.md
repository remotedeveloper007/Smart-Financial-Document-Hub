# Akaunting Comparison and Gap Analysis

**Review date:** 2026-10-17  
**Compared repository:** [akaunting/akaunting](https://github.com/akaunting/akaunting)  
**Assessment scope:** repository-visible capabilities, current Hub code, tests, routes, and project constitution. This is a product and engineering comparison, not a security certification or an exhaustive line-by-line code review of Akaunting.

## Documents

- [Feature comparison and project verdict](01-feature-comparison.md)
- [Missing capabilities and priority backlog](02-missing-features-backlog.md)
- [Constitution-aligned implementation guide](03-implementation-guidelines.md)

## Executive Summary

**Akaunting is the more advanced accounting product today.** Its repository and public product documentation describe a broad accounting application: accounts, invoices, bills, contacts, banking transactions, transfers, reconciliations, taxes, reports, imports/exports, API routes, role-based permissions, localization, and an extension ecosystem. The comparison is based on the current upstream repository/documentation retrieved during this review; the exact hosted/cloud plan feature set may differ.

**Smart Financial Document Hub is a narrower document-ingestion and financial-data-processing foundation.** It has team tenancy, session authentication, role/policy building blocks, upload/queue paths, list pages for transactions/invoices/receipts/contracts, integration settings/import-export foundations, AI request queueing, audit/fault/queue/admin pages, and tests. Several extraction engines are deterministic samples rather than real extraction, and this Hub does not implement a complete accounting ledger or bookkeeping lifecycle.

These products are not direct substitutes. Akaunting is accounting-first; the Hub's potential differentiation is turning source documents into reviewed, reliable structured records and sending those records to existing accounting systems. The right approach is not to copy every Akaunting feature. Close the integrity/security/verification gaps first, then add the accounting primitives required by customer workflows.

## Evidence and Limits

Hub evidence was gathered from current source/routes/tests/docs, including `routes/web.php`, `app/Actions`, `app/Jobs`, `app/Models`, `database/migrations`, `resources/js/Pages`, and `tests/Feature`. Current verification run:

- `php artisan test`: 107 passed, 1 skipped, 0 failures on the configured MySQL test database.
- `npm run build`: passed.
- `npm run test:unit -- --run`: 2 passed.
- `composer audit --no-interaction`: no advisories.
- `npm audit --audit-level=high`: zero vulnerabilities.

These prove the available automated checks, not production behavior against live OCR, AI, Tally, QuickBooks, Xero, or Zoho services. The local configured database has several migrations still pending; no PostgreSQL validation, load/penetration test, backup restore drill, or production staging run is evidenced by this review.

Akaunting evidence includes its upstream README, first-party routes/migrations/models, and public help documentation. Its README describes an open-source accounting product built with Laravel and a modular app ecosystem. Public docs expose workflows for invoices, bills, banking/reconciliation, taxes, reports, double-entry accounting, users/roles, import/export, inventory, CRM, project management, and more. Some listed capabilities are paid/optional apps or plans and should not be assumed available in every Akaunting edition.

## How to Read Status

- **Present:** relevant code, route/UI, and at least some automated tests exist.
- **Partial:** a foundation exists but critical workflow, security, persistence, UI, or tests are incomplete.
- **Missing:** no first-party implementation was found in the inspected repository.
- **Unverified:** code exists, but no evidence proves real provider/extraction/business correctness.

A checkbox in an old feature doc is not proof of production readiness. Current route inventory, current source code, and fresh test output take precedence.
