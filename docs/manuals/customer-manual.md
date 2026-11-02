# Customer Manual — Smart Financial Document Hub

Purpose

- Guide end users (customers) through key product flows: registration, team management, uploading documents, searching and exporting.

Getting started (for customers)

1. Registration
- Navigate to the site URL and click `Register`.
- Provide your name, email, and password.
- Expect an email verification step (if enabled).

2. Create or join a Team
- After registration, create a Team (company) and invite colleagues by email.
- Roles: Owner, Admin, Manager, Accountant, Viewer.
- Each invited user accepts invite and is scoped to the team.

3. Uploading documents
- UI: Dashboard → Documents → Upload
- Supported formats: PDF, CSV, JPG, PNG
- Steps: drag & drop or choose file, provide optional metadata (name, date, tags), click Upload.
- Expected: file upload progress, a queued processing job, notification/email when processing completes.

4. Viewing processed data
- UI: Documents → click a document → View extracted transactions/invoices.
- Expected: structured rows of extracted transactions, currency, dates, and classifications.

5. Search and export
- UI: Search → enter filters (date range, amount, vendor)
- Expected: tenant-scoped results only (your team’s data)
- Export: CSV or XLSX via Export button on results page.

6. Billing & subscriptions
- UI: Settings → Billing
- Steps: add payment method, choose plan, subscribe.
- Expected: subscription record in `Subscriptions` and invoice sent to the billing email.

Support and troubleshooting

- If uploads fail: check file size and type, then retry.
- If extraction is incorrect: use the UI to flag and submit the document for reprocessing.
- For account issues: contact support with team id, user email, and a screenshot.

Security notes for customers

- Keep accounts secure with strong passwords and 2FA where offered.
- Only invite trusted team members and assign roles appropriately.

---

# Guide for customer-facing screens

This guide describes the customer-facing screens that are currently registered in the application. The available screens depend on your account, team membership, permissions, and the service configuration enabled by your administrator. Replace `{site}` with the address provided by your organization.

## Sign In and Account

1. Open `{site}/login` and enter the email address and password supplied for your account.
2. A successful sign-in opens the dashboard. If the account requires email verification, follow the verification message or use the verification notice shown after sign-in.
3. Use `{site}/forgot-password` to request a reset link if you cannot sign in. A reset link expires; request a fresh one if it is no longer valid.
4. Open `{site}/profile` to review or update your profile. Account deletion, if offered by your deployment, requires your current password and is permanent.
5. Use the account menu's sign-out action when finished, especially on a shared device.

If you do not have an account, registration is at `{site}/register` only when the deployment permits public registration. Contact your organization's administrator if it is disabled.

## Invoices, Receipts, and Contracts

These are read-only lists of records already available to your team:

- Invoices: `{site}/invoices`
- Receipts: `{site}/receipts`
- Contracts: `{site}/contracts`

The page shows an empty message when your team has no records. When records are shown, check their vendor/party, document number, dates, amount/value, currency, status, and reference. Lists show only the team data your account is authorized to view. Contact your administrator if a page reports access denied or expected records are missing.

**Important accuracy notice:** extracted financial records must be reviewed against their source documents before they are used for accounting, tax, payment, or reporting decisions. Some repository extraction paths currently use sample/demo data; do not assume extraction is production-grade OCR or that the displayed values have been independently verified.

## Accounting Integrations

Open `{site}/integrations` to view or manage accounting provider connections when your role allows it. Provider authorization requires your organization's configured sandbox or production credentials. Do not enter credentials into an untrusted environment. Ask your administrator which provider, account ID, and environment to use. A successful save or connection test means the request completed; it does not, by itself, prove that imported or exported accounting records are reconciled.

The `Tally` option currently connects to a customer-configured Tally-compatible REST API using the documented `/ping`, `/transactions/import`, and `/transactions/export` contract. It is not yet a verified native TallyPrime XML/voucher connector. Before using it for accounting, your administrator must verify the exact mapping and reconcile imported/exported values in an approved sandbox. A queued response means only that processing was accepted.

## Subscription Plans

Open `{site}/billing` to view the subscription-plan foundation. The current screen/API exposes plan and subscription records; do not assume that card payment, invoicing, cancellation, tax calculation, or a billing portal is available unless your administrator confirms those workflows are enabled.

## Queue and AI Screens

- Queue status is available at `{site}/queue/status` only to roles permitted by the deployment. It is operational information, not a customer action screen. If a job remains pending or fails, contact your administrator with the time and affected document/reference; do not repeatedly submit the same file.
- The AI Assistant is at `{site}/ai` for users with the appropriate permission and configured service. Do not submit sensitive personal, bank, tax, or confidential data unless your organization has approved the AI provider and data-handling policy. Treat generated text as assistance, not financial advice or a verified ledger entry.

## Documents, Transactions, Search, Audit, and Team Members

These pages have registered routes in the application. Availability in a deployed site still depends on the deployed version, your permissions, and your administrator's configuration:

- Upload document: `{site}/documents/upload`
- Transactions: `{site}/transactions`
- Search: `{site}/search`
- Audit history: `{site}/audit/logs` (permission-restricted)
- Team members: `{site}/team/members` (permission-restricted)

**Important:** document extraction currently uses deterministic demo data in local/testing environments. Production extraction is intentionally blocked until a real extraction provider is implemented. Do not treat generated transaction, invoice, receipt, contract, or AI output as real or verified financial data.

## Troubleshooting and Support

- **Sign-in trouble:** check the email spelling, reset your password, and check whether email verification is required.
- **Access denied:** ask your team administrator to confirm your team membership and role. Do not share your password or verification link.
- **Unexpectedly empty list:** confirm the selected account/team with your administrator and whether processing has finished.
- **Incorrect financial value:** compare it with the source document, do not use it as an authoritative value, and report the record/document reference through your organization's approved support channel.
- **Slow or failed processing:** report the time, screen, non-sensitive reference, and what you expected. Avoid submitting duplicate uploads while the first request may still be processing.
- **Integration issue:** report provider name, test/production environment, timestamp, and visible error. Never send access tokens, security codes, webhook secrets, or passwords in a support ticket.

Protect your account with a unique password, use only trusted devices, share access through an administrator-approved account process, and upload only documents your organization is permitted to process.