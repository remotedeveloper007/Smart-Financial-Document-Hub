# 12 - Billing Foundation

## Purpose

Provide a minimal billing foundation with plans and tenant subscriptions. This is the groundwork for integrating payment providers and usage metering.

## Business Reason

Billing foundation enables subscription-based monetization and attaches usage to tenants.

## Architecture

- `Plan` model: defines available subscription plans.
- `Subscription` model: tenant-scoped subscriptions.
- `CreateSubscriptionAction`: idempotent subscription creation.
- `SendSubscriptionCreatedJob`: placeholder job for post-processing.
- `SubscriptionController`: thin controller exposing `plans` and `store` endpoints.
- Frontend: `resources/js/Pages/Billing/Plans.tsx` allows subscribing.

## Folder Structure

- `app/Models/Plan.php`
- `app/Models/Subscription.php`
- `app/Actions/Billing/CreateSubscriptionAction.php`
- `app/Jobs/SendSubscriptionCreatedJob.php`
- `app/Http/Controllers/Billing/SubscriptionController.php`
- `database/migrations/*_create_plans_table.php`
- `database/migrations/*_create_subscriptions_table.php`
- `resources/js/Pages/Billing/Plans.tsx`
- `tests/Feature/BillingTest.php`

## Database Changes

- `plans` table: `slug`, `name`, `price`, `currency`, `features`.
- `subscriptions` table: `team_id`, `plan_id`, `status`, `starts_at`, `ends_at`, `meta`.

## Testing Procedure

### Manual UI test

1. Login as a user belonging to a team.
2. Visit `/billing/plans` (frontend route depends on app nav).
3. Click `Subscribe` on a plan.
4. Confirm a success message and that a subscription record exists for your team.

### Commands

- `php artisan migrate`
- `php artisan test --filter=BillingTest`

## Tests included

- `tests/Feature/BillingTest.php`

## Troubleshooting

- If subscription creation fails, verify `plan_id` exists and user has a resolved team.
- Ensure background jobs are processed for post-subscription tasks.

## Todo

- [ ] Integrate Stripe/PayPal provider.
- [ ] Add billing history and invoices.
- [ ] Add cancellation, trial periods, proration.
- [ ] Add usage metering and limits.
