<?php

namespace App\Services\Billing;

use App\Enums\BillingInterval;
use App\Models\Plan;
use Closure;
use Illuminate\Support\Facades\DB;

final class ProviderPlanCache
{
    public function resolve(Plan $plan, string $provider, BillingInterval $interval, Closure $provision): string
    {
        return DB::transaction(function () use ($plan, $provider, $interval, $provision): string {
            $lockedPlan = Plan::query()->lockForUpdate()->findOrFail($plan->getKey());
            $providerPlanIds = $lockedPlan->provider_plan_ids ?? [];
            $cacheKey = $provider.':'.$interval->value;

            if (isset($providerPlanIds[$cacheKey]) && is_string($providerPlanIds[$cacheKey])) {
                return $providerPlanIds[$cacheKey];
            }

            if ($interval === BillingInterval::Monthly
                && isset($providerPlanIds[$provider])
                && is_string($providerPlanIds[$provider])) {
                $providerPlanIds[$cacheKey] = $providerPlanIds[$provider];
                $lockedPlan->provider_plan_ids = $providerPlanIds;
                $lockedPlan->save();

                return $providerPlanIds[$provider];
            }

            $providerPlanId = $provision();
            $providerPlanIds[$cacheKey] = $providerPlanId;
            $lockedPlan->provider_plan_ids = $providerPlanIds;
            $lockedPlan->save();

            return $providerPlanId;
        }, attempts: 3);
    }
}
