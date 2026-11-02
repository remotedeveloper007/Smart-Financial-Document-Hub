# 16 - Integrations Settings

## 1. Purpose

Provide a tenant-scoped integration settings screen that lets a team manage connected accounting providers such as QuickBooks, Xero, and Zoho. The setup uses standard Laravel policies, route validation, and a small Inertia page so the system remains secure and maintainable.

## 2. Business Reason

Teams need a place to register provider credentials, connect an account ID, and enable or disable the connection without exposing other tenants' data. This is a foundational requirement for later accounting sync features.

## 3. Architecture

- `Integration` model stores provider metadata and encrypted/serialized credentials.
- `IntegrationController` handles page rendering plus CRUD API actions.
- `IntegrationPolicy` enforces permission and tenant isolation.
- `routes/web.php` exposes page, list, create, update, delete endpoints.
- `resources/js/Pages/Integrations/Index.tsx` renders the settings form and list.
- The `BelongsToTeam` global scope ensures every integration is automatically scoped to the active team.

## 4. Database Changes

Table: `integrations`

Columns:
- `id`
- `team_id`
- `provider`
- `external_id`
- `credentials` (JSON)
- `status`
- `created_at`
- `updated_at`

Constraints:
- `team_id` foreign key to teams.
- Unique combination of `team_id` + `provider`.

## 5. Relationship Explanation

- `Integration -> Team` : each integration belongs to one team.
- `Team -> Integration` : each team can have many integrations.
- The `BelongsToTeam` trait applies the tenant scope so reads and writes remain isolated.

## 6. Folder Structure

- `app/Models/Integration.php`
- `app/Policies/IntegrationPolicy.php`
- `app/Http/Controllers/Integrations/IntegrationController.php`
- `routes/web.php`
- `resources/js/Pages/Integrations/Index.tsx`
- `tests/Feature/IntegrationFeatureTest.php`
- `tests/Feature/Authorization/IntegrationPolicyTest.php`

## 7. File Paths

- [app/Models/Integration.php](../..//app/Models/Integration.php)
- [app/Policies/IntegrationPolicy.php](../..//app/Policies/IntegrationPolicy.php)
- [app/Http/Controllers/Integrations/IntegrationController.php](../..//app/Http/Controllers/Integrations/IntegrationController.php)
- [routes/web.php](../..//routes/web.php)
- [resources/js/Pages/Integrations/Index.tsx](../..//resources/js/Pages/Integrations/Index.tsx)

## 8. Flow Diagram

1. User opens `/integrations`.
2. Controller authorizes `viewAny, create, update, delete` against the integration policy.
3. Page loads and fetches team-scoped integrations via `GET /integrations/list`.
4. User submits provider form.
5. Controller validates provider + team uniqueness.
6. Integration record is created and persisted with the active team.
7. Delete route removes only current team records.

## 9. Testing Procedure

### Automated tests

```bash
cd "c:\Users\Mazhar\Documents\github.com\remotedeveloper007\Smart Financial Document Hub"
php artisan test --filter=IntegrationFeatureTest
php artisan test --filter=IntegrationPolicyTest
```

### Manual test

1. Log in as a user with `manage_integrations` permission.
2. Open `/integrations`.
3. Add a QuickBooks connection.
4. Refresh the list and confirm the record appears.
5. Delete the integration and verify it disappears from the list.

## 10. Troubleshooting Guide

- If route creation fails, ensure the route names match `integrations.store`, `integrations.update`, and `integrations.destroy`.
- If records are visible across teams, check the `BelongsToTeam` global scope and the team_id comparisons in the controller.
- If permission checks fail, confirm the role includes `manage_integrations` and the user belongs to the same team.

## 11. Maintenance Guide

- Store provider credentials using a secure encrypted backend pattern before production deployment.
- Keep provider names normalized (e.g., `quickbooks`, `xero`, `zoho`).
- Add provider-specific validation classes as more connectors are introduced.

## 12. Artisan Commands Used

```bash
php artisan make:model Integration -m
php artisan make:controller Integrations/IntegrationController
php artisan make:policy IntegrationPolicy
php artisan test --filter=IntegrationFeatureTest
```

## 13. Verification Results

Command run:
```bash
cd "c:\Users\Mazhar\Documents\github.com\remotedeveloper007\Smart Financial Document Hub" && php artisan test --filter=IntegrationFeatureTest
```

Result:
- 4 tests passed
- 12 assertions
- Exit code 0

## 14. Notes

This feature completes the tenant-scoped integration settings foundation, which is ready for the next roadmap item after approval.
