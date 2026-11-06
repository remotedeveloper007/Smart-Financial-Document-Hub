# Billing payment files

This page describes the purpose, dependencies, inputs, outputs, and usage of the files created for provider billing.

## Application files

| File | Purpose and dependencies | Inputs and outputs / usage |
|---|---|---|
| `app/Enums/BillingInterval.php` | Defines supported monthly/yearly intervals and centralizes period math and provider interval values. | Use the enum values in requests/actions; it provides end-date and provider API interval mappings. |
| `app/Http/Requests/Billing/StoreSubscriptionCheckoutRequest.php` | Requires the active-team `manage_billing` permission, then validates checkout plan, provider, billing interval, idempotency UUID, and whether a yearly price is available. | Non-billing members receive HTTP 403 before checkout data is processed. The controller passes authorized, validated data to the checkout action. |
| `app/Http/Requests/Billing/RefundPaymentRequest.php` | Validates refund reason and checks `manage_billing` permission. | The controller receives validated refund data only after authorization succeeds. |
| `app/Data/Billing/Money.php` | Converts decimal plan prices to/from provider minor units using currency precision. | Inputs: decimal amount and ISO currency. Output: integer minor units or a decimal provider amount string. |
| `app/Actions/Billing/CreateSubscriptionAction.php` | Retained for free-plan compatibility; blocks direct activation of paid plans. | Inputs: team/plan IDs and optional metadata. Output: a free subscription, or a clear exception for paid plans. Paid access must come from a verified webhook. |
| `app/Contracts/Billing/PaymentProvider.php` | Provider contract used by the manager and billing actions. | Checkout gets an attempt, plan, and user and returns provider references/URL. Webhook parsing returns a normalized event. Refund returns the provider response. |
| `app/Actions/Billing/StartSubscriptionCheckoutAction.php` | Serializes checkout creation using the team lock and reuses an existing idempotency key or in-flight attempt. Uses the models, interval, and manager. | Inputs: team, plan, provider, monthly/yearly interval, idempotency key, user. Output: `PaymentAttempt`, including its amount, interval, state, and checkout URL. |
| `app/Actions/Billing/ProcessPaymentWebhookAction.php` | Deduplicates verified events, verifies amount/currency, and applies payment and subscription transitions. | Input: provider key and normalized, signature-verified event. Output: `true` for first delivery or `false` for a duplicate. |
| `app/Actions/Billing/RefundPaymentAction.php` | Sends idempotent refunds only for successful payments and updates subscription status after processor acceptance. | Input: tenant, payment attempt, reason. Output: refunded `PaymentAttempt`; throws if the attempt is not successful or provider call fails. |
| `app/Services/Billing/PaymentProviderManager.php` | Resolves provider implementations registered by `AppServiceProvider`. | Input: provider key. Output: provider instance or configured provider list. |
| `app/Services/Billing/ProviderPlanCache.php` | Creates and caches remote recurring plan IDs by provider and interval while locking the local plan record. | Input: local plan, provider key, interval, provisioning callback. Output: remote provider plan ID. |
| `app/Services/Billing/Providers/StripePaymentProvider.php` | Stripe Checkout subscription API, Stripe HMAC webhook verification, and refunds. Uses Laravel HTTP client. | Input: plan/user/attempt or raw webhook request. Output: hosted checkout details or normalized event/refund result. |
| `app/Services/Billing/Providers/RazorpayPaymentProvider.php` | Razorpay recurring plan/subscription API, HMAC webhook verification, and refunds. | Input: plan/user/attempt or raw webhook request. Output: subscription checkout URL or normalized event/refund result. |
| `app/Services/Billing/Providers/PayPalPaymentProvider.php` | PayPal OAuth, recurring product/plan/subscription API, remote webhook verification, and refunds. | Input: plan/user/attempt or raw webhook request. Output: approval URL or normalized event/refund result. |
| `app/Http/Controllers/Billing/SubscriptionController.php` | Requires `manage_billing` before showing team billing history. Delegates checkout and refund work to actions; the checkout request also checks permission. | Authorized users receive tenant-scoped Inertia props or JSON responses; users without the permission receive HTTP 403. The generic plan catalog remains available to signed-in users. |
| `app/Http/Controllers/Billing/PaymentWebhookController.php` | Routes provider callbacks through signature verification and event processing. | Provider HTTP request; returns 200 on accepted/duplicate event, 401 on invalid signature, or 422 on invalid payment data. |
| `app/Models/PaymentAttempt.php` | Team-scoped payment record and relationships to plan/subscription. | Eloquent model for checkout, renewal, and refund history. |
| `app/Models/PaymentWebhookEvent.php` | Provider event idempotency ledger. | Eloquent model used to claim each event once. |
| `app/Providers/AppServiceProvider.php` | Registers and tags provider implementations for dependency injection. | Composition root only; add new provider registration here. |
| `config/services.php` | Reads payment API credentials, signing secrets, and API base URLs from server environment. | Configuration consumed by provider classes; no secrets are exposed to the UI. |
| `bootstrap/app.php` | Exempts only the external webhook path from browser CSRF verification. | Laravel middleware configuration; provider signatures still protect the webhook route. |
| `routes/web.php` | Defines authenticated billing routes and unauthenticated, signature-verified provider callback routes. | Maps HTTP paths to billing controller methods. |
| `resources/js/Pages/Billing/Plans.tsx` | Displays plans, configured gateways, subscriptions, attempts, checkout errors, and refund action. | Receives tenant-scoped Inertia props; submits checkout with a fresh idempotency UUID. |

## Database, tests, and docs

| File | Purpose and usage |
|---|---|
| `database/migrations/2026_11_04_000001_add_provider_billing_support.php` | Adds provider plan/subscription references, `payment_attempts`, and unique webhook event records. Run with `php artisan migrate`. |
| `database/migrations/2026_11_04_000002_add_plan_billing_intervals.php` | Adds optional `yearly_price` and records monthly/yearly interval on payments and subscriptions. |
| `tests/Feature/BillingTest.php` | Verifies permission-denied page and checkout requests, no payment side effect on denial, billing page data, idempotent checkout, signed and duplicate Stripe webhooks, mismatched amounts, payment failures, and refund eligibility. Run with `php artisan test --filter=BillingTest`. |
| `docs/features/12-billing-foundation.md` | Junior-friendly system, database, provider setup, payout/refund policy, test procedure, and troubleshooting guide. |
| `docs/directories/billing-payments.md` | This per-file directory reference. |
