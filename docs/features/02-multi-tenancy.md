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

1. The request enters the app.
2. `ResolveTeam` middleware clears any prior tenant context.
3. If the authenticated user belongs to a team, the middleware sets `TeamContext`.
4. Models using `BelongsToTeam` automatically apply the `BelongsToTeamScope`.
5. New business records are assigned `team_id` during creation.

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
