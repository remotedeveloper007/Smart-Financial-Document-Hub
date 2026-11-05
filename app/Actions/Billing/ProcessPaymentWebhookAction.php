<?php

namespace App\Actions\Billing;

use App\Enums\BillingInterval;
use App\Jobs\SendSubscriptionCreatedJob;
use App\Models\PaymentAttempt;
use App\Models\PaymentWebhookEvent;
use App\Models\Subscription;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class ProcessPaymentWebhookAction
{
    public function execute(string $provider, array $event): bool
    {
        if (empty($event['event_id']) || empty($event['event_type'])) {
            throw new RuntimeException('Payment webhook is missing its event ID or type.');
        }

        return DB::transaction(function () use ($provider, $event): bool {
            $inserted = PaymentWebhookEvent::query()->insertOrIgnore([
                'provider' => $provider,
                'event_id' => $event['event_id'],
                'event_type' => $event['event_type'],
                'status' => 'processed',
                'processed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($inserted === 0) {
                return false;
            }

            $attemptQuery = PaymentAttempt::withoutGlobalScopes()->where('provider', $provider);
            $attempt = null;
            if (! empty($event['attempt_id'])) {
                $attempt = (clone $attemptQuery)->whereKey($event['attempt_id'])->first();
            }
            if (! $attempt && ! empty($event['subscription_id'])) {
                $attempt = (clone $attemptQuery)
                    ->where('provider_subscription_id', $event['subscription_id'])
                    ->latest('id')
                    ->first();
            }
            if (! $attempt && ! empty($event['checkout_id'])) {
                $attempt = (clone $attemptQuery)->where('provider_checkout_id', $event['checkout_id'])->first();
            }
            if (! $attempt && ! empty($event['payment_reference'])) {
                $attempt = (clone $attemptQuery)->where('payment_reference', $event['payment_reference'])->latest('id')->first();
            }

            if (! $attempt) {
                $subscription = Subscription::withoutGlobalScopes()
                    ->where('provider', $provider)
                    ->where('provider_subscription_id', $event['subscription_id'] ?? $event['checkout_id'] ?? '')
                    ->first();

                if ($subscription && ($event['status'] ?? '') === 'succeeded') {
                    Team::query()->lockForUpdate()->findOrFail($subscription->team_id);
                    $attempt = PaymentAttempt::query()->create([
                        'team_id' => $subscription->team_id,
                        'plan_id' => $subscription->plan_id,
                        'subscription_id' => $subscription->id,
                        'provider' => $provider,
                        'status' => 'processing',
                        'idempotency_key' => 'webhook:'.$event['event_id'],
                        'provider_subscription_id' => $subscription->provider_subscription_id,
                        'billing_interval' => $subscription->billing_interval,
                        'amount_minor' => (int) ($event['amount_minor'] ?? 0),
                        'currency' => strtoupper($event['currency'] ?? 'USD'),
                    ]);
                }
            }

            if (! $attempt) {
                PaymentWebhookEvent::query()
                    ->where('provider', $provider)
                    ->where('event_id', $event['event_id'])
                    ->update(['status' => 'ignored', 'updated_at' => now()]);

                return true;
            }

            if (($event['status'] ?? '') === 'succeeded'
                && $attempt->status === 'succeeded'
                && filled($event['payment_reference'] ?? null)
                && $event['payment_reference'] !== $attempt->payment_reference
                && in_array($event['event_type'], [
                    'invoice.paid',
                    'subscription.charged',
                    'PAYMENT.SALE.COMPLETED',
                    'PAYMENT.CAPTURE.COMPLETED',
                ], true)) {
                $attempt = PaymentAttempt::query()->create([
                    'team_id' => $attempt->team_id,
                    'plan_id' => $attempt->plan_id,
                    'subscription_id' => $attempt->subscription_id,
                    'provider' => $provider,
                    'status' => 'processing',
                    'idempotency_key' => 'webhook:'.$event['event_id'],
                    'provider_subscription_id' => $event['subscription_id'] ?? $attempt->provider_subscription_id,
                    'billing_interval' => $attempt->billing_interval,
                    'payment_reference' => $event['payment_reference'] ?? null,
                    'amount_minor' => (int) ($event['amount_minor'] ?? $attempt->amount_minor),
                    'currency' => strtoupper($event['currency'] ?? $attempt->currency),
                ]);
            }

            if (($event['status'] ?? 'ignored') === 'succeeded'
                && (($event['amount_minor'] ?? null) !== null)
                && ((int) $event['amount_minor'] !== (int) $attempt->amount_minor)) {
                throw new RuntimeException('Payment webhook amount does not match the recorded attempt.');
            }
            if (($event['status'] ?? 'ignored') === 'succeeded'
                && filled($event['currency'] ?? null)
                && strtoupper($event['currency']) !== strtoupper($attempt->currency)) {
                throw new RuntimeException('Payment webhook currency does not match the recorded attempt.');
            }

            Team::query()->lockForUpdate()->findOrFail($attempt->team_id);
            $attempt = PaymentAttempt::withoutGlobalScopes()->lockForUpdate()->findOrFail($attempt->id);
            $incomingStatus = $event['status'] ?? 'ignored';

            if ($incomingStatus === 'succeeded' && $attempt->status !== 'refunded') {
                $subscription = Subscription::withoutGlobalScopes()
                    ->where('team_id', $attempt->team_id)
                    ->where('plan_id', $attempt->plan_id)
                    ->lockForUpdate()
                    ->first();

                if (! $subscription) {
                    $subscription = new Subscription([
                        'team_id' => $attempt->team_id,
                        'plan_id' => $attempt->plan_id,
                    ]);
                }
                $wasActive = $subscription->status === 'active';
                $periodStart = now();
                $interval = $attempt->billing_interval instanceof BillingInterval
                    ? $attempt->billing_interval
                    : BillingInterval::from((string) $attempt->billing_interval);
                $periodEnd = $interval->addTo($periodStart);
                $subscription->forceFill([
                    'status' => 'active',
                    'billing_interval' => $interval,
                    'provider' => $provider,
                    'provider_subscription_id' => $event['subscription_id'] ?? $attempt->provider_subscription_id,
                    'starts_at' => $subscription->starts_at ?? $periodStart,
                    'current_period_start' => $periodStart,
                    'current_period_end' => $periodEnd,
                    'ends_at' => null,
                    'canceled_at' => null,
                ])->save();
                if (! $wasActive) {
                    SendSubscriptionCreatedJob::dispatch($subscription)->afterCommit();
                }

                $attempt->forceFill([
                    'subscription_id' => $subscription->id,
                    'billing_interval' => $interval,
                    'status' => 'succeeded',
                    'payment_reference' => $event['payment_reference'] ?? $attempt->payment_reference,
                    'provider_subscription_id' => $event['subscription_id'] ?? $attempt->provider_subscription_id,
                    'failure_reason' => null,
                ])->save();
            } elseif ($incomingStatus === 'failed' && ! in_array($attempt->status, ['succeeded', 'refunded'], true)) {
                $attempt->forceFill([
                    'status' => 'failed',
                    'failure_reason' => $event['failure_reason'] ?? 'Provider reported a failed payment.',
                ])->save();

                Subscription::withoutGlobalScopes()
                    ->where('team_id', $attempt->team_id)
                    ->where('plan_id', $attempt->plan_id)
                    ->where('status', 'active')
                    ->update(['status' => 'past_due', 'updated_at' => now()]);
            } elseif ($incomingStatus === 'refunded' && in_array($attempt->status, ['succeeded', 'refunding'], true)) {
                $attempt->forceFill(['status' => 'refunded', 'refunded_at' => now()])->save();
                Subscription::withoutGlobalScopes()
                    ->where('team_id', $attempt->team_id)
                    ->where('plan_id', $attempt->plan_id)
                    ->update(['status' => 'canceled', 'canceled_at' => now(), 'updated_at' => now()]);
            } elseif ($incomingStatus === 'canceled') {
                if (! in_array($attempt->status, ['succeeded', 'refunded'], true)) {
                    $attempt->forceFill(['status' => 'canceled'])->save();
                }
                Subscription::withoutGlobalScopes()
                    ->where('team_id', $attempt->team_id)
                    ->where('plan_id', $attempt->plan_id)
                    ->update(['status' => 'canceled', 'canceled_at' => now(), 'updated_at' => now()]);
            } elseif ($incomingStatus === 'processing' && $attempt->status === 'creating') {
                $attempt->forceFill(['status' => 'processing'])->save();
            }

            PaymentWebhookEvent::query()
                ->where('provider', $provider)
                ->where('event_id', $event['event_id'])
                ->update(['payment_attempt_id' => $attempt->id, 'updated_at' => now()]);

            return true;
        }, attempts: 3);
    }
}
