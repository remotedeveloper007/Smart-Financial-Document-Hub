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
| Manage billing (`manage_billing`) | Yes | **No** | No | Yes | No |
| Manage team (`manage_team`) | Yes | Yes | No | No | No |
| Manage accounting integrations (`manage_integrations`) | Yes | Yes | No | No | No |
| Use AI assistant (`use_ai`) | Yes | Yes | No | No | No |
| Configure team AI provider (`manage_ai_settings`) | Yes | Yes | No | No | No |
| View audit logs (`view_audit_logs`) | Yes | Yes | No | No | No |
| Admin operations / queue monitoring (`viewAdmin` gate) | Yes | Yes | No | No | No |

**Important:** this is the permission assignment matrix, not a promise that every product operation is complete. For example, the transaction export policy requires `export_transactions`, so only Accountant receives it by default. Owner and Admin do not currently have that permission despite their broad role labels. Avoid assuming a role name implies an unlisted permission.

## Pages and Actions by Role

| Workspace page or action | Required permission / rule | Default roles with access | What an allowed user can do |
|---|---|---|---|
| `/dashboard` | Authenticated and email verified | Any signed-in verified team user | Open the shared dashboard. |
| `/profile` | Authenticated | Any signed-in user | View/update their own profile and password. |
| `/team/members` and its member APIs | `manage_users` and current-team membership | Owner, Admin | List members, add members, change roles, remove members. |
| `/documents/upload`, document listing endpoint, and upload | `create_documents` | Owner, Admin, Manager | Open upload workflow, list documents through that workflow, and submit uploads. The current controller uses the create permission for its document list too. |
| `/transactions`, `/search`, transaction search API | `view_transactions` | Owner, Admin, Manager, Accountant, Viewer | View the current team's transaction list and search results. |
| `/transactions/export` | `export_transactions` | Accountant | Request a transaction export. |
| `/invoices` | `view_invoices` | All five default roles | View the current team's invoice list. |
| `/receipts` | `view_receipts` | All five default roles | View the current team's receipt list. |
| `/contracts` | `view_contracts` | All five default roles | View the current team's contract list. |
| `/integrations` and integration APIs | `manage_integrations` | Owner, Admin | Configure and manage accounting integrations, imports, and exports. |
| `/ai` and AI request APIs | `use_ai` | Owner, Admin | View AI request history and submit prompts when a provider is configured. |
| AI provider settings on `/ai` | `manage_ai_settings` | Owner, Admin | Select the team's AI provider/model and configure its team-owned credential. |
| `/audit/logs` | `view_audit_logs` | Owner, Admin | Read the current team's audit log. |
| `/queue/status`, `/admin/*`, Horizon | `viewAdmin` gate based on Owner/Admin role in current team | Owner, Admin | View operational queues and restricted admin health/fault screens. |
| `/billing` and subscription endpoints | Navigation uses `manage_billing`; controller does not currently enforce it | Owner, Accountant see the navigation tab | View plans and submit a subscription request. This is a local subscription foundation, not payment processing. Direct route access is not yet protected by this permission. |

### Role-by-role summary

- **Owner:** assigned automatically to a newly registered user for their new team. Has all permissions listed in the matrix except `export_transactions`, which is currently missing from the default Owner grant. Can manage users/team/integrations/AI settings, use AI, view audit logs, and access admin operations.
- **Admin:** broad operational role: can manage team members/team, integrations and AI settings; use AI; view financial records and audit logs; and access admin operations. Does not receive `manage_billing` or `export_transactions` by default.
- **Manager:** can work with documents (create/edit/export), view invoices/receipts/contracts/transactions, search, but cannot manage members, integrations, billing, AI, audit logs, or admin operations.
- **Accountant:** can view invoices/receipts/contracts/transactions, export documents and transactions, and has the `manage_billing` permission. Cannot manage team members, team, integrations, AI provider settings, audit logs, or admin operations. The current billing controller does not yet enforce that billing permission.
- **Viewer:** read-only permissions for documents, invoices, receipts, contracts, and transactions/search. The current documents page is gated by `create_documents`, so Viewer does not get the upload/document page through that route despite having `view_documents`; this is a route/policy gap to address before advertising a standalone document read page.

## Manual Verification After Login

Use the local demo accounts above in a local/testing environment only. Sign out between account changes so the browser session does not reuse the previous user.

1. Sign in at `/login`; confirm the account lands on `/dashboard`.
2. As Admin, verify Team Members, Documents, Transactions, Search, Invoices, Receipts, Contracts, Integrations, AI Assistant, Audit Logs, and admin links are visible. As Manager, verify only Dashboard, Documents, Transactions, Search, Invoices, Receipts, and Contracts are visible.
3. As Accountant, verify Dashboard, Transactions, Search, Invoices, Receipts, Contracts, and Billing are visible; Team Members, Documents, Integrations, AI Assistant, and Audit Logs are not. Document export permission does not currently have its own navigation tab.
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

### Existing local database repair and verification

The local database used during the navigation issue had default role records but no `permission_role` grants for the default roles. `viewAdmin` checks the role name directly, so an Admin could see the Admin section while the feature tabs stayed hidden because `can.permissions` was empty. This was fixed in two ways:

- `CreateDefaultRolesAction` now creates missing permission records and assigns role grants using permission IDs selected by slug. A registration regression test confirms a new Owner receives permissions without a prior manual seed.
- `php artisan db:seed --class=AuthorizationSeeder --force` was run locally to synchronize role grants for existing teams. The post-seed query showed permissions attached to Owner, Admin, Manager, Accountant, and Viewer role records.

After repairing an existing database, hard-refresh the browser (Ctrl+F5) or sign out and back in so the page reloads the updated Vite asset and fresh Inertia permission props. If a link is still missing, verify that the signed-in user actually has the expected role in the active team; the authorization seeder updates role grants but does not grant users roles.

Focused verification after the fix: `php artisan test --filter="RegistrationTest|InertiaSharedPropsTest|DatabaseSeederSafetyTest"` — 7 passed, 55 assertions; Pint passed for the changed PHP files. The permission-filtered navigation production build and frontend unit tests passed in the preceding navigation change.

## Troubleshooting a 403 After Login

1. Confirm which user is currently signed in; browser sessions can keep a previous account active.
2. Check that the user is a member of the team selected by `TeamContext`.
3. Check `role_user` for the user's role in that team.
4. Check `permission_role` and `permissions.slug` for the permission required by that route.
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
