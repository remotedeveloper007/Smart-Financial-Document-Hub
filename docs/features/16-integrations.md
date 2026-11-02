# 16 - Integrations Foundation

Purpose
- Provide a foundation to connect third-party accounting providers (QuickBooks, Xero, Zoho).

Scope
- Model, migration, factory, controller, routes, UI list page, authorization, tests, and docs.

Database
- `integrations` table: `team_id`, `provider`, `external_id`, `credentials` (encrypted text), `status`.
- `2026_10_17_000001_encrypt_integration_credentials.php` converts existing plaintext JSON credentials to Laravel-encrypted JSON before the encrypted model cast is used. Back up the database and preserve the same `APP_KEY` before deploying this migration.

Credential handling
- `Integration` uses Laravel's encrypted-array-object cast for credentials at rest.
- `credentials` is hidden from Eloquent JSON serialization. API responses must not reveal access tokens, security codes, webhook secrets, or provider credentials.
- Partial settings updates merge submitted credential fields with existing values; omitted fields are retained. Submitted keys replace corresponding old values.

Permissions
- `manage_integrations` permission required to view/manage integrations.

How to test manually
- Create a team and a user with `manage_integrations` permission and visit `/integrations`.
- Expect to see configured integrations for that team.

Automated tests
- `tests/Feature/IntegrationFeatureTest.php` and `tests/Feature/Authorization/IntegrationPolicyTest.php`.

Verification checklist
- Integrations table exists and is tenant-scoped.
- `Integrations/Index` page accessible only with `manage_integrations` permission.
- Integrations listing returns only current team's integrations.
- Credentials are encrypted in the database and absent from JSON responses.
- OAuth callback tokens are saved through the encrypted cast.
- `composer audit` passed after the Composer dependency refresh (Laravel 13.37.0).

Latest focused verification
- Command: `php artisan test --filter='IntegrationFeatureTest|IntegrationPolicyTest|QuickBooksOAuthTest|XeroOAuthTest|ZohoBooksOAuthTest'`
- Result: 13 tests passed, 54 assertions (before final Composer refresh).
- Command: `php artisan test --filter='IntegrationChunkUploadTest|IntegrationChunkResumeTest'`
- Result: 2 tests passed, 11 assertions.
