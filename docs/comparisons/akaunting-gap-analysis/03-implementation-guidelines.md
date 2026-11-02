# Constitution-Aligned Feature Implementation Guide

This guide operationalizes `docs/PROJECT-CONSTITUTION.md`, `docs/COPILOT-QUICK-CONTEXT.md`, `docs/SESSION-START-PROMPT.md`, and the current Laravel/Inertia architecture. It is a reusable delivery standard for each item in [the gap backlog](02-missing-features-backlog.md). Build one feature at a time and stop for approval after its evidence and docs are complete.

## 0. Define the Product Boundary First

The current strategic choice is whether this product is:

1. **Document intelligence platform (recommended):** securely ingest documents, extract/normalize/review financial facts, then export/sync to accounting software. It does not recreate an entire general ledger.
2. **Full accounting suite:** also own chart of accounts, journal posting, invoices/bills, payments, reconciliation, taxes, periods, statements, and accountant workflows.

Write the choice into `docs/PRODUCT-VISION.md` and the roadmap. Do not add double-entry accounting merely because Akaunting has it; it is a separate, regulated domain with a large correctness and support cost.

## 1. Feature Discovery and Definition

Before editing code:

1. Read the Constitution, architecture, database/security/testing/deployment standards, canonical roadmap, and nearby feature docs.
2. Inspect the current route, controller, model, migration, frontend page, policy, jobs, tests, and git status. Respect existing local edits.
3. Write a one-paragraph problem statement and a definition of done with measurable inputs, outputs, error states, permission rules, tenant boundary, and operational impact.
4. Confirm the feature is next in the approved roadmap. Ask for clarification only if product scope cannot be resolved from the canonical docs.
5. Define non-goals, especially whether the feature is a real provider/OCR integration or only a mock/sandbox contract.
6. Identify data classification, retention, cost, regulatory, and support implications before handling customer financial data.

Output before coding: a compact feature checklist and a falsifiable local hypothesis with the smallest useful test.

## 2. Financial Domain and Schema Design

For each persisted business concept:

1. Use the existing `team_id` convention unless a separately approved tenant-key migration changes the repository as a whole. Do not add an inconsistent `tenant_id` column to one table.
2. Define ownership, lifecycle/state machine, relationships, money precision, currency, timezone, source/provenance, who changed it, and deletion/retention behavior.
3. Store money as fixed-precision decimal or integer minor units, never binary floating point. Specify rounding and currency scale explicitly.
4. Keep source documents and provider payloads out of relational rows where possible; store private object metadata and encrypted/sanitized payload references.
5. Add foreign keys, tenant-scoped unique constraints, and indexes for actual query predicates/orderings. Avoid blind duplicate indexes.
6. Make every migration reversible. Use Laravel Schema Builder for PostgreSQL/MySQL compatibility. Test fresh install, upgrade path, and rollback on disposable databases for both engines when both are promised.
7. For encrypted fields, define how old plaintext data migrates, how `APP_KEY` is preserved/rotated, what is hidden from serialization, and how backups are restored.
8. Create factories/seeders with unmistakably synthetic data. Never seed customer production data or live secrets.

## 3. Tenancy and Authorization Boundary

Every request, job, import, export, search result, cache entry, object path and audit record must carry tenant/team identity.

- Resolve team context at request and job boundaries; clear context in long-lived workers before setting a new team.
- Authorize the action on the server using policies/gates. Hiding a button/link is only presentation, not authorization.
- Bind record lookups to the current team and return 404/403 without disclosing whether another team's record exists.
- Prove access for an allowed role, a denied role, unauthenticated access, and Tenant A attempting Tenant B IDs/data.
- Tenant-scope cache keys and index/search filters. Test exports, downloads, generated files, callbacks and queued jobs, not just list endpoints.
- Do not accept client-supplied `team_id` as proof of access.

Minimum tenant test design: create Team A and Team B, distinct users, records, files, exports, and jobs; verify every public read/write path is isolated for both teams.

## 4. Application Architecture and SOLID Responsibilities

Use the existing modular-monolith patterns:

1. **Form Request / validator:** validates untrusted input and rejects unsupported fields/sizes/formats.
2. **Policy:** decides whether the user may perform the operation on that team/resource.
3. **Thin Controller:** orchestrates authorization, validation, Action invocation and HTTP response only.
4. **Action / domain service:** performs one cohesive use case; keeps business rules testable and deterministic.
5. **DTO/value object:** use when an external/import payload has a meaningful stable schema; keep currency/date/amount types explicit.
6. **Job:** handles slow or retryable I/O such as OCR, provider imports, notifications and large exports.
7. **Provider adapter:** translates internal DTOs into one external protocol; normalize provider errors and never leak secrets.
8. **Events/listeners:** use when multiple independent reactions are required. Do not create event layers for a single trivial operation.
9. **Read model/resource:** return only fields the caller is allowed to see. Never serialize credentials, internal stack traces or unreviewed financial data.

Apply SOLID pragmatically: each class has one reason to change; external providers are behind interfaces/strategies; additions extend a contract rather than changing unrelated domains; depend on injected abstractions where there is a real seam. Avoid introducing repository classes or microservices without measurable benefit.

## 5. Queue, Idempotency, and Concurrency

For each queue-backed feature:

1. Persist a run/import/export record before dispatch so the UI can show durable status.
2. Dispatch only stable identifiers (record/team/run IDs), not raw credentials or large mutable models.
3. Reload under the correct team context in the job.
4. Define a deterministic idempotency key from tenant + source ID/checksum + operation/schema version. Add a unique database constraint where locally applicable.
5. For remote writes, send a stable remote idempotency key and document whether the provider honors it. If not, implement a lookup/reconciliation strategy or label duplicate risk.
6. Define attempts, timeout, backoff, rate limit, queue name, failure status, user-visible error, and retry policy. Never swallow a failure and mark success.
7. Protect against concurrent duplicate submits with unique constraints, cache/DB locks, state transitions, or provider idempotency. Define lock lease and crash recovery behavior.
8. Store queue payloads without secrets and avoid logging raw financial content. Add correlation IDs.
9. Test success, malformed input, timeout, 4xx/5xx, duplicate dispatch, retry, worker restart, cancellation, and two concurrent submissions.

## 6. Document Intake and Extraction Workflow

Required order:

```text
validate request -> private tenant quarantine storage -> durable upload record
-> queued scan -> clean result -> queued extraction -> normalize/validate
-> persist idempotently -> audit -> review/correction -> search/report/export
```

- Private storage only; never publish financial statements through a public disk URL.
- Check extension, MIME, content signature, size, archive/decompression bounds, page count and malicious content.
- Fail closed when required scanning is unavailable; never use a regex/script heuristic as a malware scanner in production.
- Preserve source file checksum and provenance, extractor/provider/model version, page/source evidence, confidence and run IDs.
- Extraction produces suggestions, not authoritative ledger entries. Provide human review/correction and a visible error state.
- Validate accounting invariants: date ranges, currencies, debit/credit signs, decimal scale, totals, duplicates, statement opening/closing balances and row counts.
- Build representative, legally obtained or synthetic test fixtures. Measure precision/recall per field and document the dataset and known failure modes.
- Do not report “accurate extraction” without a measured test set and a threshold agreed with product/accounting stakeholders.

## 7. Integration and API Design

For each provider:

1. Create a provider capability table: protocol/version, auth, sandbox, rate limits, pagination, data types, idempotency, revocation, refresh behavior and supported operations.
2. Do not label a generic REST shim as native integration. Name the adapter according to the actual protocol.
3. Validate configured URLs (HTTP(S), host allowlist where possible, no embedded credentials), defend against SSRF/private-address access, enforce timeouts and outbound egress rules.
4. Encrypt secrets at rest and exclude them from JSON, logs, exception messages, job payloads and telemetry.
5. Apply authentication/scopes per integration and team. Validate every callback state/nonce, redirect URI and tenant ownership.
6. Normalize date, money, currency, tax, account, contact and document IDs into a versioned internal DTO.
7. Use pagination, request budgets, bounded retries/backoff, rate limits, checkpoints and resumable imports.
8. Build sandbox contract tests for accepted/rejected mappings, duplicate requests, partial failures, stale tokens, rate limits and reconciliation.
9. Provide a mapping preview and display exactly what fields will be sent. Require explicit confirmation for financial writes.
10. Add operator controls to pause/revoke a provider and rotate credentials without deleting audit history.

## 8. Frontend and UX Completion

For each UI feature:

- Register a named authenticated route and verify its Inertia component path resolves from the production Vite build.
- Provide navigation only to users who can use the page, but enforce policy again in controller/API.
- Implement loading, empty, validation, permission-denied, server-error, offline, retry and success states.
- Use React/TypeScript types matching server response contracts; never use `any` for untrusted API shapes when feasible.
- For financial values, preserve precision and make currency/date context visible; show source/review state.
- For import mapping, preview representative rows and errors, define required target fields, reject ambiguous mappings, and allow a safe cancel before dispatch.
- Test keyboard access, labels, focus, responsive viewport, long values, table overflow, network failures and duplicate-click behavior.
- Add component/unit tests and browser E2E for critical workflows. A successful TypeScript build is necessary but not sufficient.

## 9. Accounting Scope Rules

If adding accounting features, use separate milestones and domain review:

1. **Chart of accounts:** codes/types, parent/subaccount rules, tenant uniqueness, defaults and archival.
2. **Journal entry model:** entry header and debit/credit lines; totals must balance exactly in the transaction currency; posted entries are immutable and corrected by reversal.
3. **Ledger/reporting:** reports derive from posted journal lines; report totals reconcile to trial balance; explicitly test cash/accrual basis.
4. **Invoices/bills:** versioned lifecycle and status transitions; totals recomputed server-side; tax/discount/rounding rules; payment allocations and partial payments.
5. **Banking/reconciliation:** imported statement identity, matching workflow, opening/closing balance, difference, locked completion and audit trail.
6. **Taxes/multi-currency:** jurisdiction, effective dates, inclusive/exclusive calculation, FX source/rate timestamp, rounding and realized/unrealized gain handling.
7. Every domain feature has accounting SME review and immutable audit evidence before external customer use.

Do not begin these features until secure document extraction and customer demand are demonstrated, unless product direction is formally changed to a full accounting system.

## 10. Test Strategy and Evidence

Required layers for a financial feature:

- Unit tests for normalization, mapping, amount/date rules and parser edge cases.
- Feature tests for routes, validation, authorization and API responses.
- Integration tests with MySQL and PostgreSQL for migrations, constraints and transactions.
- Queue tests for dispatch, tenant context, idempotency, failure/retry and status lifecycle.
- Tenant tests for every data/file/cache/export/provider boundary.
- Frontend component tests for mapping, validation, loading/error and cancellation.
- Browser E2E for the real end-to-end workflow on a built app.
- Provider sandbox tests for live API semantics; mocks do not prove provider compatibility.
- Coverage targets from `docs/TESTING-STANDARDS.md`, with critical financial paths measured separately.

Record exact command, database engine/version, environment, test count/assertions, skips and failures in the feature docs. Never paste anticipated results or stale previous-run results as current evidence.

## 11. Performance, Observability, and Operations

- Baseline DB query count/latency, queue wait/run time, upload size, parse throughput, memory, storage growth and provider call rate.
- Paginate lists, stream/chunk large file operations, use indexes justified by query plans, and test query plans with realistic tenant volume.
- Avoid N+1 queries; eager-load needed relations.
- Bound network timeouts and worker memory; separate queues by workload before increasing concurrency.
- Track metrics by feature and tenant without logging sensitive values. Use correlation IDs across request, job, provider request, audit event and export.
- Alert on queue backlog, retries, failures, extraction quality drops, storage limits, provider rate limits and metered-cost thresholds.
- Test backup restore and deploy rollback. A backup not restored in a drill is not a verified recovery plan.
- Keep production debugging disabled, secrets in deployment secret management, TLS on all endpoints, and dependency audits in CI.

## 12. Documentation and Feature Exit Gate

Each feature adds or updates:

1. `docs/features/{feature}.md`: purpose, scope/non-scope, architecture, schema/relationships, routes, permissions, API/request contracts, failure/retry behavior, retention and maintenance.
2. A separate `*-frontend.md` when a React UI is added, including inputs, expected rendered/network results, error states, accessibility checks and component flow.
3. A tester guide with exact URLs, role, synthetic input, expected status/data, negative tests and browser DevTools evidence.
4. `*-checklist.md`: implementation and migration acceptance criteria, all checked only against real evidence.
5. Directory/file guide entries for new source files, explaining role, inputs/outputs and dependencies in plain English.
6. Actual verification logs and unresolved limitations.
7. Customer guidance only when customer-visible behavior changes.

Feature completion requires all code/tests/docs/review gates. Then stop and wait for approval before the next roadmap feature.

## 13. Migration, Rollout, and Compatibility

1. Build an additive/backward-compatible migration where feasible.
2. Test on a disposable copy of the currently supported MySQL and PostgreSQL versions.
3. Run `php artisan migrate:status`, fresh migration tests, rollback tests and restore rehearsal.
4. For encrypted-field changes, preserve APP_KEY; deploy migration and compatible application code in a reviewed order.
5. Deploy behind a feature flag when behavior is unfinished or risky; define flag owner and removal condition.
6. Deploy built frontend assets and code, run migrations, restart workers, execute health checks/smoke tests, monitor, and preserve rollback procedure.
7. Never report production-ready until staging and operational evidence exist.

## 14. Change History Requirement

When behavior must change, preserve rationale through version control, focused commits, migration history, and a concise decision/change note. Do not retain executable old implementation as commented-out code in source files: commented duplicate code becomes stale, may leak secrets, and confuses maintenance. If an explicit repository instruction requires archival, place the prior implementation in a dated change record under `docs/decisions/` with a reason and removal/migration note, not as dead code inside the runtime file.
