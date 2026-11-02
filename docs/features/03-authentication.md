# 03 - Authentication

## Purpose

Implement user registration, login, logout, password reset, and email verification using Laravel Breeze with Inertia, React, and TypeScript.

## Business Reason

Authentication is required before users can securely access tenant-specific dashboards and document workflows. This ensures only authorized team members can interact with financial records.

## Architecture

- Laravel Breeze for authentication controllers and routes
- Inertia.js with React and TypeScript for frontend pages
- `User` model implements email verification
- Team creation during registration for tenant isolation
- Global middleware resolves the user's current team context
- Validation handled by `App\Http\Requests\Auth\RegisterUserRequest`
- Business logic handled by `App\Actions\Auth\RegisterUserAction`

## Database Changes

No new tables were introduced by authentication. Existing tables used:
- `users`
- `teams`
- `team_user`

## Eloquent Relationship Details

- `App\Models\User`
  - `team()`: belongsTo `Team`
  - `teams()`: belongsToMany `Team`
  - implements `MustVerifyEmail`

- `App\Models\Team`
  - `members()`: belongsToMany `User`

## Folder Structure

- `app/Http/Controllers/Auth/`
- `app/Http/Requests/Auth/`
- `resources/js/Pages/Auth/`
- `tests/Feature/Auth/`
- `docs/features/03-authentication.md`

## File Paths

- `app/Actions/Auth/RegisterUserAction.php`
- `app/Http/Controllers/Auth/RegisteredUserController.php`
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `app/Http/Requests/Auth/RegisterUserRequest.php`
- `app/Http/Requests/Auth/LoginRequest.php`
- `routes/auth.php`
- `resources/js/Pages/Auth/*.tsx`
- `database/factories/UserFactory.php`
- `tests/Feature/Auth/*.php`

## Flow Diagram

1. User visits `/register` or `/login`.
2. Inertia renders the frontend page.
3. `RegisterUserRequest` validates the registration input.
4. `RegisteredUserController` delegates the registration workflow to `RegisterUserAction`.
5. Registered users are assigned a personal team and added to the team membership table.
6. Authenticated users are redirected to `/dashboard`.

## After Login: Workspace Access

The `/dashboard` route is the shared landing page for authenticated, email-verified users. It is not a role assignment screen and does not mean every feature is available. The authenticated layout receives permission slugs for the active team and displays only matching feature tabs on desktop, tablet, and mobile. Controllers and policies still check permissions on every protected route; hiding a tab is not a security control. A user who lacks a route's permission receives HTTP 403 if they open it directly.

New self-registered users are assigned the **Owner** role for their newly created team. The local demo seeder creates separate **Admin**, **Manager**, **Accountant**, and **Viewer** accounts and assigns each the matching role. The exact feature-by-feature permission matrix, route permissions, and allowed actions are maintained in [04 - Authorization Foundation](./04-authorization.md#default-role-permission-matrix). Refer to that table before changing a role or troubleshooting a 403.

## Testing Procedure

- Run migrations: `php artisan migrate`
- Run tests: `php artisan test`
- Confirm auth pages load with `GET /login`, `GET /register`, `GET /forgot-password`
- Confirm a new registration creates a team and team membership
- Confirm login works with valid credentials
- Confirm password reset flow sends a reset notification
- Confirm email verification can be completed with a signed URL
- Confirm registration validation is handled by `RegisterUserRequest`
- Confirm business logic is handled by `RegisterUserAction`
- Confirm user passwords are stored hashed in the database

## Checklist

- [x] Thin controller, no local validation in `RegisteredUserController`
- [x] `RegisterUserRequest` handles validation rules
- [x] `RegisterUserAction` handles registration business logic
- [x] Personal team is created during registration
- [x] Team membership pivot is persisted
- [x] Password storage uses hashing
- [x] Feature tests cover registration and validation failures

## Test Results

- `bash php artisan test --filter="RegistrationTest"`

```bash
PHPUnit 12.5.30 by Sebastian Bergmann and contributors.

....                                                                4 / 4 (100%)

Time: 00:00.024, Memory: 24.00 MB

OK (4 tests, 16 assertions)
```

- `bash ./vendor/bin/phpunit --filter Auth`

```bash
PHPUnit 12.5.30 by Sebastian Bergmann and contributors.

.....                                                              21 / 21 (100%)

Time: 00:00.014, Memory: 32.00 MB

OK (21 tests, 51 assertions)
```

## Troubleshooting

- If auth routes return 404, ensure `routes/auth.php` is included from `routes/web.php`.
- If password reset emails don't send during tests, verify `Notification::fake()` is used in the test.
- If registration doesn't persist `team_id`, verify `Team::create()` and user association logic in `RegisteredUserController`.
- If validation rules change, update `RegisterUserRequest` rather than the controller.

## Maintenance

- Add new auth-related features in `app/Http/Controllers/Auth/`
- Keep frontend auth pages in `resources/js/Pages/Auth/`
- Update tests in `tests/Feature/Auth/`

## Artisan CLI Commands

- Install Breeze React: `php artisan breeze:install react --typescript`
- Run migrations: `php artisan migrate`
- Create test database: `touch database/database.sqlite`
- Create auth request: `php artisan make:request Auth/RegisterUserRequest`
- Create auth action: `php artisan make:action Auth/RegisterUserAction`
- Create auth action: `php artisan make:action Auth/RegisterUserAction`

## Test Results

- `php artisan test` - passed

## Local Demo Account Safety

`DatabaseSeeder` creates synthetic admin, manager, accountant, and viewer accounts with the local-only password `password`, and assigns each account its matching team role. It refuses to run unless the application environment is `local` or `testing`, so a production `db:seed` cannot create these predictable accounts. The backend still enforces role permissions; seeing a navigation link does not grant access.

Run this seeder only in a local or testing database:

```bash
php artisan db:seed
```

The AI tester account and synthetic AI request history are separate and use `AiDemoSeeder`; see [AI Assistant Processing Frontend](./17-ai-assistant-processing-frontend.md#local-mysql-synthetic-data).

## Latest Seeder Safety Test

`tests/Feature/DatabaseSeederSafetyTest.php` verifies that the default demo-account seeder throws before writing in production and that each local demo account receives its matching team role. `CreateDefaultRolesAction` creates missing permission catalog entries before creating role grants, so fresh registrations receive working permissions without requiring a previous manual seeder run. On an existing local database, run `php artisan db:seed --class=AuthorizationSeeder` to synchronize default roles and grants. This does not assign a role to an existing user; assign the appropriate team role to that user separately. Do not grant Owner/Admin to a real account unless approved. The latest test counts are recorded in [Production Readiness Remediation](./production-readiness-remediation.md#reverification-after-the-follow-up-fixes).

Latest focused verification after the role-assignment fix: `php artisan test --filter=DatabaseSeederSafetyTest` — 2 passed, 10 assertions; Pint passed for the seeder and test.
