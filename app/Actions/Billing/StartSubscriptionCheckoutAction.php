<?php

namespace App\Actions\Billing;

use App\Data\Billing\Money;
use App\Enums\BillingInterval;
use App\Jobs\SendSubscriptionCreatedJob;
use App\Models\PaymentAttempt;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Team;
use App\Models\User;
use App\Services\Billing\PaymentProviderManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class StartSubscriptionCheckoutAction
{
    public function __construct(private readonly PaymentProviderManager $providers) {}

    public function execute(
        int $teamId,
        int $planId,
        string $providerKey,
        BillingInterval $interval,
        string $idempotencyKey,
        User $user,
    ): PaymentAttempt {
        $provider = $this->providers->get($providerKey);
        $plan = Plan::query()->findOrFail($planId);
        $price = $plan->priceFor($interval);
        $effectiveProvider = (float) $price === 0.0 ? 'free' : $providerKey;

        $attempt = DB::transaction(function () use ($teamId, $plan, $provider, $effectiveProvider, $interval, $price, $idempotencyKey): PaymentAttempt {
            Team::query()->lockForUpdate()->findOrFail($teamId);

            $existing = PaymentAttempt::query()
                ->where('team_id', $teamId)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                if ($existing->plan_id !== $plan->id || $existing->provider !== $effectiveProvider || $existing->billing_interval !== $interval) {
                    throw ValidationException::withMessages([
                        'idempotency_key' => 'This idempotency key was already used for a different checkout.',
                    ]);
                }

                return $existing;
            }

            $inFlight = PaymentAttempt::query()
                ->where('team_id', $teamId)
                ->where('plan_id', $plan->id)
                ->where('provider', $effectiveProvider)
                ->where('billing_interval', $interval)
                ->whereIn('status', ['creating', 'pending', 'processing'])
                ->latest('id')
                ->first();

            if ($inFlight) {
                return $inFlight;
            }

            $currentSubscription = Subscription::query()
                ->where('team_id', $teamId)
                ->whereIn('status', ['active', 'past_due'])
                ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))
                ->lockForUpdate()
                ->first();

            if ($currentSubscription) {
                if ($currentSubscription->plan_id === $plan->id
                    && $currentSubscription->billing_interval === $interval
                    && $currentSubscription->status === 'active') {
                    $successfulAttempt = PaymentAttempt::query()
                        ->where('team_id', $teamId)
                        ->where('subscription_id', $currentSubscription->id)
                        ->where('status', 'succeeded')
                        ->latest('id')
                        ->first();

                    if ($successfulAttempt) {
                        return $successfulAttempt;
                    }
                }

                throw ValidationException::withMessages([
                    'plan_id' => 'The team already has an active subscription.',
                ]);
            }

            if ((float) $price > 0 && ! $provider->configured()) {
                throw ValidationException::withMessages([
                    'provider' => 'The selected payment provider is not configured.',
                ]);
            }

            return PaymentAttempt::query()->create([
                'team_id' => $teamId,
                'plan_id' => $plan->id,
                'provider' => $effectiveProvider,
                'status' => 'creating',
                'idempotency_key' => $idempotencyKey,
                'billing_interval' => $interval,
                'amount_minor' => Money::toMinorUnits($price, $plan->currency),
                'currency' => strtoupper($plan->currency),
            ]);
        }, attempts: 3);

        if ($attempt->status !== 'creating') {
            return $attempt;
        }

        if ((int) $attempt->amount_minor === 0) {
            return DB::transaction(function () use ($attempt, $plan, $interval): PaymentAttempt {
                $lockedAttempt = PaymentAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
                $subscription = Subscription::query()
                    ->where('team_id', $lockedAttempt->team_id)
                    ->where('plan_id', $plan->id)
                    ->lockForUpdate()
                    ->first();
                $wasActive = $subscription?->status === 'active';
                $subscription ??= new Subscription([
                    'team_id' => $lockedAttempt->team_id,
                    'plan_id' => $plan->id,
                ]);
                $periodEnd = $interval->addTo(now());
                $subscription->forceFill([
                    'status' => 'active',
                    'billing_interval' => $interval,
                    'starts_at' => $subscription->starts_at ?? now(),
                    'current_period_start' => now(),
                    'current_period_end' => $periodEnd,
                    'ends_at' => $periodEnd,
                    'canceled_at' => null,
                ])->save();
                if (! $wasActive) {
                    SendSubscriptionCreatedJob::dispatch($subscription)->afterCommit();
                }
                $lockedAttempt->forceFill([
                    'subscription_id' => $subscription->id,
                    'status' => 'succeeded',
                ])->save();

                return $lockedAttempt;
            }, attempts: 3);
        }

        try {
            $checkout = $provider->createCheckout($attempt, $plan, $user);

            return DB::transaction(function () use ($attempt, $checkout): PaymentAttempt {
                $lockedAttempt = PaymentAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
                if ($lockedAttempt->status === 'creating') {
                    $lockedAttempt->forceFill([
                        'status' => 'pending',
                        'provider_checkout_id' => $checkout['checkout_id'],
                        'provider_subscription_id' => $checkout['subscription_id'] ?? null,
                        'checkout_url' => $checkout['checkout_url'],
                    ])->save();
                }

                return $lockedAttempt;
            }, attempts: 3);
        } catch (\Throwable $exception) {
            PaymentAttempt::query()->whereKey($attempt->id)->where('status', 'creating')->update([
                'status' => 'failed',
                'failure_reason' => $exception->getMessage(),
                'updated_at' => now(),
            ]);

            throw $exception;
        }
    }
}
