<?php

namespace App\Services\Billing\Providers;

use App\Contracts\Billing\PaymentProvider;
use App\Models\PaymentAttempt;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class StripePaymentProvider implements PaymentProvider
{
    public function key(): string
    {
        return 'stripe';
    }

    public function configured(): bool
    {
        return filled(config('services.payments.stripe.secret'))
            && filled(config('services.payments.stripe.webhook_secret'));
    }

    public function createCheckout(PaymentAttempt $attempt, Plan $plan, User $user): array
    {
        $secret = (string) config('services.payments.stripe.secret');
        $currency = strtolower($plan->currency);
        $amount = $attempt->amount_minor;

        $response = Http::asForm()
            ->withToken($secret)
            ->withHeader('Idempotency-Key', $attempt->idempotency_key)
            ->connectTimeout(5)
            ->timeout(20)
            ->post(rtrim(config('services.payments.stripe.base_url'), '/').'/v1/checkout/sessions', [
                'mode' => 'subscription',
                'customer_email' => $user->email,
                'line_items[0][price_data][currency]' => $currency,
                'line_items[0][price_data][product_data][name]' => $plan->name,
                'line_items[0][price_data][unit_amount]' => $amount,
                'line_items[0][price_data][recurring][interval]' => $attempt->billing_interval->stripeInterval(),
                'line_items[0][quantity]' => 1,
                'metadata[payment_attempt_id]' => (string) $attempt->id,
                'subscription_data[metadata][payment_attempt_id]' => (string) $attempt->id,
                'success_url' => route('billing.page').'?checkout=success&session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('billing.page').'?checkout=cancelled',
            ]);
        $response->throw();

        $checkout = $response->json();
        if (! is_string($checkout['id'] ?? null) || ! is_string($checkout['url'] ?? null)) {
            throw new RuntimeException('Stripe did not return a valid checkout session.');
        }

        return [
            'checkout_id' => $checkout['id'],
            'subscription_id' => $checkout['subscription'] ?? null,
            'checkout_url' => $checkout['url'],
        ];
    }

    public function parseWebhook(Request $request): array
    {
        $secret = (string) config('services.payments.stripe.webhook_secret');
        $header = (string) $request->header('Stripe-Signature', '');
        preg_match('/(?:^|,)t=(\d+)/', $header, $timestampMatch);
        preg_match_all('/(?:^|,)v1=([a-f0-9]+)/', $header, $signatureMatches);

        $timestamp = isset($timestampMatch[1]) ? (int) $timestampMatch[1] : 0;
        if ($secret === '' || $timestamp === 0 || abs(time() - $timestamp) > 300) {
            throw new RuntimeException('Invalid Stripe webhook signature.');
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);
        $valid = false;
        foreach ($signatureMatches[1] ?? [] as $signature) {
            $valid = $valid || hash_equals($expected, $signature);
        }
        if (! $valid) {
            throw new RuntimeException('Invalid Stripe webhook signature.');
        }

        $event = $request->json()->all();
        $object = $event['data']['object'] ?? [];
        $type = (string) ($event['type'] ?? '');
        $status = match ($type) {
            'checkout.session.completed', 'checkout.session.async_payment_succeeded' => (($object['payment_status'] ?? 'paid') === 'paid') ? 'succeeded' : 'processing',
            'checkout.session.async_payment_failed' => 'failed',
            'checkout.session.expired' => 'canceled',
            'invoice.paid' => 'succeeded',
            'invoice.payment_failed' => 'failed',
            'charge.refunded' => 'refunded',
            'customer.subscription.deleted' => 'canceled',
            default => 'ignored',
        };

        return [
            'event_id' => (string) ($event['id'] ?? ''),
            'event_type' => $type,
            'attempt_id' => $object['metadata']['payment_attempt_id'] ?? null,
            'checkout_id' => $object['id'] ?? null,
            'subscription_id' => is_string($object['subscription'] ?? null)
                ? $object['subscription']
                : ($object['id'] ?? null),
            'payment_reference' => $object['payment_intent'] ?? $object['id'] ?? null,
            'status' => $status,
            'amount_minor' => $object['amount_paid'] ?? $object['amount_total'] ?? $object['amount'] ?? null,
            'currency' => isset($object['currency']) ? strtoupper($object['currency']) : null,
            'failure_reason' => $object['last_payment_error']['message'] ?? null,
        ];
    }

    public function refund(PaymentAttempt $attempt, string $reason): array
    {
        if (! $attempt->payment_reference) {
            throw new RuntimeException('Stripe payment reference is missing.');
        }

        $response = Http::asForm()
            ->withToken((string) config('services.payments.stripe.secret'))
            ->withHeader('Idempotency-Key', 'refund-'.$attempt->id)
            ->post(rtrim(config('services.payments.stripe.base_url'), '/').'/v1/refunds', [
                'payment_intent' => $attempt->payment_reference,
                'reason' => 'requested_by_customer',
                'metadata[payment_attempt_id]' => (string) $attempt->id,
                'metadata[reason]' => $reason,
            ]);
        $response->throw();

        return $response->json();
    }
}
