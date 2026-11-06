<?php

namespace Tests\Feature;

use App\Actions\Billing\CreateSubscriptionAction;
use App\Actions\Billing\RefundPaymentAction;
use App\Models\PaymentAttempt;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\Team;
use App\Models\User;
use App\Services\Billing\PaymentProviderManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private User $user;

    private Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.payments.stripe.secret' => 'sk_test_local',
            'services.payments.stripe.webhook_secret' => 'whsec_local',
            'services.payments.stripe.base_url' => 'https://api.stripe.com',
        ]);
        app()->forgetInstance(PaymentProviderManager::class);
        Http::fake([
            'https://api.stripe.com/v1/checkout/sessions' => Http::response([
                'id' => 'cs_test_checkout',
                'url' => 'https://checkout.stripe.test/session',
                'subscription' => 'sub_test_subscription',
            ]),
        ]);

        $this->team = Team::factory()->create(['id' => random_int(1_000_000, 2_000_000_000)]);
        $manageBilling = Permission::firstOrCreate(
            ['slug' => Permission::PERMISSION_MANAGE_BILLING],
            ['name' => 'Manage billing'],
        );
        $billingRole = Role::create([
            'team_id' => $this->team->id,
            'name' => 'Billing manager',
            'slug' => 'billing-manager',
        ]);
        $billingRole->permissions()->attach($manageBilling);

        $this->user = User::factory()->create(['team_id' => $this->team->id]);
        $this->user->roles()->attach($billingRole);
        $this->plan = Plan::create([
            'slug' => 'pro',
            'name' => 'Pro',
            'description' => 'Monthly plan',
            'price' => 49.00,
            'yearly_price' => 490.00,
            'currency' => 'USD',
        ]);
        $this->actingAs($this->user);
    }

    public function test_billing_page_has_plan_subscription_and_attempt_props(): void
    {
        $response = $this->get(route('billing.page'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Billing/Plans')
                ->has('plans', 1)
                ->where('providers', ['stripe'])
                ->has('subscriptions', 0)
                ->has('paymentAttempts', 0));
    }

    public function test_non_billing_team_member_cannot_view_billing_or_start_checkout(): void
    {
        $member = User::factory()->create(['team_id' => $this->team->id]);
        $this->actingAs($member);

        $this->get(route('billing.page'))->assertForbidden();

        $this->withHeader('Idempotency-Key', 'c0a80123-4567-4890-a123-456789abcdef')
            ->postJson(route('billing.subscriptions.store'), [
                'plan_id' => $this->plan->id,
                'provider' => 'stripe',
                'billing_interval' => 'monthly',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('payment_attempts', 0);
        Http::assertNothingSent();
    }

    public function test_checkout_starts_one_monthly_payment_attempt_and_is_idempotent(): void
    {
        $key = 'c0a80123-4567-4890-a123-456789abcdef';
        $response = $this->withHeader('Idempotency-Key', $key)->postJson(route('billing.subscriptions.store'), [
            'plan_id' => $this->plan->id,
            'provider' => 'stripe',
            'billing_interval' => 'monthly',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('checkout_url', 'https://checkout.stripe.test/session');

        $attempt = PaymentAttempt::query()->sole();
        $this->assertSame(4900, $attempt->amount_minor);
        $this->assertSame('stripe', $attempt->provider);
        $this->assertDatabaseCount('subscriptions', 0);

        $this->withHeader('Idempotency-Key', $key)->postJson(route('billing.subscriptions.store'), [
            'plan_id' => $this->plan->id,
            'provider' => 'stripe',
            'billing_interval' => 'monthly',
        ])->assertOk()->assertJsonPath('id', $attempt->id);

        $this->assertDatabaseCount('payment_attempts', 1);
        Http::assertSentCount(1);
    }

    public function test_paid_subscription_cannot_be_activated_without_a_verified_payment(): void
    {
        try {
            app(CreateSubscriptionAction::class)->execute($this->team->id, $this->plan->id);
            $this->fail('A paid subscription must not be activated directly.');
        } catch (LogicException $exception) {
            $this->assertSame('Paid subscriptions must be activated by a verified payment webhook.', $exception->getMessage());
        }

        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_pending_attempt_is_reused_for_another_submit_key(): void
    {
        $this->startCheckout('c0a80123-4567-4890-a123-456789abcdef');
        $response = $this->withHeader('Idempotency-Key', 'c0a80123-4567-4890-a123-456789abcdee')
            ->postJson(route('billing.subscriptions.store'), [
                'plan_id' => $this->plan->id,
                'provider' => 'stripe',
                'billing_interval' => 'monthly',
            ]);
        $response->assertOk()->assertJsonPath('status', 'pending');

        $this->assertDatabaseCount('payment_attempts', 1);
        Http::assertSentCount(1);
    }

    public function test_checkout_does_not_reuse_pending_attempt_with_a_different_interval(): void
    {
        $this->startCheckout('c0a80123-4567-4890-a123-456789abcdef');

        $this->withHeader('Idempotency-Key', 'c0a80123-4567-4890-a123-456789abcdee')
            ->postJson(route('billing.subscriptions.store'), [
                'plan_id' => $this->plan->id,
                'provider' => 'stripe',
                'billing_interval' => 'yearly',
            ])
            ->assertOk();

        $this->assertDatabaseCount('payment_attempts', 2);
        $this->assertSame(
            ['monthly', 'yearly'],
            PaymentAttempt::query()->orderBy('id')->pluck('billing_interval')->map->value->all(),
        );
        Http::assertSentCount(2);
    }

    public function test_yearly_checkout_uses_yearly_amount_and_provider_interval(): void
    {
        $response = $this->withHeader('Idempotency-Key', 'c0a80123-4567-4890-a123-456789abcdef')
            ->postJson(route('billing.subscriptions.store'), [
                'plan_id' => $this->plan->id,
                'provider' => 'stripe',
                'billing_interval' => 'yearly',
            ]);

        $response->assertOk();
        $attempt = PaymentAttempt::query()->sole();
        $this->assertSame('yearly', $attempt->billing_interval->value);
        $this->assertSame(49000, $attempt->amount_minor);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/v1/checkout/sessions')
            && $request->data()['line_items[0][price_data][recurring][interval]'] === 'year'
            && $request->data()['line_items[0][price_data][unit_amount]'] === 49000);
    }

    public function test_yearly_checkout_rejects_plan_without_yearly_price(): void
    {
        $this->plan->update(['yearly_price' => null]);

        $response = $this->withHeader('Idempotency-Key', 'c0a80123-4567-4890-a123-456789abcdef')
            ->postJson(route('billing.subscriptions.store'), [
                'plan_id' => $this->plan->id,
                'provider' => 'stripe',
                'billing_interval' => 'yearly',
            ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('billing_interval');
        $this->assertDatabaseCount('payment_attempts', 0);
    }

    public function test_invalid_billing_interval_is_rejected_by_form_request(): void
    {
        $response = $this->withHeader('Idempotency-Key', 'c0a80123-4567-4890-a123-456789abcdef')
            ->postJson(route('billing.subscriptions.store'), [
                'plan_id' => $this->plan->id,
                'provider' => 'stripe',
                'billing_interval' => 'weekly',
            ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('billing_interval');
        $this->assertDatabaseCount('payment_attempts', 0);
    }

    public function test_free_plan_expires_after_the_selected_billing_period(): void
    {
        $this->plan->update(['price' => 0, 'yearly_price' => null]);
        $this->assertDatabaseCount('subscriptions', 0);
        $response = $this->withHeader('Idempotency-Key', 'c0a80123-4567-4890-a123-456789abcdef')
            ->postJson(route('billing.subscriptions.store'), [
                'plan_id' => $this->plan->id,
                'provider' => 'stripe',
                'billing_interval' => 'yearly',
            ]);

        $response->assertOk()->assertJsonPath('status', 'succeeded');
        $attemptId = $response->json('id');
        $this->assertDatabaseHas('subscriptions', [
            'team_id' => $this->team->id,
            'plan_id' => $this->plan->id,
            'billing_interval' => 'yearly',
            'status' => 'active',
        ]);

        $this->withHeader('Idempotency-Key', 'd0a80123-4567-4890-a123-456789abcdef')
            ->postJson(route('billing.subscriptions.store'), [
                'plan_id' => $this->plan->id,
                'provider' => 'stripe',
                'billing_interval' => 'yearly',
            ])->assertOk()->assertJsonPath('id', $attemptId);
        $this->assertDatabaseCount('payment_attempts', 1);
        $subscription = Subscription::query()->firstOrFail();
        $this->assertTrue($subscription->ends_at->isSameDay(now()->addYear()));
        Http::assertNothingSent();
    }

    public function test_signed_success_webhook_activates_subscription_once(): void
    {
        $attempt = $this->startCheckout('c0a80123-4567-4890-a123-456789abcdef');
        $body = json_encode([
            'id' => 'evt_checkout_paid',
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => 'cs_test_checkout',
                'subscription' => 'sub_test_subscription',
                'payment_intent' => 'pi_test_payment',
                'payment_status' => 'paid',
                'amount_total' => 4900,
                'currency' => 'usd',
                'metadata' => ['payment_attempt_id' => (string) $attempt->id],
            ]],
        ], JSON_THROW_ON_ERROR);

        $this->sendStripeWebhook($body)->assertOk()->assertJsonPath('duplicate', false);
        $this->sendStripeWebhook($body)->assertOk()->assertJsonPath('duplicate', true);

        $this->assertDatabaseCount('payment_webhook_events', 1);
        $this->assertDatabaseCount('payment_attempts', 1);
        $this->assertDatabaseHas('payment_attempts', ['id' => $attempt->id, 'status' => 'succeeded']);
        $this->assertDatabaseHas('subscriptions', [
            'team_id' => $this->team->id,
            'plan_id' => $this->plan->id,
            'provider_subscription_id' => 'sub_test_subscription',
            'status' => 'active',
        ]);
    }

    public function test_invalid_webhook_signature_does_not_change_payment_state(): void
    {
        $attempt = $this->startCheckout('c0a80123-4567-4890-a123-456789abcdef');
        $body = '{"id":"evt_invalid","type":"checkout.session.completed","data":{"object":{}}}';

        $this->withHeader('Stripe-Signature', 't='.time().',v1=invalid')
            ->call('POST', route('billing.webhooks', 'stripe'), [], [], [], [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => 't='.time().',v1=invalid',
            ], $body)
            ->assertUnauthorized();

        $this->assertSame('pending', $attempt->fresh()->status);
        $this->assertDatabaseCount('payment_webhook_events', 0);
    }

    public function test_mismatched_webhook_amount_is_rejected_without_processing(): void
    {
        $attempt = $this->startCheckout('c0a80123-4567-4890-a123-456789abcdef');
        $body = json_encode([
            'id' => 'evt_wrong_amount',
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => 'cs_test_checkout',
                'subscription' => 'sub_test_subscription',
                'payment_intent' => 'pi_wrong_amount',
                'payment_status' => 'paid',
                'amount_total' => 10,
                'currency' => 'usd',
                'metadata' => ['payment_attempt_id' => (string) $attempt->id],
            ]],
        ], JSON_THROW_ON_ERROR);

        $this->sendStripeWebhook($body)->assertUnprocessable();
        $this->assertSame('pending', $attempt->fresh()->status);
        $this->assertDatabaseCount('payment_webhook_events', 0);
        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_failed_payment_is_tracked_without_activating_a_subscription(): void
    {
        $attempt = $this->startCheckout('c0a80123-4567-4890-a123-456789abcdef');
        $body = json_encode([
            'id' => 'evt_invoice_failed',
            'type' => 'invoice.payment_failed',
            'data' => ['object' => [
                'id' => 'in_test_invoice',
                'subscription' => 'sub_test_subscription',
                'payment_intent' => 'pi_failed',
                'currency' => 'usd',
                'last_payment_error' => ['message' => 'Card was declined'],
            ]],
        ], JSON_THROW_ON_ERROR);

        $this->sendStripeWebhook($body)->assertOk();

        $this->assertDatabaseHas('payment_attempts', [
            'id' => $attempt->id,
            'status' => 'failed',
            'failure_reason' => 'Card was declined',
        ]);
        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_razorpay_yearly_checkout_creates_and_caches_a_yearly_provider_plan(): void
    {
        config([
            'services.payments.razorpay.key_id' => 'rzp_test_id',
            'services.payments.razorpay.key_secret' => 'rzp_test_secret',
            'services.payments.razorpay.webhook_secret' => 'rzp_test_webhook',
        ]);
        app()->forgetInstance(PaymentProviderManager::class);
        Http::fake([
            'https://api.razorpay.com/v1/plans' => Http::response(['id' => 'plan_razorpay_test']),
            'https://api.razorpay.com/v1/subscriptions' => Http::response([
                'id' => 'sub_razorpay_test',
                'short_url' => 'https://rzp.test/checkout',
            ]),
        ]);

        $response = $this->withHeader('Idempotency-Key', 'c0a80123-4567-4890-a123-456789abcdef')
            ->postJson(route('billing.subscriptions.store'), [
                'plan_id' => $this->plan->id,
                'provider' => 'razorpay',
                'billing_interval' => 'yearly',
            ]);

        $response->assertOk()->assertJsonPath('checkout_url', 'https://rzp.test/checkout');
        $this->assertSame('plan_razorpay_test', $this->plan->fresh()->provider_plan_ids['razorpay:yearly']);
        $this->assertDatabaseHas('payment_attempts', [
            'provider' => 'razorpay',
            'provider_subscription_id' => 'sub_razorpay_test',
            'billing_interval' => 'yearly',
            'status' => 'pending',
        ]);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/v1/plans')
            && $request->data()['period'] === 'yearly'
            && $request->data()['item']['amount'] === 49000);
        Http::assertSentCount(2);
    }

    public function test_paypal_yearly_checkout_creates_a_yearly_recurring_subscription(): void
    {
        config([
            'services.payments.paypal.client_id' => 'paypal_test_id',
            'services.payments.paypal.client_secret' => 'paypal_test_secret',
            'services.payments.paypal.webhook_id' => 'paypal_test_webhook',
        ]);
        app()->forgetInstance(PaymentProviderManager::class);
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'paypal_access_token']),
            'https://api-m.sandbox.paypal.com/v1/catalogs/products' => Http::response(['id' => 'PROD_TEST']),
            'https://api-m.sandbox.paypal.com/v1/billing/plans' => Http::response(['id' => 'P-PLAN_TEST']),
            'https://api-m.sandbox.paypal.com/v1/billing/subscriptions' => Http::response([
                'id' => 'I-SUB_TEST',
                'links' => [['rel' => 'approve', 'href' => 'https://paypal.test/approve']],
            ]),
        ]);

        $response = $this->withHeader('Idempotency-Key', 'c0a80123-4567-4890-a123-456789abcdef')
            ->postJson(route('billing.subscriptions.store'), [
                'plan_id' => $this->plan->id,
                'provider' => 'paypal',
                'billing_interval' => 'yearly',
            ]);

        $response->assertOk()->assertJsonPath('checkout_url', 'https://paypal.test/approve');
        $this->assertSame('P-PLAN_TEST', $this->plan->fresh()->provider_plan_ids['paypal:yearly']);
        $this->assertDatabaseHas('payment_attempts', [
            'provider' => 'paypal',
            'provider_subscription_id' => 'I-SUB_TEST',
            'billing_interval' => 'yearly',
            'status' => 'pending',
        ]);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/v1/billing/plans')
            && $request->data()['billing_cycles'][0]['frequency']['interval_unit'] === 'YEAR'
            && $request->data()['billing_cycles'][0]['pricing_scheme']['fixed_price']['value'] === '490.00');
        Http::assertSentCount(6);
    }

    public function test_razorpay_webhook_requires_signature_and_activates_subscription(): void
    {
        config([
            'services.payments.razorpay.key_id' => 'rzp_test_id',
            'services.payments.razorpay.key_secret' => 'rzp_test_secret',
            'services.payments.razorpay.webhook_secret' => 'rzp_test_webhook',
        ]);
        app()->forgetInstance(PaymentProviderManager::class);
        $attempt = $this->createAttempt('razorpay', 'sub_razorpay_test');
        $body = json_encode([
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => [
                'id' => 'pay_razorpay_test',
                'subscription_id' => 'sub_razorpay_test',
                'amount' => 4900,
                'currency' => 'USD',
                'notes' => ['payment_attempt_id' => (string) $attempt->id],
            ]]],
        ], JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $body, 'rzp_test_webhook');

        $this->call('POST', route('billing.webhooks', 'razorpay'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => $signature,
            'HTTP_X_RAZORPAY_EVENT_ID' => 'evt_razorpay_test',
        ], $body)->assertOk();

        $this->assertDatabaseHas('payment_attempts', ['id' => $attempt->id, 'status' => 'succeeded']);
        $this->assertDatabaseHas('subscriptions', ['team_id' => $this->team->id, 'status' => 'active']);
    }

    public function test_paypal_webhook_is_verified_with_paypal_before_subscription_activation(): void
    {
        config([
            'services.payments.paypal.client_id' => 'paypal_test_id',
            'services.payments.paypal.client_secret' => 'paypal_test_secret',
            'services.payments.paypal.webhook_id' => 'paypal_test_webhook',
        ]);
        app()->forgetInstance(PaymentProviderManager::class);
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'paypal_access_token']),
            'https://api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature' => Http::response([
                'verification_status' => 'SUCCESS',
            ]),
        ]);
        $attempt = $this->createAttempt('paypal', 'I-SUB_TEST');
        $body = json_encode([
            'id' => 'WH-TEST-1',
            'event_type' => 'PAYMENT.SALE.COMPLETED',
            'resource' => [
                'id' => 'SALE-TEST-1',
                'billing_agreement_id' => 'I-SUB_TEST',
                'custom_id' => (string) $attempt->id,
                'amount' => ['total' => '49.00', 'currency' => 'USD'],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->call('POST', route('billing.webhooks', 'paypal'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_PAYPAL_AUTH_ALGO' => 'SHA256withRSA',
            'HTTP_PAYPAL_CERT_URL' => 'https://api-m.sandbox.paypal.com/cert',
            'HTTP_PAYPAL_TRANSMISSION_ID' => 'transmission-test',
            'HTTP_PAYPAL_TRANSMISSION_SIG' => 'signature-test',
            'HTTP_PAYPAL_TRANSMISSION_TIME' => now()->toIso8601String(),
        ], $body)->assertOk();

        $this->assertDatabaseHas('payment_attempts', ['id' => $attempt->id, 'status' => 'succeeded']);
        $this->assertDatabaseHas('subscriptions', ['team_id' => $this->team->id, 'status' => 'active']);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/v1/notifications/verify-webhook-signature'));
    }

    public function test_refund_is_rejected_for_failed_or_unpaid_attempts(): void
    {
        $attempt = $this->startCheckout('c0a80123-4567-4890-a123-456789abcdef');
        $attempt->update(['status' => 'failed']);

        try {
            app(RefundPaymentAction::class)->execute($this->team->id, $attempt->id, 'Refund test');
            $this->fail('A failed payment must not be sent to a provider for refund.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Only a confirmed successful payment can be refunded.', $exception->getMessage());
        }

        $this->assertDatabaseHas('payment_attempts', ['id' => $attempt->id, 'status' => 'failed']);
        Http::assertSentCount(1);
    }

    private function startCheckout(string $key): PaymentAttempt
    {
        $response = $this->withHeader('Idempotency-Key', $key)->postJson(route('billing.subscriptions.store'), [
            'plan_id' => $this->plan->id,
            'provider' => 'stripe',
            'billing_interval' => 'monthly',
        ]);
        $response->assertOk();

        return PaymentAttempt::query()->latest('id')->firstOrFail();
    }

    private function createAttempt(string $provider, string $subscriptionId): PaymentAttempt
    {
        return PaymentAttempt::query()->create([
            'team_id' => $this->team->id,
            'plan_id' => $this->plan->id,
            'provider' => $provider,
            'billing_interval' => 'monthly',
            'status' => 'pending',
            'idempotency_key' => 'manual-'.$provider.'-attempt',
            'provider_subscription_id' => $subscriptionId,
            'amount_minor' => 4900,
            'currency' => 'USD',
        ]);
    }

    private function sendStripeWebhook(string $body)
    {
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, 'whsec_local');

        return $this->call('POST', route('billing.webhooks', 'stripe'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => 't='.$timestamp.',v1='.$signature,
        ], $body);
    }
}
