# Manuals and UI Coverage

Choose the guide for your role:

- [Developer Manual](developer-manual.md): setup, architecture, feature workflow, security, testing, performance, deployment, and maintenance.
- [Tester Manual](tester-manual.md): route-by-route manual scenarios, sample inputs, expected results, tenancy/access tests, and current gaps.
- [Customer Manual](customer-manual.md): end-user workflows and limitations for currently registered pages.
- [System Administrator Manual](system-admin-manual.md): platform-admin access, operational routines, safe queue/fault handling, role boundaries, and current limitations.

Feature-specific design details remain in [`docs/features/`](../features/). These manuals summarize the cross-feature workflow and link to the feature documentation; they are not a substitute for migration details, request contracts, or troubleshooting specific to one implementation.

## Current UI and Documentation Coverage

The registered route list was inspected with `php artisan route:list --except-vendor`. Routes are authoritative for browser URLs; a React file or controller without a route is not a usable page. Status below describes code presence, not production readiness or accuracy.

| Feature | Registered UI route | Frontend page found | Related docs/tests found | Status / note |
|---|---|---|---|---|
| Authentication | `/register`, `/login`, `/forgot-password`, `/reset-password/{token}`, `/verify-email` | Auth pages under `resources/js/Pages/Auth/` | Auth feature tests; `03-authentication.md` | Routed. Email delivery depends on environment configuration. |
| Dashboard | `/dashboard` | `Dashboard.tsx` | Existing general docs | Routed; verify its actual content and authorization in QA. |
| Profile | `/profile` | `Profile/Edit.tsx` | `ProfileTest.php` | Routed. |
| Invoices | `/invoices` | `Invoices/Index.tsx` | `13-invoice-processing*.md`, `InvoiceFeatureTest.php`, policy test | Routed; list endpoint is paginated and authorized. |
| Receipts | `/receipts` | `Receipts/Index.tsx` | `14-receipts-processing*.md`, `ReceiptFeatureTest.php`, policy test | Routed; verify read permission and team scope. |
| Contracts | `/contracts` | `Contracts/Index.tsx` | `15-contracts-processing*.md`, `ContractFeatureTest.php`, policy test | Routed; verify read permission and team scope. |
| Integrations | `/integrations` | `Integrations/Index.tsx` | `16-*` integration docs and provider/import/export tests | Routed; provider sandbox configuration required; integrations are security-sensitive. |
| Billing | `/billing` | `Billing/Plans.tsx` | Billing feature docs/tests | Routed foundation. Payment-provider lifecycle must not be assumed. |
| Queue status | `/queue/status` | `Queue/Status.tsx` | queue feature docs and `QueueStatusTest.php` | Routed operational view. Confirm the queue schema/connection is configured. |
| AI Assistant | `/ai` | `Ai/Index.tsx` | `17-ai-assistant*.md`, `18-team-ai-provider-settings.md`, AI tests | Routed and queued; teams can configure OpenAI. Financial-data retrieval, cost limits, privacy controls, and staging verification remain release gates. |
| System health | `/admin/system-health` | `Admin/SystemHealth/Index.tsx` | `18-system-health-dashboard.md`, system health tests | Routed to verified admin-capable users. |
| Fault logs | `/admin/fault-logs` and `/admin/fault-logs/{id}` | `Admin/FaultLogs/Index.tsx`, `Show.tsx` | admin/fault docs and tests | Routed, admin-only. |
| Failed jobs | `/admin/failed-jobs` and `/admin/failed-jobs/{id}` | `Admin/FailedJobs/Index.tsx`, `Show.tsx` | admin UI docs and tests | Routed, admin-only; retry only safe jobs. |
| Document upload | `/documents/upload`, `POST /documents`, `GET /documents` | `Documents/Upload.tsx` | upload/queue docs and tests | Routes are registered. Uploaded files are private and scanner-gated. Production extraction remains blocked until a real extraction provider is implemented and configured. |
| Transactions and export | `/transactions`, `/transactions/list`, `/transactions/export` | `Transactions/Index.tsx` | extraction/export docs and tests | Routes are registered. Verify data provenance: current document extraction is simulated. |
| Search | `/search`, `/search/transactions` | `Search/Index.tsx` | search docs and tests | Routes are registered; confirm current filters/search scope against the feature guide. |
| Audit log list | `/audit/logs`, `/audit/logs/list` | `Audit/Index.tsx` | audit docs and tests | Routes are registered and policy-protected; audit event coverage still requires review per action. |
| Team member UI | `/team/members`, `/team/members/list` and mutations | `Team/Members.tsx` | user management docs and tests | Routes are registered and permission-protected. |

## Maintenance of This Register

After changing routes, Inertia page names, permissions, or feature tests:

1. Run `php artisan route:list --except-vendor` and check that each documented URL exists.
2. Confirm the route resolves the intended Inertia page and that the frontend entry is included in the Vite build.
3. Run the feature and authorization tests for that feature, including tenant isolation.
4. Update the corresponding row, developer/tester/customer instructions, and feature docs with executed results.
5. Keep unimplemented items marked as blocked/planned; do not convert a code-file existence check into a claim of working UI.

## Release status (verified 2026-11-02)

Route registration and passing local tests do not establish deployment readiness. The current local build has these known release gates:

- The application has demo/simulated AI responses and deterministic fake transaction, invoice, receipt, and contract extraction. Do not rely on these outputs for customer accounting.
- Billing creates local subscription records only; no payment-provider checkout, webhook verification, invoices, cancellation, or usage enforcement is implemented.
- `composer.json` now requires PHP `^8.4`, matching the constitution. The local PHP runtime is 8.3.14, so local test results do not prove compatibility with the supported production runtime; the PHP 8.4 CI run must pass.
- Runtime dependencies for Horizon, Pulse, Pennant, Laravel Scout, and an external search engine are not installed/configured.
- The repository has no production Dockerfile or full application Compose stack; the current Compose file only supplies the optional ClamAV service. Deployment, storage-backend, and rollback procedures have not been exercised on a target VPS/Hostinger environment.
- Per-tenant usage/cost metering is not implemented, and the current routes are web routes rather than versioned `/api/v1` endpoints.
- The ClamAV live integration, PostgreSQL runtime suite, provider sandbox flows, browser E2E, backup restoration, monitoring, and deployment rollback have not been verified.
- The PHPUnit suite is configured for MySQL. A CI workflow now defines PHP 8.4 jobs for MySQL and PostgreSQL; its hosted results must be checked before release. Local PHP 8.3.14 is below the declared PHP minimum.

The application must remain marked **not production-ready** until each gate is closed with evidence from the intended deployment environment.