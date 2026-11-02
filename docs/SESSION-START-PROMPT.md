Read and follow: 

1. .github/copilot-instructions.md
2. docs/COPILOT-QUICK-CONTEXT.md
3. docs/PROJECT-CONSTITUTION.md
4. docs/ARCHITECTURE.md
5. docs/DEVELOPMENT-ROADMAP.md
6. docs/SESSION-START-PROMPT.md

Follow all repository standards.

Implement ONLY the next roadmap item.

Requirements:

* Production-ready code
* Laravel 13 best practices
* Tests required
* Documentation required
* Verification checklist required
* Controller must be thin
* Implement all heavy time consuming task in background like(sending emailuploading or processing file)
* print test results in respective dodument for each feature.
* Also explain in docs/features wise clearly, how to test each and every perticular featured included manually by Tester to completely test the Application from Frontend with Input and the expected output.
* Also maintain Todos after each featured added in docs/features

NOTE1: Document must be maintained for each and every line of code added or removed with greater detaild in simple English also add check list for each featured implemented in documents. 

Purpose: Allow even junior developers (with no experience) to identify issues quickly.

NOTE2: Must create and maintain separate docs for Frontend and explain each and every lines of code in each file created seprately in simple English with:
1. Purpose. 
2. Business Reason. 
3. Architecture. 
4. Folder Structure. 
5. Dependencies. 
6. Flow. 
7. Testing Procedure. 
8. Tests included. 
9. Troubleshooting in Browser and console to fixed bugs, Maintenance, Sample Date for Input to test Frontend Manually By Tester and Expected Output and its format in browser console or in debugging tools. 
10. Why this is added and useful
11. Explain Line by line of frontend in details in simple English to make it easy for Junior Developer to understand to Troubleshoot, fixed and maintain independently.
12. The application must be highly stable, scalable, reliable and highly performance optimizeable and fully maintainable.
13. Must follow SOLID Principles and Design Patterns.
14. Explain Architectural and Design Patterns in greater details step-by-step/line-by-line in simple English to make it easy for Junior Developer to understand.
15. Find the drawback of every features, and draw the outline to overcome those drawback to make the application highly professionals, highly advanced, more desireable to users.
16. The application must be highly stable, reliable and scalable and highly performance optimizeable, fully maintainable and highly secure in therm of data security purpose and over all application security.


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


**IMPORTANT NOTES MUST BE FOLLOWED**: Must strictly follows, that in case of making changes in any file, do not completely removed old code, you must comment the old code in order to make any changes, so the old code history must be mailtained in commented with details explaination to understand the reason of commenting it.



After completion:

STOP.

Do not continue to another feature until approval is provided.