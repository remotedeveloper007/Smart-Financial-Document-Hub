<?php

namespace App\Actions\Billing;

use App\Enums\BillingInterval;
use App\Jobs\SendSubscriptionCreatedJob;
use App\Models\Plan;
use App\Models\Subscription;
use Carbon\Carbon;
use LogicException;

final class CreateSubscriptionAction
{
    public function execute(
        int $teamId,
        int $planId,
        array $meta = [],
        BillingInterval $interval = BillingInterval::Monthly,
    ): Subscription {
        $plan = Plan::query()->findOrFail($planId);
        if ((float) $plan->price > 0) {
            throw new LogicException('Paid subscriptions must be activated by a verified payment webhook.');
        }

        $now = Carbon::now();

        $subscription = Subscription::updateOrCreate([
            'team_id' => $teamId,
            'plan_id' => $planId,
        ], [
            'status' => 'active',
            'starts_at' => $now,
            'ends_at' => $interval->addTo($now),
            'billing_interval' => $interval,
            'current_period_start' => $now,
            'current_period_end' => $interval->addTo($now),
            'meta' => $meta,
        ]);

        SendSubscriptionCreatedJob::dispatch($subscription);

        return $subscription;
    }
}
