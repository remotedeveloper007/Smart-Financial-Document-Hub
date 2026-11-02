# Missing Features and Prioritized Backlog

This backlog compares current Hub implementation evidence with the constitution and Akaunting's repository/public product documentation. `P0` means release/security/integrity gate. `P1` is required for a credible document-intelligence product. `P2` is accounting capability to add only after product validation. `Optional` means likely unsuitable for the first Hub release unless customers ask for it.

## P0: Trust, Tenant Safety, and Financial Correctness

| ID | Gap | Current evidence | Outcome / acceptance criteria |
|---|---|---|---|
| P0-01 | Replace fake document extraction | `ExtractTransactionsAction`, `ExtractInvoicesAction`, `ExtractReceiptsAction`, `ExtractContractsAction` generate fixed examples | Real parsers/OCR behind an extraction interface; synthetic fixtures and representative consented samples; field-level confidence/provenance; malformed/unsupported documents fail visibly; 95%+ field-level target is measured on an explicitly defined corpus before marketing accuracy claims |
| P0-02 | Human review and correction | Structured rows are primarily read-only; no full review/correction lifecycle | Reviewer can inspect source beside extracted fields, correct/reject values, record actor/time/old/new values, and audit each correction; only reviewed/approved data can be exported when configured |
| P0-03 | Secure upload and scan ordering | `UploadDocumentAction` writes to public disk and dispatches processing; scan job is not proven to gate the document pipeline | Private object storage by team prefix; size/type/content validation; upload -> quarantine -> antivirus scan -> processing; scanner errors fail closed; download uses authorization-controlled temporary URLs; tests prove malicious/scan-error files never reach extractors |
| P0-04 | Retry-safe extraction and remote writes | Local fake extractors now have deterministic sample keys; remote Tally must honor idempotency header | Database uniqueness keys include tenant + source/document + external identity; adapter records remote idempotency outcome; retry-after-timeout test proves no duplicates or double posting |
| P0-05 | Complete tenant isolation proof | Global scope/team context exist but do not prove all exports/storage/queue/provider operations | Two-tenant tests for every list/detail/update/delete/export/search/download/import/job/cache path; tenant cannot supply another team ID; storage, cache and idempotency keys carry tenant identity |
| P0-06 | Encryption migration/deploy safety | Integration secrets use encrypted cast and a plaintext-to-encrypted migration | Back up and restore before migration; retain APP_KEY; migration test covers legacy values; rollback tested on a disposable database; key rotation/recovery procedure documented |
| P0-07 | Production-grade Tally/connector validation | Tally is a generic REST adapter; no live sandbox test | Contract test against vendor sandbox (or certified adapter) proves auth, pagination, field semantics, imports, exports, rate limiting, errors, idempotency, reconciliation and token lifecycle. Do not call it native TallyPrime until XML/voucher protocol is implemented and tested |
| P0-08 | Migration and database portability gate | MySQL test suite is available; PostgreSQL execution is not evidenced; historical migrations have mixed styles | CI creates fresh MySQL and PostgreSQL databases, migrates, rolls back/re-migrates, seeds and runs core tests; no engine-specific SQL without documented conditional support |
| P0-09 | Reliable fault capture | `CaptureFaults` contains duplicate persistence and empty diagnostic fields; exceptions from non-HTTP jobs are not fully addressed | One fault event per failure with correlation ID, severity, module, class/method/file/line, tenant/user/document where safe, root cause/recommended fix workflow; secrets and source documents redacted; queue/CLI failures captured too |
| P0-10 | Accurate authorization matrix | Policies/permissions exist; role-to-feature capability coverage needs consistent tests | Document matrix for every role and operation; tests cover allowed/denied/cross-team for both UI and JSON endpoints; no UI hiding substituted for server authorization |

## P1: Minimum Viable Document Intelligence Product

| ID | Gap | Why it matters | Acceptance criteria |
|---|---|---|---|
| P1-01 | Document status/detail page | Uploaders need to know processing state and trace output to source | Private document list/detail with status timeline, original filename, checksum, page count, extractor/version, errors, and authorized download; no public file URL |
| P1-02 | Bank statement import domain | The Hub's target value includes bank statement processing | Support documented bank formats; normalize date, currency, amount, debit/credit, balance, account, reference; reconcile parsed row count and opening/closing balances; review UI and export tests |
| P1-03 | Extraction provenance and confidence | Financial data must be trusted and explainable | Each field retains source page/region or parser evidence, confidence, parser version, corrections and approval state |
| P1-04 | Versioned import/export schema | External accounting integrations must not silently break customers | Version payloads (`/api/v1` for public API; versioned internal contract for integrations), validate schema, keep backward-compatibility tests, document deprecation windows |
| P1-05 | Durable job workflow state | `202` is not enough for users to understand progress | Store tenant-bound processing/import/export run IDs, status, attempts, counts, timestamps, safe error summary, and correlation IDs; frontend polls/presents terminal states |
| P1-06 | Bulk import guardrails | Large CSVs need accuracy and resource limits | Max file size/rows/columns, encoding rules, duplicate detection, field-level errors, preview sampling, resumability, checksum, scanner result, cancellation, queue backpressure |
| P1-07 | Per-tenant usage metering | Required by constitution and needed for billing/cost controls | Idempotent usage ledger for uploads, storage, OCR pages, imports/exports and AI; team dimension, period, unit, source event; tests against duplicate jobs |
| P1-08 | Per-tenant cost and quota controls | Prevent abuse and surprise provider costs | Estimate and meter provider/OCR/storage costs; enforce plan limits before dispatch; tenant dashboards/admin reports; alert on budget/abuse thresholds |
| P1-09 | Financial search quality | Search is currently SQL filtering, not Scout/Meilisearch-scale search | Tenant-filtered indexed search; authorization, index sync/retry, deletion propagation, migration/reindex and cross-tenant tests; measure query/index latency |
| P1-10 | Export formats and safe delivery | MVP standards include CSV/XLSX and potentially JSON; current transaction export is CSV | Asynchronous large exports; CSV formula-injection protection; schema/version docs; temporary authorized download URLs; retention/cleanup; audit record; tenant isolation tests |
| P1-11 | Audit log integrity and coverage | Sensitive activity must be traceable without logs blocking primary work | Audit upload/download/export/import/search/corrections/billing/admin actions; avoid duplicate writes; define best-effort vs durable events; retention and tamper-access policy |
| P1-12 | API keys and external API authentication | Constitution names versioned APIs; current primary app is session/web route oriented | Add `/api/v1`, scoped/revocable keys or Sanctum, rate limits, tenant binding, validation, OpenAPI docs, token redaction and API-specific tests |
| P1-13 | Live frontend verification | Build and two unit tests do not prove user experience | Browser E2E for auth, upload, progress, processing, review, mapping, import/export, mobile layout, empty/error/permission states, and accessibility |

## P2: Accounting Core (Only If Product Direction Requires Competing With Accounting Suites)

| ID | Capability | Minimum viable domain design before UI |
|---|---|---|
| P2-01 | Chart of accounts | Tenant-scoped account codes/types, active/archived state, normal balance, currency policy, immutable references once posted |
| P2-02 | Double-entry journals and general ledger | Journal header/lines, debit=credit invariant, posting lifecycle, reversal not destructive edit, period locking, audit trail, trial balance and ledgers |
| P2-03 | Customers and vendors | Tenant-scoped contacts, billing/shipping addresses, tax IDs, credit/payment terms, privacy/retention rules, role-limited customer portal separation |
| P2-04 | Invoices / estimates / credit notes | Number sequences, line items, tax calculations, currency/rates, totals, delivery, status lifecycle, payments/credits, overdue/recurring schedules |
| P2-05 | Bills / payables | Vendor bills, line/tax validation, approvals, payment allocation, recurring bills, duplicate detection and due-date workflows |
| P2-06 | Banking accounts, transfers, reconciliation | Opening/current balances, statement import, matching rules, transfer double entries, reconciliation sessions, unmatched differences and immutable completion audit |
| P2-07 | Taxes and accounting periods | Jurisdiction-aware rates and inclusive/exclusive calculations, tax reports, fiscal calendar, locked periods and explicit effective dates |
| P2-08 | Multi-currency | Currency metadata, exchange-rate source/time, transaction/document functional vs foreign amounts, rounding policy, revaluation gains/losses and tests |
| P2-09 | Financial reports | Cash/accrual basis definitions, ledger-backed totals, filters, permission checks, reproducible exports and reconciliation to journal totals |
| P2-10 | Recurring accounting workflows | Schedule state machine, idempotent occurrence generation, pause/end/retry, notification and period-boundary tests |
| Optional | Inventory, CRM, projects, estimates, payroll, helpdesk, app marketplace | Only implement after customer research; these are not required to deliver a reliable financial document intelligence platform and would greatly expand compliance/support scope |

## P3: Operational and Ecosystem Maturity

- General GitHub Actions workflow for dependency install, lint/static analysis, MySQL/PostgreSQL migration matrix, backend/frontend tests, build, and dependency audits.
- Production Docker/Compose or documented deployment artifact; separate web, queue, scheduler, database, Redis, scanner and object storage responsibilities.
- Horizon/Pulse/Pennant packages and configuration if those constitution requirements remain mandatory; otherwise amend the constitution explicitly and document chosen alternatives.
- Database/object-storage backups, encryption, retention, restore drills, rollback plan, key rotation and incident response.
- Structured logs/traces/metrics with request/job/import correlation IDs, tenant-safe labels, PII/secrets redaction, alert thresholds, and retention.
- Load/soak testing for upload, parsing, indexing, exports, queue backlog and database connection limits.
- Security review/penetration test, SSRF protection for user-provided integration URLs, webhook signatures, outbound network policy, file scanner fail-closed behavior, rate limits and abuse quotas.
- Accessibility, localization, data export/delete workflows, privacy/retention policy, and support runbooks.

## Prioritization Rule

Do not begin P2 accounting features while P0 extraction correctness, private upload/scanning, cross-tenant tests, or provider idempotency are unproven. Do not implement optional Akaunting modules simply to match a comparison table. Every item needs an owner, measurable acceptance criteria, tests, docs, and explicit approval at the end of its feature milestone.
