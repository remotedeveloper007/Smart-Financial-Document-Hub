# System Administrator Manual

## 1. Purpose and role boundary

A **System Admin** (currently stored as `super_admin`) is a trusted platform operator. This role is for maintaining the application itself across customer workspaces: investigating faults, checking queue and application health, and reviewing system-wide audit events.

A System Admin is **not automatically a tenant Owner**. Platform access and tenant access are separate:

- Platform administration is granted by a record in `platform_administrators`.
- Workspace roles (Owner, Admin, Manager, Accountant, Viewer) apply to one tenant team and are managed through that team's membership workflow.
- A platform administrator can view cross-team operational data but does not automatically receive a tenant user's normal document, integration, billing, or AI permissions.
- A tenant Owner/Admin cannot access platform administration just because of their workspace role.

The current platform model only has a `super_admin` role assigned by the platform-administrator seeder. Access gates check whether a user has a platform-administrator record; they do not yet apply different abilities for `system_owner`, `super_admin`, or other role values. **Do not create a `developer` role record and assume it is restricted.** Any current platform-admin row grants the same broad platform access.

## 2. Sign in and open platform tools

1. Open the application's `/login` page and sign in with the System Admin account.
2. Verify the account's email. Unverified users are redirected to `/verify-email` and cannot enter the application.
3. Use the platform-only navigation for **System Health**, **Queue Monitor**, **Fault Logs**, **Failed Jobs**, and **System Audit Logs**. Tenant workspace links are hidden for platform administrators. If a platform link is missing, sign out and back in after the platform-admin seed has been run.
4. The corresponding routes are:

| Tool | URL | Purpose |
|---|---|---|
| System Health | `/admin/system-health` | Summary of fault and failed-job counts, severity totals, and recent records. |
| Queue Monitor | `/queue/status` | Pending/failed queue counts and recent failed jobs. |
| Fault Logs | `/admin/fault-logs` | Review recorded fault details. |
| Failed Jobs | `/admin/failed-jobs` | Inspect, retry, or delete a failed queue job. |
| System Audit Logs | `/audit/logs` | Read application-wide audit events with the affected team name. |
| Horizon | `/horizon` if installed/configured | Queue dashboard protected by the platform-admin gate. Horizon is not currently installed/configured in this repository. |

Access is enforced on server routes as well as navigation. A 403 means the signed-in account does not currently have platform access; do not work around it by removing the authorization check.

## 3. Routine health check

At the start of an operational shift or after a deployment:

1. Open **System Health** and record the time and any Critical/High faults or failed jobs.
2. Open **Queue Monitor**. Note whether pending jobs are increasing and whether recent failures share a queue, exception, or time window.
3. Open the relevant fault detail and failed-job detail. Identify the affected feature, class/file, exception, and any deployment/configuration change around the failure.
4. Check application logs (`storage/logs/laravel.log` locally; use the configured log collector in production) and deployment/worker logs. Never paste credentials, signed verification URLs, provider secrets, customer documents, or full sensitive payloads into tickets or chat.
5. If the problem is recurring, preserve the fault/audit evidence, identify an owner, and escalate it to the developer/on-call engineer. Do not delete the record to make the dashboard look healthy.
6. After a fix is deployed, check that the queue is draining and the same fault is no longer being generated. Document what changed and how the issue was verified.

System Health currently reports fault-derived counts and database queue counts. Treat these as diagnostic signals, not a complete uptime, database performance, storage-capacity, or security-monitoring service. Verify the underlying infrastructure monitoring separately.

## 4. Handling a failed job safely

The failed-job screen offers **retry** and **delete** actions. A retry can repeat the job's side effects; first determine whether it is safe and idempotent.

1. Inspect the failed job and exception. Identify the job type and tenant/record it affects without exposing customer data unnecessarily.
2. Check the cause first: for example, fix a missing configuration, unavailable provider, bad file, or temporary dependency outage.
3. Confirm whether the job is safe to replay. If it can charge a payment method, send an external request, create a duplicate record, or overwrite data, ask the feature owner before retrying.
4. Retry a single known-safe job and monitor the queue and affected record.
5. Delete only when the job is understood, no longer needs retry, and retention policy permits removal. Deletion removes the failed-job record; it does not repair the original business operation.
6. Record the incident, decision, and result in the team's approved incident tracking system.

Do not use the queue screen as a substitute for provider reconciliation or database backups.

## 5. Reviewing System Audit Logs

System Audit Logs span customer teams. They can include sensitive operational facts, such as a `ai.provider.settings.updated` event and the model `App\\Models\\TeamAiProviderSetting`. The current settings audit event records provider/model metadata, not the encrypted credential itself; still treat all audit data as restricted.

The current page shows date, team, action, model, message, and severity. Although audit rows store a `user_id`, the page does not currently display the actor's name/email; it also does not provide a complete immutable forensic export, all audit event types, or a full change diff for every feature. Treat it as a useful starting point, not a complete forensic console.

- Use the team name to establish the correct tenant context before escalating.
- Only use events for support, incident response, compliance, or approved operations.
- Do not share screenshots or records containing customer identifiers outside authorized channels.
- Audit events are evidence. Do not edit or delete them to hide an action.

## 6. What System Admin can and cannot do today

### Available to the seeded `super_admin`

- View application-wide System Health, queue status, fault logs, and failed jobs.
- Retry or delete failed-job records through the current admin routes.
- Delete a fault log through the current admin route. Avoid doing so during incident response; preserve evidence and follow retention policy.
- Read system-wide audit events across teams.
- Pass the configured Horizon authorization gate if Horizon is separately installed and enabled.

### Not currently provided by a System Admin screen

- Creating customer workspaces or managing all workspace users and roles from one platform console.
- Assigning or removing platform administrators in the UI. Current assignment uses the server-side `SYSTEM_ADMIN_EMAILS` setting and `PlatformAdministratorSeeder`; revocation requires deleting the corresponding database row through an approved database operation.
- A separately permissioned Developer, Support, Security Auditor, or Read-only Operator platform role.
- A role editor, platform-admin approval workflow, two-person approval, or platform-admin activity review workflow.
- Comprehensive tenant usage/billing management, subscription enforcement, customer impersonation, or production-ready incident/SLO dashboards.
- Guaranteed Horizon functionality; the dependency and runtime configuration are not currently present.

The application may expose individual tenant features to a System Admin only if that account also has the appropriate tenant membership and role. Platform-admin status alone should not be used to bypass tenant authorization.

## 7. Can a System Admin assign a Developer role?

**No, not as a distinct platform role in the current system.** The current application has no Developer role or UI to grant it. It is also unsafe to create a platform-admin row with a `developer` string: the current authorization gate treats any row as full platform administrator access.

Tenant roles are different. An Owner/Admin can assign one of the predefined tenant roles (Owner, Admin, Manager, Accountant, Viewer) to a member of their own team using **Team Members**. That does not grant system health, failed-job, or cross-team audit access. A developer working on the software should normally receive repository, CI, staging, and observability access through the organization's engineering access controls—not a customer tenant role.

### Recommended future platform roles

If the organization needs several operators, implement and test separate capabilities before assigning them:

| Proposed platform role | Appropriate work | Suggested boundary |
|---|---|---|
| **System Owner** | Owns production access policy and approves platform-admin assignments. | Full platform authority; tightly limited to named accountable owners. |
| **Platform Developer / Engineer** | Diagnoses code/runtime faults, reviews deploys, and fixes application bugs. | Read operational diagnostics; production mutation/retry only when needed and audited. Prefer repository/CI access and staging for development. |
| **Operations / SRE** | Monitors queues, workers, deployments, and infrastructure; performs approved recovery. | Queue/health actions; no routine access to customer documents, credentials, or unrestricted audit contents. |
| **Security Auditor** | Reviews security and access events for approved investigations. | Read-only audit/security events; no retry/delete or tenant data mutation. |
| **Customer Support** | Helps a particular customer with approved support requests. | Tenant-scoped support tooling with consent, time limits, and auditable access; not global system access. |

These are recommendations, **not roles that exist today**. Implement least-privilege gates and separate policy checks for each action before using these names.

## 8. Platform-admin provisioning and removal

The seeder grants `super_admin` only to an existing verified user whose email appears in the server configuration. It does not create an account or set a password.

To provision:

1. Create the operator account through the normal registration process and complete email verification.
2. Add the exact account email to server-side `SYSTEM_ADMIN_EMAILS`.
3. Run `php artisan config:clear` locally, then `php artisan db:seed --class=PlatformAdministratorSeeder --force`.
4. Confirm the operator can sign in and open a platform page, and confirm a tenant Owner cannot.
5. Keep the environment file and database access restricted. Do not commit the email list or share seed/config output containing private information.

To revoke:

1. Remove the user's row from `platform_administrators` using a reviewed administrative procedure.
2. Remove the email from `SYSTEM_ADMIN_EMAILS` so a later seeder run cannot restore access.
3. End active sessions and review recent System Audit Logs.

The seeder is idempotent. Running it with an empty list, a missing user, or an unverified user fails instead of silently granting access.

## 9. Daily security rules

- Use an individual verified account; do not share platform-admin credentials.
- Use a password manager and MFA at the identity provider/hosting layer where available. Application-level MFA is not currently documented as implemented.
- Keep platform-admin accounts to the minimum number of named operators.
- Do not open or download customer files unless a separate approved support workflow authorizes it.
- Never copy API keys, passwords, signed URLs, full exception payloads, or financial documents into logs, issues, or chat.
- Use staging to reproduce bugs. Make production changes through reviewed version control, deployment, backup, and rollback procedures.
- Before database or queue mutations, record the reason, target, approver (if required), and recovery plan.
- Report suspected unauthorized access immediately and preserve logs/evidence.

## 10. Known gaps and verification checklist

- [ ] Confirm platform access is limited to named, verified operators.
- [ ] Confirm a tenant Owner/Admin receives 403 for system health, queue, audit, fault, and failed-job routes.
- [ ] Review whether each fault and failed-job route should be read-only or allow mutation for the current operator.
- [ ] Add distinct least-privilege platform capabilities before introducing Developer/Operations/Auditor roles.
- [ ] Establish audit retention, backups, incident response, alerting, and production observability.
- [ ] Verify Horizon, workers, database, cache, storage, email, and provider webhooks in the actual deployment environment.

For code-level access rules and seeder tests, see [Authorization Foundation](../features/04-authorization.md). For setup and deployment procedures, see the [Developer Manual](developer-manual.md).
