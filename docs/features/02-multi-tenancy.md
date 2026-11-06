# 02 - Multi-Tenancy Foundation

## Purpose

Implement team-based tenancy to ensure tenant isolation from day one. This foundation prevents cross-team data access and sets the stage for secure document processing.

## Business Reason

Financial document handling must never expose data across teams. Multi-tenancy is a hard security boundary that protects customer records, documents, and billing.

## Architecture

- Team-based tenancy
- Tenant isolation by `team_id`
- Eloquent global scope for automatic filtering
- Middleware-based tenant context resolution
- Team membership via `team_user`
- Session-backed active-team selection, checked against the signed-in user's teams

## Database Changes

Created:
- `teams`
- `team_user`
- `team_id` on `users`

## Eloquent Models

- `App\Models\Team`
- `App\Models\Document`
- `App\Models\User` updates

## Files Created

- `app/Models/Team.php`
- `app/Models/Document.php`
- `app/Scopes/BelongsToTeamScope.php`
- `app/Traits/BelongsToTeam.php`
- `app/Services/Tenancy/TeamContext.php`
- `app/Http/Middleware/ResolveTeam.php`
- `app/Exceptions/TenantNotResolvedException.php`
- `database/migrations/0001_01_01_000003_create_teams_table.php`
- `database/migrations/0001_01_01_000004_create_team_user_table.php`
- `database/migrations/0001_01_01_000005_add_team_id_to_users_table.php`

## Flow

1. A signed-in request enters the web middleware, which can read the user's session.
2. Laravel starts the session and checks authentication.
3. `ResolveTeam` runs after authentication but before route model binding and authorization.
4. It gets the teams from the `team_user` membership relation and the user's primary `team_id`.
5. If the saved active team is still one of those teams, the middleware selects it. If not, it selects the user's primary team or first membership.
6. The middleware stores the valid selection in the session and sets `TeamContext` for this request.
7. Route model binding and policy checks now run with the selected tenant context available.
8. Models using `BelongsToTeam` automatically apply the `BelongsToTeamScope`.
9. New business records are assigned the active `team_id` during creation.
10. `HandleInertiaRequests` shares the active team and only the teams the signed-in user may select with the frontend.
11. The workspace selector sends the selected ID to `team.active.update`. The controller independently verifies primary-team or membership access before saving it in the session.
12. The next page request resolves the selected team and refreshes permissions and scoped data for that team.
13. A `finally` block clears the in-memory `TeamContext` after the request, including when resolution or request handling throws.

## Active-team selection

The active team is stored in the signed-in user's server-side session using
`active_team_id`. A browser cannot gain access by sending an arbitrary team ID:
the switch endpoint accepts only the user's primary team or a team in the
`team_user` membership list. A selection that is no longer valid is ignored on
the next request, and the middleware falls back to a team the user still
belongs to.

No database migration is needed. The selected team is a per-session preference,
not a new business record. A user with fewer than two available teams does not
see a selector.

## Tenant-aware model and relationship review

These models use `BelongsToTeam` and the shared `BelongsToTeamScope`:

- `AiRequest`
- `AuditLog`
- `Contract`
- `Document`
- `Integration`
- `Invoice`
- `PaymentAttempt`
- `Receipt`
- `Subscription`
- `TeamAiProviderSetting`
- `Transaction`

Tests check that every model in this list binds the active `team_id` in its
query. A tenant-scoped query with no resolved team raises
`TenantNotResolvedException`; it does not return all teams' records. When a
tenant model is created while a team context is active, the context's team ID
replaces a different ID supplied to the model.

The `document()` relationships on transactions, invoices, receipts, and
contracts point to the tenant-scoped `Document` model. A related document from
another team cannot be loaded in the active team's context. The database still
does not enforce that the parent's `team_id` and `document_id` belong to the
same team. Composite database integrity is reserved for M1.9.

Some models have a deliberate different access pattern:

- `Role` rows belong to teams but do not use the global scope. Permission
  checks, role assignment, and member-role output filter by the active or
  explicitly authorized team. Cross-team role output has a regression test.
  Keep this under review in M1.6, the role and permission audit.
- `FaultLog` is a platform-level diagnostic record. Platform admin operations
  can see these records, so the tenant scope is not applied.
- `PaymentWebhookEvent`, `Permission`, and `Plan` are provider/system-level
  records, not team business data.
- The system audit-log listing intentionally bypasses the model scope for the
  platform administrator and is protected by authorization.

The reviewed AI provider, document-processing, and integration job paths that
bypass global scopes add an explicit `team_id` condition. Keep any future
bypass narrow, documented, and paired with an ownership condition.

## Changed implementation files

- `routes/web.php` registers the authenticated `team.active.update` route.
- `app/Http/Controllers/Team/SwitchActiveTeamController.php` validates the requested ID, checks membership, and saves the session selection.
- `app/Http/Middleware/ResolveTeam.php` resolves the session choice and rejects stale or unauthorized values.
- `bootstrap/app.php` places team resolution after authentication and before model binding and authorization.
- `app/Http/Middleware/HandleInertiaRequests.php` shares only permitted teams and the active team.
- `resources/js/Layouts/AuthenticatedLayout.tsx` shows the selector on desktop and mobile and submits the choice through Inertia.
- `tests/Feature/TenantIsolationTest.php` checks valid switching, forged session state, and rejecting a non-member's team.
- `tests/Feature/TenantIsolationTest.php` checks all shared tenant scopes, missing-context failure, and protection from a forged create-time `team_id`.
- `resources/js/Layouts/AuthenticatedLayout.test.tsx` checks that the selector submits the selected team ID.

## Focused verification

Backend:

```powershell
$env:DB_CONNECTION='sqlite'; $env:DB_DATABASE=':memory:'; php artisan test --filter=TenantIsolationTest
```

Frontend:

```bash
npm run test:unit -- resources/js/Layouts/AuthenticatedLayout.test.tsx
npm run build
```

Verification run for active-team selection:

- PHP 8.3.14: `TenantIsolationTest` passed (7 tests, 20 assertions) using
  in-memory SQLite. The repository's supported PHP requirement is 8.4+, so
  this does not replace a supported-runtime CI run.
- Frontend layout tests: passed (5 tests).
- Frontend TypeScript check and production build: passed.
- The default backend test configuration targets MySQL, but the local MySQL
  service was not running. That MySQL run failed before tests could start.

Verification run for M1.3 request ordering:

- `php artisan route:list --name=team.active.update -vv` shows `Authenticate`,
  then `ResolveTeam`, then `SubstituteBindings`, then Inertia and security
  middleware.
- `php artisan test --filter=TenantIsolationTest` passed on the configured
  MySQL test database (8 tests, 23 assertions).
- The binding regression test confirms a Team A user can resolve Team A's
  document, while a Team B document ID returns 404 before the controller.
- PHP syntax checks and `git diff --check` passed.

Verification run for M1.4 model access:

- `php artisan test --filter="TenantIsolationTest|TeamUserManagementTest"`
  passed against configured MySQL (15 tests, 60 assertions).
- Checks cover all 11 models using the shared tenant scope, missing-context
  failure, active-team assignment on create, model binding, and team-specific
  role output.
- PHP syntax checks and `git diff --check` passed.

## M1.5 controller, route, and policy review

The authenticated business routes and their controllers were checked for
permission checks, tenant ownership checks, and tenant-scoped data access.
Integration operations use the integration policy and verify active-team
ownership. Document listing requires `view_documents`; opening the upload page
and submitting a file require `create_documents`. Refund requests require
`manage_billing`.

The review found that `/billing` returned the active team's subscription and
payment history to any signed-in member, and subscription checkout had no
permission check. The billing page now requires `manage_billing`, and the
checkout request denies users without that active-team permission before a
payment attempt or provider request can be created. The generic plan catalog
contains no team's private billing history and remains available to signed-in
users. The authorization guide now reflects the implementation: only the
default Owner role has `manage_billing`.

Verification:

- `php artisan test --filter=BillingTest` passed (19 tests, 110 assertions).
- `php artisan test --filter="BillingTest|TenantIsolationTest|TeamUserManagementTest|DocumentUploadTest"` passed (37 tests, 180 assertions).
- A regression test confirms a member without `manage_billing` receives 403
  from the billing page and checkout endpoint, with no payment attempt and no
  provider HTTP request.
- PHP syntax checks and Pint formatting checks passed for the changed PHP
  files. Route listing confirms billing routes require authentication and
  email verification; refund also has its explicit `manage_billing` middleware.
- These local tests ran with PHP 8.3.14. Run them again on the project's
  supported PHP 8.4+ runtime in CI before release.

## Testing Procedure

- Confirm local development schema is migrated: `php artisan migrate`.
- Run the full test suite: `php artisan test`.
- Confirm migration files are applied successfully.
- Create test teams and users using factories or a local seeder.
- Validate tenant isolation by checking that `Document::all()` returns only records for the current authenticated team.
- Validate global scope filters records to the current team.
- Confirm the `ResolveTeam` middleware sets the current team context for each request.


## Persisted test data for manual inspection

The PHPUnit configuration now writes test data to `database/database.sqlite` instead of `:memory:`. This means:

- Local test runs persist schema and final database state to `database/database.sqlite`.
- You can inspect the persisted SQLite file manually after running tests.
- To keep sample data available for review, use dedicated seeders or manual creation rather than relying on ephemeral test data created inside `RefreshDatabase` tests.

### Local inspection options

- Open `database/database.sqlite` with a SQLite browser such as DB Browser for SQLite, DBeaver, or TablePlus.
- Use Laravel Tinker:
  - `php artisan tinker`
  - `Team::with('members')->get();`
  - `Document::with('team')->get();`
- Create a local debug route or admin inspection view if needed for browser-based verification. Keep any debug route restricted to local or debug mode only.

## Migration to MySQL

1. Create the target MySQL database and user.
2. Update `.env`:
   - `DB_CONNECTION=mysql`
   - `DB_HOST=127.0.0.1`
   - `DB_PORT=3306`
   - `DB_DATABASE=your_mysql_db`
   - `DB_USERNAME=your_mysql_user`
   - `DB_PASSWORD=your_mysql_password`
3. Run schema migration:
   - `php artisan migrate`

### Migrating existing SQLite data to MySQL

- Option 1: Use seeders and factories to recreate the same sample data in MySQL.
- Option 2: Export SQLite data and import into MySQL using a tool:
  - `sqlite3 database/database.sqlite .dump > dump.sql`
  - Remove SQLite-specific pragmas and type declarations.
  - Import into MySQL with `mysql -u user -p database < dump.sql`.
- Option 3: Use a database migration utility such as DBeaver, DBConvert, or `sqlite3-to-mysql`.

## Migration to PostgreSQL

1. Create the target PostgreSQL database and user.
2. Update `.env`:
   - `DB_CONNECTION=pgsql`
   - `DB_HOST=127.0.0.1`
   - `DB_PORT=5432`
   - `DB_DATABASE=your_pg_db`
   - `DB_USERNAME=your_pg_user`
   - `DB_PASSWORD=your_pg_password`
3. Run schema migration:
   - `php artisan migrate`

### Migrating existing SQLite data to PostgreSQL

- Option 1: Rebuild sample data with Laravel seeders and factories on PostgreSQL.
- Option 2: Use `pgloader` to move data directly from SQLite to PostgreSQL:
  - `pgloader sqlite:///full/path/to/database/database.sqlite postgresql://user:pass@host/dbname`
- Option 3: Export and adjust SQL dump using a database tool like DBeaver.

## Troubleshooting

- If `TenantNotResolvedException` occurs, confirm the current request has a valid authenticated team user.
- Ensure `ResolveTeam` middleware is appended in `bootstrap/app.php`.

## Maintenance

- Add `BelongsToTeam` to any business model requiring tenant isolation.
- Add `team_id` columns and unique constraints to new tenant-aware tables.
