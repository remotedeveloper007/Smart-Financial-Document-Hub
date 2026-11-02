# 15 - Contracts processing (frontend)

Pages
- `resources/js/Pages/Contracts/Index.tsx` — lists extracted contracts with pagination.

Navigation
- Added `Contracts` link to `resources/js/Layouts/AuthenticatedLayout.tsx` (desktop & mobile).

Permissions
- Page requires `view_contracts` permission; server-side authorization enforced in `ContractController::page()`.

Testing
- Manual: visit `/contracts` as a user with `view_contracts` permission.
- Automated: `tests/Feature/ContractFeatureTest.php` and `tests/Feature/Authorization/ContractPolicyTest.php`.
