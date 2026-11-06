# 04 - Authorization Foundation

## Purpose

Implement role-based access control with tenant-aware roles and permissions.

## Business Reason

Authorization ensures users only access features and data allowed for their role and tenant. This is critical for secure financial document management.

## Architecture

- `roles` and `permissions` tables
- `role_user` and `permission_role` pivot tables
- `Role` and `Permission` models
- Model policies enforce feature permissions and team ownership
- `AuthServiceProvider` registers policies and gates
- Platform operations use a separate email allowlist (`SYSTEM_ADMIN_EMAILS`), never tenant role slugs
- `RegisterUserAction` assigns the default `Owner` role when a user registers
- `User::hasPermission()` evaluates the current team's roles, not just the user's account-wide identity

## What Happens After Login

1. A verified user signs in and is redirected to `/dashboard`.
2. The dashboard route requires authentication and email verification. It is the shared workspace landing page; it does not itself assign a role or grant access to every feature.
3. Each feature request is authorized by its controller/policy using the user's role permissions for the active team. Some pages, such as profile, require authentication but do not use a feature permission.
4. If the role lacks the permission required by a route, Laravel returns **403 Forbidden**. A visible navigation item is not proof of permission; backend authorization is the security boundary.
5. Team members and provider settings use separate capabilities: `manage_users` controls membership/roles, while `manage_ai_settings` controls AI-provider configuration.
6. `HandleInertiaRequests` shares only permission slugs granted to the signed-in user in the active team. The authenticated layout uses those slugs to hide unauthorized tabs consistently on desktop, tablet, and mobile.

The user must have a role attached in the active team. Merely belonging to a team, being logged in, or having a role with the same name in another team does not grant the permission.

Navigation visibility is a usability improvement, not an authorization boundary. Routes, controllers, and policies must continue to enforce access because users can type a URL or make a direct HTTP request. Hiding a tab does not prove a direct URL is protected; endpoint authorization tests are still required.

## Default Role Permission Matrix

This table reflects `Role::defaultRolePermissions()` in `app/Models/Role.php`. A check means that permission is granted by the default role. The current team screen can assign the predefined roles, but does not provide a UI for editing role-to-permission mappings; do not assume a team owner can customize the matrix in the browser.

| Permission / action | Owner | Admin | Manager | Accountant | Viewer |
|---|:---:|:---:|:---:|:---:|:---:|
| View documents (`view_documents`) | Yes | Yes | Yes | Yes | Yes |
| Upload/create documents (`create_documents`) | Yes | Yes | Yes | No | No |
| Edit documents (`edit_documents`) | Yes | Yes | Yes | No | No |
| Delete documents (`delete_documents`) | Yes | Yes | No | No | No |
| Export documents (`export_documents`) | Yes | Yes | Yes | Yes | No |
| View invoices (`view_invoices`) | Yes | Yes | Yes | Yes | Yes |
| View receipts (`view_receipts`) | Yes | Yes | Yes | Yes | Yes |
| View contracts (`view_contracts`) | Yes | Yes | Yes | Yes | Yes |
| View transactions/search (`view_transactions`) | Yes | Yes | Yes | Yes | Yes |
| Export transactions (`export_transactions`) | **No** | **No** | **No** | Yes | No |
| Manage team members and roles (`manage_users`) | Yes | Yes | No | No | No |
| Manage billing (`manage_billing`) | Yes | **No** | No | No | No |
| Manage team (`manage_team`) | Yes | Yes | No | No | No |
| Manage accounting integrations (`manage_integrations`) | Yes | Yes | No | No | No |
| Use AI assistant (`use_ai`) | Yes | Yes | No | No | No |
| Configure team AI provider (`manage_ai_settings`) | Yes | Yes | No | No | No |
| Application administration / system audit logs / queue monitoring | No | No | No | No | No |

**Important:** this is the permission assignment matrix, not a promise that every product operation is complete. For example, the transaction export policy requires `export_transactions`, so only Accountant receives it by default. Owner and Admin do not currently have that permission despite their broad role labels. Avoid assuming a role name implies an unlisted permission.

Platform-level access is separate from this tenant matrix. Only verified accounts recorded in the `platform_administrators` table can use system administration tools. Seed this table from existing, verified users listed in the server-side `SYSTEM_ADMIN_EMAILS` variable. A tenant Owner or Admin cannot grant themselves this access by changing team roles or permissions.

## Pages and Actions by Role

| Workspace page or action | Required permission / rule | Default roles with access | What an allowed user can do |
|---|---|---|---|
| `/dashboard` | Authenticated and email verified | Any signed-in verified team user | Open the shared dashboard. |
| `/profile` | Authenticated | Any signed-in user | View/update their own profile and password. |
| `/team/members` and its member APIs | `manage_users` and current-team membership | Owner, Admin | List members, add members, change roles, remove members. |
| `/documents/upload` and `POST /documents` | `create_documents` | Owner, Admin, Manager | Open the upload screen and submit a document. |
| `GET /documents` | `view_documents` | All five default roles | Read the active team's document list through the JSON API. There is not yet a separate read-only document page in the frontend. |
| `/transactions`, `/search`, transaction search API | `view_transactions` | Owner, Admin, Manager, Accountant, Viewer | View the current team's transaction list and search results. |
| `/transactions/export` | `export_transactions` | Accountant | Request a transaction export. |
| `/invoices` | `view_invoices` | All five default roles | View the current team's invoice list. |
| `/receipts` | `view_receipts` | All five default roles | View the current team's receipt list. |
| `/contracts` | `view_contracts` | All five default roles | View the current team's contract list. |
| `/integrations` and integration APIs | `manage_integrations` | Owner, Admin | Configure and manage accounting integrations, imports, and exports. |
| `/ai` and AI request APIs | `use_ai` | Owner, Admin | View AI request history and submit prompts when a provider is configured. |
| AI provider settings on `/ai` | `manage_ai_settings` | Owner, Admin | Select the team's AI provider/model and configure its team-owned credential. |
| `/audit/logs` | `AuditLogPolicy::viewAny` requires configured platform administrator | System Owner / Super Admin allowlisted by deployment | Read application-wide audit events, including sensitive provider-setting changes. |
| `/queue/status`, `/admin/*`, Horizon | `viewAdmin` / `viewHorizon` platform administrator gates | System Owner / Super Admin allowlisted by deployment | View application-wide queues, health, fault, and job-management screens. |
| `/billing` and subscription checkout/refund endpoints | `manage_billing` is checked by the controller or request; generic plan catalog requires sign-in | Owner | View team subscriptions and payment attempts, start checkout, and request a refund. This is a local subscription foundation, not payment processing. |

### Role-by-role summary

- **Owner:** assigned automatically to a newly registered user for their new team. Has all permissions listed in the matrix except `export_transactions`, which is currently missing from the default Owner grant. Can manage users/team/integrations/AI settings and use AI, but cannot view system audit logs or application operations unless separately allowlisted as a platform administrator.
- **Admin:** broad tenant role: can manage team members/team, integrations and AI settings, and use AI. Does not receive `manage_billing` or `export_transactions` by default and cannot view system audit logs or application operations unless separately allowlisted.
- **Manager:** can work with documents (create/edit/export), view invoices/receipts/contracts/transactions, and search, but cannot access platform administration or system audit logs.
- **Accountant:** can view invoices/receipts/contracts/transactions and export documents and transactions. Cannot manage billing, team members, team, integrations, AI provider settings, or access platform administration/system audit logs.
- **Viewer:** read-only permissions for documents, invoices, receipts, contracts, and transactions/search. Viewers can use `GET /documents`, but the current frontend has no separate read-only document page; `/documents/upload` correctly requires `create_documents`.

## Manual Verification After Login

Use the local demo accounts above in a local/testing environment only. Sign out between account changes so the browser session does not reuse the previous user.

1. Sign in at `/login`; confirm the account lands on `/dashboard`.
2. As tenant Admin, verify Team Members, Documents, Transactions, Search, Invoices, Receipts, Contracts, Integrations, and AI Assistant are visible; System Audit Logs, System Health, and Queue Monitor are hidden. As Manager, verify only Dashboard, Documents, Transactions, Search, Invoices, Receipts, and Contracts are visible.
3. As Accountant, verify Dashboard, Transactions, Search, Invoices, Receipts, and Contracts are visible; Billing, Team Members, Documents, Integrations, AI Assistant, and platform administration links are not. Document export permission does not currently have its own navigation tab.
4. As Viewer, verify Dashboard, Transactions, Search, Invoices, Receipts, and Contracts are visible; restricted tabs are hidden.
5. Repeat the checks at desktop, tablet, and mobile widths; the same permission rules must apply in all navigation presentations.
6. Use the browser Network panel to test restricted URLs as well. A 403 means authorization denied that request; it is not the same as the DEBUG middleware log entry. Never solve a 403 by removing a controller authorization check.

## Local Test Accounts

`DatabaseSeeder` creates local/testing-only users with password `password` and attaches each to its matching role in the seeded `Primary Team`:

| Email | Assigned role |
|---|---|
| `admin@local.test` | Admin |
| `manager@local.test` | Manager |
| `accountant@local.test` | Accountant |
| `viewer@local.test` | Viewer |

The demo accounts are synthetic and must never be seeded into production. A new self-registered user is assigned Owner by `RegisterUserAction`; the local demo seeder does not create an Owner account. `CreateDefaultRolesAction` creates the permission catalog before assigning role grants, so registration on a fresh install does not create empty roles. To investigate missing tabs or a 403, first confirm the signed-in account, active team, assigned role, and role-permission rows. On an existing local database, run `php artisan db:seed --class=AuthorizationSeeder` to synchronize default permissions and grants for existing teams. This does not assign roles to existing users.

## Platform System Administrators

System Audit Logs, System Health, Queue Monitor, fault/failed-job administration, and Horizon expose information across customer teams. They are not tenant permissions and must never be granted through a tenant Owner/Admin role.

1. Create each platform operator account through the normal account registration process and verify its email. The seeder never creates users or passwords.
2. Add the verified account emails to the server environment variable `SYSTEM_ADMIN_EMAILS`, separated by commas. For the local System Admin account, set `SYSTEM_ADMIN_EMAILS=superadmin@gmail.com`.
3. Run `php artisan migrate` and `php artisan db:seed --class=PlatformAdministratorSeeder --force`. The seeder fails if the list is empty, a user does not exist, or an account is unverified.
4. The idempotent seeder stores each account's `user_id` and `super_admin` role in `platform_administrators`; access checks read the database. Refresh Laravel's configuration cache after changing the environment list.
5. To revoke access, delete the user's row from `platform_administrators`. Do not expose the seed configuration to tenant users or store it in client-side configuration.

Audit log page and API both authorize with `AuditLogPolicy`. The API intentionally removes the tenant global scope only after authorization so platform operators can investigate events across teams. The response includes the team name for context. Tenant roles with an old audit permission are still denied.

### Existing local database repair and verification

An earlier navigation bug allowed tenant Owner/Admin roles into platform operations. Platform access now uses the verified-email allowlist above; it does not depend on team role records. Default tenant roles no longer receive audit access, and the system audit policy rejects tenant permissions, including legacy grants.

After deploying the access fix, run the migration and platform-administrator seeder, refresh cached configuration, and hard-refresh the browser (Ctrl+F5) or sign out and back in. Tenant Owners/Admins should not see system audit, health, or queue links and direct requests must return 403. Seeded platform operators must have verified email and can see these links.

## Troubleshooting a 403 After Login

1. Confirm which user is currently signed in; browser sessions can keep a previous account active.
2. Check that the user is a member of the team selected by `TeamContext`.
3. Check `role_user` for the user's role in that team.
4. For tenant features, check `permission_role` and `permissions.slug`. For platform operations, check verified email and the user's `platform_administrators` row instead.
5. Do not remove the controller authorization check to make a page load. Assign the intended least-privilege team role or fix the default matrix/policy if it is wrong.
6. If a navigation link appears for a user who gets 403, treat it as a menu visibility defect; the route's 403 is expected enforcement until the link is hidden or the role is intentionally granted access.

## Database Changes

Created:
- `roles`
- `permissions`
- `role_user`
- `permission_role`

## Model Relationships

- `User` belongs to many `Role`
- `Role` belongs to `Team`, belongs to many `Permission`, and belongs to many `User`
- `Permission` belongs to many `Role`

## Folder Structure

- `app/Models/Role.php`
- `app/Models/Permission.php`
- `app/Policies/DocumentPolicy.php`
- `app/Policies/TeamPolicy.php`
- `app/Providers/AuthServiceProvider.php`
- `app/Actions/Team/CreateDefaultRolesAction.php`
- `database/migrations/0001_01_01_000007_create_roles_and_permissions_tables.php`
- `database/seeders/AuthorizationSeeder.php`
- `tests/Feature/Authorization/DocumentPolicyTest.php`

## Flow

1. A user registers and creates a personal team.
2. `CreateDefaultRolesAction` creates default tenant roles and grants permissions.
3. The user is assigned the `Owner` role.
4. `AuthServiceProvider` defines gates for each permission.
5. `DocumentPolicy` protects document access by permission and team membership.

## Testing Procedure

- Run `php artisan migrate --seed --class=DatabaseSeeder`
- Run `php artisan db:seed --class=AuthorizationSeeder`
- Run `./vendor/bin/phpunit --filter DocumentPolicyTest`
- Run `php artisan test --filter=InertiaSharedPropsTest` to verify only current-team permissions are sent to the frontend.
- Run `npm.cmd run build` to type-check the desktop, tablet, and mobile permission-filtered navigation.

Latest navigation-prop verification: `php artisan test --filter=InertiaSharedPropsTest` — 3 passed, 35 assertions; `npm.cmd run build` — passed; `npm.cmd run test:unit -- --run` — 3 files and 4 tests passed; Pint passed for the changed PHP files.

Platform-access verification: `php artisan test tests/Feature/PlatformAdministratorSeederTest.php tests/Feature/SystemHealthDashboardTest.php tests/Feature/AuditLogTest.php` — 10 passed, 39 assertions; targeted Pint check — passed. The local `PlatformAdministratorSeeder` ran successfully for the verified System Admin account, and the persisted database role is `super_admin`.

Platform navigation verification: `npm.cmd run test:unit -- --run resources/js/Layouts/AuthenticatedLayout.test.tsx` verifies platform administrators see only platform-operation links, while tenant navigation remains permission-based for other users. `npm.cmd run build` type-checks and builds the navigation.

## Troubleshooting

- If a `Gate` check fails, verify that the `permissions`, `roles`, and `role_user` records exist for the current tenant.
- Ensure `AuthServiceProvider` is loaded in `bootstrap/providers.php`.
- Confirm `User::hasPermission()` returns true for the expected permission slug.

## Maintenance

- Add new permission slugs to `Permission::defaultPermissions()`.
- Add new tenant roles to `Role::defaultRolePermissions()`.
- Map new policies in `AuthServiceProvider::$policies`.
- Update this permission matrix and route table whenever permission names, role grants, or route authorization changes.
- Add feature tests asserting both the allowed role and a denied role for every newly protected route.
