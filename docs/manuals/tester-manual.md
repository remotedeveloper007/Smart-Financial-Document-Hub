# Tester Manual — Smart Financial Document Hub

Purpose

- Provide step-by-step test cases with inputs, expected outputs, and UI locations so QA can verify features manually and via automated tests.

Test environment setup

- Use the same branch as developers.
- Ensure `.env.testing` matches `phpunit.xml` values (MySQL test DB configured).
- Prepare test accounts: sample `Admin`, `Manager`, `Accountant`, `Viewer` in seeders.

Global test checklist

- Verify tenant isolation: data created under Tenant A should not be visible under Tenant B.
- Verify auth flows: registration, login, logout, password reset.
- Verify queues: document uploaded enqueues expected jobs.
- Verify fault capture: trigger a test fault route and confirm logs.

Key manual test cases

1) Authentication (UI)
- URL: `/login` and `/register`
- Steps: register a new user, verify email (if mailer test), login, logout.
- Input: name, email, password
- Expected: successful registration, redirect to dashboard, session persists until logout.

2) Team creation and tenant isolation
- URL: `/teams` (owner only)
- Steps: create Team A and Team B, add users to each team, create documents in Team A, then log in as Team B user and assert documents are not visible.
- Expected: strict isolation; 403 for cross-tenant attempts.

3) Document upload pipeline
- URL: `/documents/upload`
- Steps: upload sample PDF/CSV, ensure file is stored, job is queued, processing job extracts metadata.
- Input: sample documents in `tests/_fixtures`.
- Expected: a Document record created, `ProcessDocumentJob` dispatched, `transactions` and `invoices` created as applicable.

4) Fault capture
- URL: `GET /__test/fault` (test route)
- Steps: visit route while authenticated; verify 500 response and entry in `storage/logs/laravel.log` (or fault admin UI when available).
- Expected: 500 response, `unit test fault` present in `laravel.log` or `fault_logs` admin.

5) Queue processing (integration)
- Steps: switch `QUEUE_CONNECTION=redis`, run worker, upload large file and verify job is processed asynchronously.
- Expected: job completes, DB records updated, no duplicate writes.

Automated test guidance

- PHPUnit: `php artisan test`.
- Run a single test: `php artisan test --filter TenantIsolationTest`.
- For failing tests, collect `storage/logs/laravel.log` and failing stack trace.

Reporting bugs

- Provide steps to reproduce, expected vs actual, environment details, relevant log excerpts, and screenshots.

---

# Tester Manual

Use this manual to verify observable behavior in a local or approved test environment. Replace `{base-url}` with the address printed by the running Laravel server, commonly `http://127.0.0.1:8000`. The current application uses session authentication; log in before opening protected pages. Never use real customer financial data or production credentials in test runs.

## 1. Test Setup

1. Check out the branch/build under test and follow [Developer Manual setup](developer-manual.md#2-requirements-and-local-setup).
2. Configure a disposable database in `.env.testing`/`phpunit.xml`; confirm the test configuration before running commands.
3. Run migrations and approved seeders in the test environment.
4. Start the app and frontend using `composer run dev`, then open the server URL shown in the terminal.
5. Use separate users/teams for isolation tests. Grant only the permissions required for each scenario.
6. In browser DevTools, keep Network and Console open. Record status codes and response JSON, but redact credentials, cookies, tokens, and private document contents.

Automated checks:

```bash
php artisan test
npm run test:unit -- --run
npm run build
```

The second command is applicable when frontend unit tests exist. Do not edit test environment settings to point at production.

## 2. Test Result Recording

For every test report, write build/commit, browser and OS, role/team, exact URL, steps, input, expected result, actual result, pass/fail, and evidence. Include request path/status and relevant sanitized response fields. A test is not passed merely because the page rendered; verify its data, authorization, tenant scope, and error behavior.

## 3. Live UI Route Matrix

The following paths are registered in the current `php artisan route:list --except-vendor` output. Permission checks still apply and can return `403`.

| Feature | UI URL | Data/action URL | Input | Expected observable result |
|---|---|---|---|---|
| Welcome | `{base-url}/` | none | none | Welcome page with login/register links according to route availability. |
| Register | `{base-url}/register` | `POST /register` | valid name, unique email, password and confirmation | Account is created; follow the configured email verification/login flow. |
| Login/logout | `{base-url}/login` then `POST /logout` | same | registered email/password | Valid credentials establish a session; logout ends it and returns to the public page. |
| Password reset | `{base-url}/forgot-password` | `POST /forgot-password`, then `/reset-password/{token}` | registered email; valid reset token and new password | Reset link request shows the configured status; a valid token changes the password. Use test mail capture, not real inboxes. |
| Email verification | `{base-url}/verify-email` | signed verification URL; resend is `POST /email/verification-notification` | unverified test user | Verification notice is shown; valid signed link verifies the account. Expired/invalid signatures are rejected. |
| Dashboard | `{base-url}/dashboard` | none | verified authenticated user | Dashboard loads for an eligible session; unauthenticated users are redirected to login. |
| Profile | `{base-url}/profile` | `PATCH /profile`, `DELETE /profile` | valid profile fields; current password for deletion | Profile update persists and redirects back; valid deletion removes the test account and ends its session. |
| Invoices | `{base-url}/invoices` | `GET /invoices/list` | authorized team with invoice records | Invoice page loads; JSON contains paginated `invoices.data` for the active team only. Empty team shows empty state. |
| Receipts | `{base-url}/receipts` | `GET /receipts/list` | authorized team with receipt records | Paginated `receipts.data`; verify no other team's rows. |
| Contracts | `{base-url}/contracts` | `GET /contracts/list` | authorized team with contract records | Paginated `contracts.data`; verify dates, vendor, number, value and status shown correctly. |
| Integrations | `{base-url}/integrations` | see route list: POST/PUT/DELETE `/integrations`, POST `/integrations/test-connection` | in test env, provider `quickbooks`, status `enabled`, test external ID `acct-qa-001`; use only sandbox credentials | Page lists/saves/updates/removes a team integration according to authorization. Provider connection tests must be run against a sandbox or mocked provider. |
| Integration import/export | `{base-url}/integrations` | `POST /integrations/{id}/import`, `POST /integrations/{id}/export`; export status/list/download routes | test CSV or sandbox data | Verify queued/result status and tenant ownership; large/chunked uploads resume without duplicate finalized data. See integration-specific tests and docs. |
| Billing | `{base-url}/billing` | `GET /billing/plans`, `POST /billing/subscriptions` | `plan_id` from the returned plans list | Plans render; subscription endpoint returns `201` and a subscription response for the active team. Do not interpret this foundation as payment processing unless a payment provider flow is present. |
| Queue status | `{base-url}/queue/status` | `GET /queue/status/metrics` | none | UI metrics match JSON `pending_jobs`, `failed_jobs`, and recent failed jobs. Use a disposable failed job to test display; do not retry it blindly. |
| AI Assistant | `{base-url}/ai` | `GET /ai/list`, `POST /ai/create` | prompt: `Summarize the latest approved test transaction data.` | Authorized request is accepted asynchronously (`202`, ID returned); status/result appears after worker processing. Verify no cross-team data and cost/rate controls. Use mock/test provider configuration. |
| System Health (admin) | `{base-url}/admin/system-health` | page data is server supplied | admin-capable verified test user | Counts and recent faults/jobs render. A non-admin must be denied. |
| Fault log list/details (admin) | `{base-url}/admin/fault-logs`, `/admin/fault-logs/{id}` | `DELETE /admin/fault-logs/{id}` | seeded test fault ID | Admin sees fault detail and can remove the test record; non-admin receives denial. |
| Failed jobs (admin) | `{base-url}/admin/failed-jobs`, `/admin/failed-jobs/{id}` | `POST .../{id}/retry`, `DELETE .../{id}` | seeded safe test job ID | Admin can inspect, retry, or delete only the test job; verify state changes. |

For provider OAuth, the registered callbacks are under `/integrations/{provider}/connect` and `/callback` for QuickBooks, Xero, Zoho, and Tally. Only test with provider sandbox applications and valid callback configuration. Do not expect those external providers to work with placeholder credentials.

## 4. High-Value Scenarios

### Authentication and authorization

- Open a protected URL in a private browser session; expect a redirect to `/login`.
- Log in with a valid test account; verify `/dashboard` loads.
- Attempt a protected feature without its required policy/permission; expect `403` or the documented denial response.
- Exercise expired/invalid password reset and email verification links; ensure they do not mutate the account.
- Verify password deletion confirmation rejects a wrong password and accepts the current test password.

### Tenant isolation

1. Create Tenant A and Tenant B test teams and one authorized user for each.
2. Create one record of the tested type in each team.
3. Request the list endpoint as Tenant A. Expect only Tenant A's record.
4. Attempt to address a Tenant B record through any ID-based route. Expect `404` or `403`, never Tenant B data.
5. Repeat for invoice, receipt, contract, integration, AI request, job, export, and any other tenant-owned domain touched by the feature.

### Queue reliability

- Use an isolated test queue and known test payload.
- Confirm HTTP acceptance and queued status before running a worker when the workflow is asynchronous.
- Process once and process/retry again. Assert no duplicate financial rows or side effects.
- Cause a controlled test failure and verify useful, sanitized diagnostics are captured and the job reaches the configured retry/failure state.

### Invoices, receipts, and contracts

- Open each UI URL above as an allowed user.
- For populated data, compare every rendered field to the API JSON and DB fixture; check date and decimal precision.
- For an empty team, confirm a clear empty state rather than a blank screen.
- For a denied user, verify page and JSON endpoint both deny access.
- Confirm all list responses are paginated and tenant-scoped.

## 5. Current Route/Frontend Verification Notes

The application currently registers UI and API routes for documents, transactions, search, audit logs, team members, invoices, receipts, contracts, billing, integrations, queue status, AI, authentication, and admin operations. Confirm the exact route names with:

```bash
php artisan route:list --except-vendor
```

Registration is not a UI pass. Test each page in a browser with authorized and unauthorized users, verify the expected data and empty/error states, and check responsive navigation and browser-console errors. Financial extraction pages must be treated as demo data until a real extraction provider replaces the simulated implementation.

The live scanner, extraction, billing, OAuth provider, PostgreSQL, and deployment validations are environment-dependent. Record them as blocked unless tested in the relevant isolated environment with synthetic data.

## 6. Defects and Evidence

When reporting a bug include:

- exact build/branch, browser version, role, team, and environment;
- URL and steps from a clean starting state;
- input data (synthetic only), expected versus actual output;
- HTTP method/status and sanitized response body from Network tools;
- console error and relevant timestamp/request ID;
- screenshot/video if it clarifies layout or interaction;
- whether it reproduces with the automated test command.

Never attach live access tokens, cookies, customer records, full financial files, or unredacted exception payloads.
