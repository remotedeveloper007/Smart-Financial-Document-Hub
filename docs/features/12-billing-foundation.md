# Billing, Payments, and Subscriptions

## Purpose

Give billing users a working plans page and safe monthly/yearly checkouts with Stripe, Razorpay, or PayPal. Each plan can set a monthly price and an optional yearly price. Payment attempts are stored separately from subscriptions. A subscription becomes active only after a provider confirms a successful payment.

The proposed tier structure, quota assumptions, free-trial expiry, and AI cost policy are documented in [Subscription Plan Proposal](./13-subscription-plan-proposal.md). That proposal is not implemented or a source of active entitlements until approved.

## Business reason

The earlier page silently swallowed plan-loading errors and directly marked a subscription active without charging a customer. This implementation uses provider checkout, verifiable webhooks, idempotency, and a visible payment history instead.

## Architecture

- `PaymentProvider` defines checkout, webhook parsing, and refund operations.
- `PaymentProviderManager` resolves a tagged provider. Each gateway is an injectable implementation; a new gateway can be added without putting provider API calls in controllers.
- `StartSubscriptionCheckoutAction` serializes checkout creation on the team row, reuses an in-flight attempt, and sends a stable provider idempotency key.
- Each provider verifies its webhook before `ProcessPaymentWebhookAction` changes records.
- `payment_webhook_events` has a unique `(provider, event_id)` key. Duplicate deliveries are acknowledged without applying state twice.
- `BillingInterval` is the closed set of supported renewal periods (`monthly`, `yearly`). Form Requests validate plan, provider, interval, idempotency key, refund reason, and refund authorization before actions run.
- Payment-attempt status and subscription status are separate. A failed renewal can make a subscription `past_due`, but it cannot turn an already succeeded payment attempt into a failed payment.
- Refunds require a successful captured payment, are sent using a provider idempotency key, and cancel the matching subscription after the provider accepts them.

## Subscription period and payout policy

- Paid plans can renew monthly or yearly. Each `Plan` stores the monthly `price` and an optional `yearly_price`; the yearly option appears only when that price is set. A zero-priced plan can be selected for either interval and expires at the selected period end.
- Stripe and PayPal use the selected recurring period. Razorpay recurring plans use the matching monthly or yearly interval and are created with a maximum of 120 charges; an owner must renew/re-authorize after that provider limit.
- The payment processor settles funds to the platform's merchant account under that merchant account's settlement schedule. The application does not initiate transfers, split funds, or pay out to tenant teams. Do not use this feature as a marketplace payout system.
- Only captured/succeeded charges may be refunded. A failed or pending payment is not a refund; it remains a failed/pending payment attempt. Refunds are issued through the originating payment provider, not by changing a local status alone.
- Provider settlement, merchant onboarding, and tax handling remain configured in the Stripe, Razorpay, and PayPal dashboards.

## Database changes

The billing migrations add:

- `plans.provider_plan_ids`: provider recurring-plan IDs provisioned and cached by provider and interval; `plans.yearly_price` stores the optional yearly price.
- `subscriptions.provider`, `provider_subscription_id`, `billing_interval`, `current_period_start`, `current_period_end`, and `canceled_at`.
- `payment_attempts`: team and plan, independent status, provider references, billing interval, idempotency key, amount in minor units, currency, checkout URL, failure reason, and refund timestamp.
- `payment_webhook_events`: provider event ID/type, processed status, and associated payment attempt. Unique provider/event keys enforce deduplication.

Existing plan and subscription rows are preserved. No payment credentials or raw webhook bodies are stored in the database.

## Model relationships

- `Plan` is the catalog entry. It keeps existing names such as Starter and Pro in database rows; this change does not invent tiers or prices. Set `yearly_price` for each tier that should offer annual billing. Its `provider_plan_ids` JSON value caches remote recurring IDs by provider and period.
- `Subscription` belongs to a `Team` through `team_id`, belongs to a `Plan`, and has many `PaymentAttempt` rows. Its `billing_interval` describes the entitlement renewal period; it does not represent an individual charge.
- `PaymentAttempt` belongs to a `Team`, `Plan`, and optionally a `Subscription`. Its tenant global scope limits billing history to the resolved team. Each checkout or renewal charge gets an independent record, including its billing interval.
- `PaymentWebhookEvent` is a provider-level dedupe ledger. It contains no raw payload or card/payment credentials.
- `Team` is locked while a checkout is created or a webhook changes a subscription, preventing parallel requests from creating duplicate charges or subscriptions.

## Folder structure and files

- `app/Contracts/Billing/PaymentProvider.php`
- `app/Actions/Billing/StartSubscriptionCheckoutAction.php`
- `app/Actions/Billing/ProcessPaymentWebhookAction.php`
- `app/Actions/Billing/RefundPaymentAction.php`
- `app/Enums/BillingInterval.php`
- `app/Http/Requests/Billing/StoreSubscriptionCheckoutRequest.php`
- `app/Http/Requests/Billing/RefundPaymentRequest.php`
- `app/Services/Billing/PaymentProviderManager.php`
- `app/Services/Billing/ProviderPlanCache.php`
- `app/Services/Billing/Providers/StripePaymentProvider.php`
- `app/Services/Billing/Providers/RazorpayPaymentProvider.php`
- `app/Services/Billing/Providers/PayPalPaymentProvider.php`
- `app/Http/Controllers/Billing/SubscriptionController.php`
- `app/Http/Controllers/Billing/PaymentWebhookController.php`
- `app/Models/PaymentAttempt.php`
- `app/Models/PaymentWebhookEvent.php`
- `database/migrations/2026_11_04_000001_add_provider_billing_support.php`
- `database/migrations/2026_11_04_000002_add_plan_billing_intervals.php`
- `resources/js/Pages/Billing/Plans.tsx`
- `tests/Feature/BillingTest.php`
- `docs/directories/billing-payments.md`

## Flow

```text
Billing page -> choose plan/period/provider -> idempotent checkout request
  -> persist payment_attempt (creating) -> provider recurring checkout
  -> persist provider IDs/checkout URL (pending) -> customer approves/pays
  -> signed provider webhook -> unique event claim -> amount/currency check
  -> payment attempt succeeds -> subscription becomes active
```

Failed provider events update the attempt to `failed`; they do not activate a new subscription. Provider errors are returned to the user and recorded as failed attempts. Duplicate provider events do not repeat subscription changes.

## Configuration and provider webhooks

Put sandbox credentials only in the server's environment file or secret manager. Do not put secret values in Git, React props, logs, or chat. See `.env.example` for the variable names:

- Stripe: `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`
- Razorpay: `RAZORPAY_KEY_ID`, `RAZORPAY_KEY_SECRET`, `RAZORPAY_WEBHOOK_SECRET`
- PayPal: `PAYPAL_CLIENT_ID`, `PAYPAL_CLIENT_SECRET`, `PAYPAL_WEBHOOK_ID`
- Optional API base URLs are provided for sandbox or regional endpoints.

Register these webhook paths with the matching sandbox account:

- `POST /billing/webhooks/stripe`
- `POST /billing/webhooks/razorpay`
- `POST /billing/webhooks/paypal`

The webhook route is exempted from CSRF because provider servers do not have a browser session. It is not exempted from signature verification. Configure a public HTTPS endpoint (or a provider-approved local webhook tunnel) before testing external delivery.

## File and database commands

Commands for reproducing the project setup:

```bash
php artisan make:migration add_provider_billing_support
php artisan make:migration add_plan_billing_intervals
php artisan make:model PaymentAttempt
php artisan make:model PaymentWebhookEvent
php artisan make:controller Billing/PaymentWebhookController
php artisan make:request Billing/StoreSubscriptionCheckoutRequest
php artisan make:request Billing/RefundPaymentRequest
php artisan migrate
php artisan test --filter=BillingTest
npm run build
```

No payment SDK or JavaScript package was added; Laravel's HTTP client is used for provider APIs. Run `php artisan migrate:rollback --step=1` only when rolling back this migration in a development database and after confirming it is the latest migration.

## Testing procedure

Automated tests use fake provider responses and locally generated webhook signatures; they do not charge a real card. They verify billing page props, Form Request validation, monthly/yearly price and provider interval selection, checkout idempotency, duplicate webhook behavior, failed-payment handling, amount validation, and refund guards.

### Results

- `php artisan test --filter=test_free_plan_expires_after_the_selected_billing_period` — passed: 1 test, 9 assertions.
- `php artisan test tests/Feature/BillingTest.php` — passed: 17 tests, 101 assertions.
- `npm.cmd run build` — passed: TypeScript check and Vite production build.
- `vendor/bin/pint --test app/Actions/Billing/StartSubscriptionCheckoutAction.php tests/Feature/BillingTest.php` — passed.

### Manual sandbox check

1. Configure one provider's sandbox credentials and its signing/webhook ID.
2. Run migrations and start Laravel plus the queue/web server.
3. Use a user with billing access and open `/billing`.
4. Choose a plan, select monthly or yearly, and complete sandbox checkout. Wait for the signed webhook.
5. Confirm the attempt is `succeeded` and one subscription is `active`. Deliver the same webhook again; the result must be a duplicate with no second subscription.
6. Try a declined sandbox payment. Confirm a failed attempt appears and the subscription is not activated.
7. Request a refund for a successful payment as a user with `manage_billing`; confirm the processor dashboard and local payment/subscription statuses.

## Troubleshooting

- **No providers are listed:** check that the corresponding sandbox credentials and webhook secret/ID are set, then run `php artisan config:clear`.
- **Checkout says the provider is unavailable:** verify all required server environment values without copying their secrets into logs.
- **Provider accepts checkout but page stays pending:** the webhook URL must be publicly reachable over HTTPS and match the configured signing secret/webhook ID.
- **Webhook returns 401:** verify signature secret, raw request-body forwarding, server clock, and the provider endpoint configuration.
- **Webhook returns 422:** compare the event amount and currency with the stored attempt; do not bypass the check.
- **Repeated submit returns an existing checkout:** this prevents an extra payment request. Continue that checkout or wait for its provider event before retrying.
- **Yearly option is disabled:** set the plan's `yearly_price` after choosing the correct price. Existing plans do not receive guessed annual prices.
- **Razorpay or PayPal reports an existing cached plan ID as invalid:** the cached ID belongs to another provider environment/account. Clear that provider's ID from `plans.provider_plan_ids` only after checking no active subscription depends on it.
- **Refund fails:** the local attempt stays succeeded and records the error; fix the provider-side issue and retry. A failed/pending attempt cannot be refunded.

## Maintenance guide

- Keep all provider HTTP calls and signature verification inside their provider implementations.
- Never activate subscriptions from a browser redirect; only signed provider events confirm payment.
- Preserve provider/event uniqueness and team/idempotency uniqueness in migrations.
- Keep credentials in server configuration. Rotate webhook secrets in the provider and environment together.
- If changing plan price/currency, create a new plan slug or explicitly migrate existing provider plan IDs; provider recurring plans keep the amount they were created with.
- Check provider API versions and sandbox webhook event formats before upgrading a gateway.
- Provider-managed settlement is the only supported payout route. Any future marketplace payout feature must be designed separately with verified recipient onboarding, compliance, and reconciliation.
