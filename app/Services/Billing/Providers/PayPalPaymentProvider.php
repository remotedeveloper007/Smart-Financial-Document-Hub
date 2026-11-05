<?php

namespace App\Services\Billing\Providers;

use App\Contracts\Billing\PaymentProvider;
use App\Data\Billing\Money;
use App\Models\PaymentAttempt;
use App\Models\Plan;
use App\Models\User;
use App\Services\Billing\ProviderPlanCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class PayPalPaymentProvider implements PaymentProvider
{
    public function __construct(private readonly ProviderPlanCache $planCache) {}

    public function key(): string
    {
        return 'paypal';
    }

    public function configured(): bool
    {
        return filled(config('services.payments.paypal.client_id'))
            && filled(config('services.payments.paypal.client_secret'))
            && filled(config('services.payments.paypal.webhook_id'));
    }

    public function createCheckout(PaymentAttempt $attempt, Plan $plan, User $user): array
    {
        $interval = $attempt->billing_interval;
        $planId = $this->planCache->resolve($plan, 'paypal', $interval, function () use ($plan, $attempt, $interval): string {
            $productResponse = $this->api()->withHeader('PayPal-Request-Id', 'product-'.substr(hash('sha256', $plan->slug), 0, 30))
                ->post($this->url('/v1/catalogs/products'), [
                    'name' => $plan->name,
                    'description' => $plan->description ?? $plan->name,
                    'type' => 'SERVICE',
                    'category' => 'SOFTWARE',
                ]);
            $productResponse->throw();
            $productId = $productResponse->json('id');
            if (! is_string($productId) || $productId === '') {
                throw new RuntimeException('PayPal did not return a product ID.');
            }

            $planResponse = $this->api()->withHeader('PayPal-Request-Id', 'plan-'.substr(hash('sha256', $plan->slug.':'.$interval->value), 0, 30))
                ->post($this->url('/v1/billing/plans'), [
                    'product_id' => $productId,
                    'name' => $plan->name.' '.strtolower($interval->value),
                    'billing_cycles' => [[
                        'frequency' => ['interval_unit' => $interval->providerInterval(), 'interval_count' => 1],
                        'tenure_type' => 'REGULAR',
                        'sequence' => 1,
                        'total_cycles' => 0,
                        'pricing_scheme' => ['fixed_price' => [
                            'value' => Money::fromMinorUnits($attempt->amount_minor, $plan->currency),
                            'currency_code' => strtoupper($plan->currency),
                        ]],
                    ]],
                    'payment_preferences' => [
                        'auto_bill_outstanding' => true,
                        'payment_failure_threshold' => 3,
                    ],
                ]);
            $planResponse->throw();
            $id = $planResponse->json('id');
            if (! is_string($id) || $id === '') {
                throw new RuntimeException('PayPal did not return a billing plan ID.');
            }

            return $id;
        });

        $response = $this->api()
            ->withHeader('PayPal-Request-Id', $attempt->idempotency_key)
            ->post($this->url('/v1/billing/subscriptions'), [
                'plan_id' => $planId,
                'custom_id' => (string) $attempt->id,
                'subscriber' => ['email_address' => $user->email],
                'application_context' => [
                    'brand_name' => config('app.name'),
                    'user_action' => 'SUBSCRIBE_NOW',
                    'return_url' => route('billing.page').'?checkout=success',
                    'cancel_url' => route('billing.page').'?checkout=cancelled',
                ],
            ]);
        $response->throw();

        $subscription = $response->json();
        $approvalUrl = collect($subscription['links'] ?? [])
            ->firstWhere('rel', 'approve')['href'] ?? null;
        if (! is_string($subscription['id'] ?? null) || ! is_string($approvalUrl)) {
            throw new RuntimeException('PayPal did not return a valid subscription approval URL.');
        }

        return [
            'checkout_id' => $subscription['id'],
            'subscription_id' => $subscription['id'],
            'checkout_url' => $approvalUrl,
        ];
    }

    public function parseWebhook(Request $request): array
    {
        $event = $request->json()->all();
        $verification = $this->api()->post($this->url('/v1/notifications/verify-webhook-signature'), [
            'auth_algo' => $request->header('PAYPAL-AUTH-ALGO'),
            'cert_url' => $request->header('PAYPAL-CERT-URL'),
            'transmission_id' => $request->header('PAYPAL-TRANSMISSION-ID'),
            'transmission_sig' => $request->header('PAYPAL-TRANSMISSION-SIG'),
            'transmission_time' => $request->header('PAYPAL-TRANSMISSION-TIME'),
            'webhook_id' => config('services.payments.paypal.webhook_id'),
            'webhook_event' => $event,
        ]);
        $verification->throw();
        if ($verification->json('verification_status') !== 'SUCCESS') {
            throw new RuntimeException('Invalid PayPal webhook signature.');
        }

        $resource = $event['resource'] ?? [];
        $type = (string) ($event['event_type'] ?? '');
        $status = match ($type) {
            'PAYMENT.SALE.COMPLETED', 'PAYMENT.CAPTURE.COMPLETED' => 'succeeded',
            'PAYMENT.SALE.DENIED', 'PAYMENT.CAPTURE.DENIED' => 'failed',
            'PAYMENT.SALE.REFUNDED', 'PAYMENT.CAPTURE.REFUNDED' => 'refunded',
            'BILLING.SUBSCRIPTION.CANCELLED', 'BILLING.SUBSCRIPTION.SUSPENDED' => 'canceled',
            default => 'ignored',
        };
        $subscriptionId = $resource['billing_agreement_id'] ?? $resource['id'] ?? null;
        $amount = $resource['amount']['total'] ?? $resource['amount']['value'] ?? null;
        $currency = $resource['amount']['currency'] ?? null;

        return [
            'event_id' => (string) ($event['id'] ?? ''),
            'event_type' => $type,
            'attempt_id' => $resource['custom_id'] ?? null,
            'checkout_id' => $subscriptionId,
            'subscription_id' => $subscriptionId,
            'payment_reference' => $resource['id'] ?? null,
            'status' => $status,
            'amount_minor' => is_numeric($amount) && is_string($currency)
                ? Money::toMinorUnits((string) $amount, $currency)
                : null,
            'currency' => $currency,
            'failure_reason' => $resource['status_details']['reason'] ?? null,
        ];
    }

    public function refund(PaymentAttempt $attempt, string $reason): array
    {
        if (! $attempt->payment_reference) {
            throw new RuntimeException('PayPal capture reference is missing.');
        }

        $path = $attempt->provider_subscription_id
            ? '/v1/payments/sale/'.$attempt->payment_reference.'/refund'
            : '/v2/payments/captures/'.$attempt->payment_reference.'/refund';
        $response = $this->api()
            ->withHeader('PayPal-Request-Id', 'refund-'.$attempt->id)
            ->post($this->url($path), [
                'note_to_payer' => $reason,
            ]);
        $response->throw();

        return $response->json();
    }

    private function api()
    {
        $tokenResponse = Http::asForm()
            ->withBasicAuth(
                (string) config('services.payments.paypal.client_id'),
                (string) config('services.payments.paypal.client_secret'),
            )->connectTimeout(5)->timeout(20)
            ->post($this->url('/v1/oauth2/token'), ['grant_type' => 'client_credentials']);
        $tokenResponse->throw();
        $token = $tokenResponse->json('access_token');
        if (! is_string($token) || $token === '') {
            throw new RuntimeException('PayPal did not issue an API access token.');
        }

        return Http::withToken($token)->acceptJson()->connectTimeout(5)->timeout(20);
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.payments.paypal.base_url'), '/').$path;
    }
}
