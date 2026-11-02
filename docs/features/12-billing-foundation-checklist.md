# 12 - Billing Foundation Checklist

Why this checklist wasn't previously created:
- The billing foundation implementation included models, migrations, an action, a job, controller, frontend page and feature tests. During iterative fixes (controller validation and job wiring) the explicit checklist file was not added — this file documents verification steps and test outputs.

Verification checklist:
- [x] `plans` and `subscriptions` migrations exist and migrate successfully.
- [x] `Plan` and `Subscription` models exist and are multi-tenant aware (`team_id` present where applicable).
- [x] `CreateSubscriptionAction` performs idempotent `updateOrCreate` behavior.
- [x] `SendSubscriptionCreatedJob` is dispatched after subscription creation.
- [x] `SubscriptionController::store` validates input using `$request->validate()` and returns 201.
- [x] Feature tests for billing are present and passing. See `tests/Feature/BillingTest.php`.

How to run tests (backend):
1. Ensure `.env.testing` uses MySQL (not SQLite) as required by project standards.
2. Run:
```
php artisan test --filter=BillingTest
```

Manual test steps (frontend):
1. Login as a user who is Owner or Admin for a team.
2. Navigate to Billing → Plans.
3. Click Subscribe on a plan.
4. Expect a 201 response and a subscription record associated with your team.

Expected outputs and notes:
- The `subscriptions` table should contain `team_id`, `plan_id`, `status`, and timestamps.
- Jobs are queued (verify with `php artisan queue:work --once` or Horizon).

Troubleshooting:
- If tests fail with validation errors: ensure controller uses `$request->validate()` (fixed in codebase).
- If `SendSubscriptionCreatedJob` not dispatched: verify `CreateSubscriptionAction` calls `dispatch()` and queue worker is running.

Recorded test results:
- `BillingTest` — passed (single test run; assertions: 4). Full test output is stored in `docs/features/12-billing-foundation.md`.

Todos:
- Integrate payment provider (Stripe) and webhook handling.
- Add subscription cancellation and invoice history.
