<?php

namespace App\Services\Billing\Providers;

use App\Contracts\Billing\PaymentProvider;
use App\Models\PaymentAttempt;
use App\Models\Plan;
use App\Models\User;
use App\Services\Billing\ProviderPlanCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class RazorpayPaymentProvider implements PaymentProvider
{
    public function __construct(private readonly ProviderPlanCache $planCache) {}

    public function key(): string
    {
        return 'razorpay';
    }

    public function configured(): bool
    {
        return filled(config('services.payments.razorpay.key_id'))
            && filled(config('services.payments.razorpay.key_secret'))
            && filled(config('services.payments.razorpay.webhook_secret'));
    }

    public function createCheckout(PaymentAttempt $attempt, Plan $plan, User $user): array
    {
        $interval = $attempt->billing_interval;
        $planId = $this->planCache->resolve($plan, 'razorpay', $interval, function () use ($plan, $attempt, $interval): string {
            $response = $this->request()
                ->withHeader('X-Razorpay-Idempotency-Key', 'plan-'.substr(hash('sha256', $plan->slug.':'.$interval->value), 0, 30))
                ->post($this->url('/v1/plans'), [
                    'period' => $interval->razorpayPeriod(),
                    'interval' => 1,
                    'item' => [
                        'name' => $plan->name,
                        'amount' => $attempt->amount_minor,
                        'currency' => strtoupper($plan->currency),
                        'description' => $plan->description ?? $plan->name,
                    ],
                ]);
            $response->throw();
            $id = $response->json('id');
            if (! is_string($id) || $id === '') {
                throw new RuntimeException('Razorpay did not return a plan ID.');
            }

            return $id;
        });

        $response = $this->request()
            ->withHeader('X-Razorpay-Idempotency-Key', $attempt->idempotency_key)
            ->post($this->url('/v1/subscriptions'), [
                'plan_id' => $planId,
                'total_count' => 120,
                'customer_notify' => 1,
                'notes' => [
                    'payment_attempt_id' => (string) $attempt->id,
                    'team_id' => (string) $attempt->team_id,
                ],
            ]);
        $response->throw();

        $subscription = $response->json();
        if (! is_string($subscription['id'] ?? null) || ! is_string($subscription['short_url'] ?? null)) {
            throw new RuntimeException('Razorpay did not return a valid subscription checkout.');
        }

        return [
            'checkout_id' => $subscription['id'],
            'subscription_id' => $subscription['id'],
            'checkout_url' => $subscription['short_url'],
        ];
    }

    public function parseWebhook(Request $request): array
    {
        $secret = (string) config('services.payments.razorpay.webhook_secret');
        $signature = (string) $request->header('X-Razorpay-Signature', '');
        $expected = hash_hmac('sha256', $request->getContent(), $secret);
        if ($secret === '' || $signature === '' || ! hash_equals($expected, $signature)) {
            throw new RuntimeException('Invalid Razorpay webhook signature.');
        }

        $event = $request->json()->all();
        $type = (string) ($event['event'] ?? '');
        $payment = $event['payload']['payment']['entity'] ?? [];
        $subscription = $event['payload']['subscription']['entity'] ?? [];
        $refund = $event['payload']['refund']['entity'] ?? [];
        $subscriptionId = $payment['subscription_id'] ?? $subscription['id'] ?? null;
        $status = match ($type) {
            'payment.captured', 'subscription.charged' => 'succeeded',
            'refund.processed' => 'refunded',
            'payment.failed', 'subscription.halted' => 'failed',
            'subscription.cancelled', 'subscription.completed' => 'canceled',
            default => 'ignored',
        };

        return [
            'event_id' => (string) ($request->header('x-razorpay-event-id') ?? $event['event_id'] ?? $event['id'] ?? ''),
            'event_type' => $type,
            'attempt_id' => $payment['notes']['payment_attempt_id'] ?? $subscription['notes']['payment_attempt_id'] ?? null,
            'checkout_id' => $subscriptionId,
            'subscription_id' => $subscriptionId,
            'payment_reference' => $refund['payment_id'] ?? $payment['id'] ?? null,
            'status' => $status,
            'amount_minor' => $payment['amount'] ?? $refund['amount'] ?? null,
            'currency' => isset($payment['currency']) ? strtoupper($payment['currency']) : (isset($refund['currency']) ? strtoupper($refund['currency']) : null),
            'failure_reason' => $payment['error_description'] ?? null,
        ];
    }

    public function refund(PaymentAttempt $attempt, string $reason): array
    {
        if (! $attempt->payment_reference) {
            throw new RuntimeException('Razorpay payment reference is missing.');
        }

        $response = $this->request()
            ->withHeader('X-Razorpay-Idempotency-Key', 'refund-'.$attempt->id)
            ->post($this->url('/v1/payments/'.$attempt->payment_reference.'/refund'), [
                'notes' => ['reason' => $reason, 'payment_attempt_id' => (string) $attempt->id],
            ]);
        $response->throw();

        return $response->json();
    }

    private function request()
    {
        return Http::withBasicAuth(
            (string) config('services.payments.razorpay.key_id'),
            (string) config('services.payments.razorpay.key_secret'),
        )->acceptJson()->connectTimeout(5)->timeout(20);
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.payments.razorpay.base_url'), '/').$path;
    }
}
