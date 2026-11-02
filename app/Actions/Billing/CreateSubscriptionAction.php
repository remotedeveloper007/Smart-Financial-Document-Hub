<?php

namespace App\Actions\Billing;

use App\Jobs\SendSubscriptionCreatedJob;
use App\Models\Subscription;
use Carbon\Carbon;

final class CreateSubscriptionAction
{
    public function execute(int $teamId, int $planId, array $meta = []): Subscription
    {
        $now = Carbon::now();

        $subscription = Subscription::updateOrCreate([
            'team_id' => $teamId,
            'plan_id' => $planId,
        ], [
            'status' => 'active',
            'starts_at' => $now,
            'ends_at' => null,
            'meta' => $meta,
        ]);

        SendSubscriptionCreatedJob::dispatch($subscription);

        return $subscription;
    }
}
