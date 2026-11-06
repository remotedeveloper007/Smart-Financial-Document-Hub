<?php

namespace App\Http\Controllers\Billing;

use App\Actions\Billing\RefundPaymentAction;
use App\Actions\Billing\StartSubscriptionCheckoutAction;
use App\Enums\BillingInterval;
use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\RefundPaymentRequest;
use App\Http\Requests\Billing\StoreSubscriptionCheckoutRequest;
use App\Models\PaymentAttempt;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Billing\PaymentProviderManager;
use App\Services\Tenancy\TeamContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class SubscriptionController extends Controller
{
    public function page(Request $request, TeamContext $teamContext, PaymentProviderManager $providers): Response
    {
        $this->authorize('manage_billing');

        $teamId = $teamContext->teamId();

        return Inertia::render('Billing/Plans', [
            'plans' => Plan::query()->orderBy('price')->get(),
            'providers' => $providers->available(),
            'billingIntervals' => array_map(
                fn (BillingInterval $interval): array => ['value' => $interval->value, 'label' => ucfirst($interval->value)],
                BillingInterval::cases(),
            ),
            'canRefund' => $request->user()->can('manage_billing'),
            'subscriptions' => Subscription::query()
                ->with('plan:id,name')
                ->where('team_id', $teamId)
                ->latest('id')
                ->get(),
            'paymentAttempts' => PaymentAttempt::query()
                ->with('plan:id,name')
                ->where('team_id', $teamId)
                ->latest('id')
                ->limit(20)
                ->get(),
        ]);
    }

    public function plans(PaymentProviderManager $providers): JsonResponse
    {
        $plans = Plan::query()->orderBy('price')->get();

        return response()->json([
            'plans' => $plans,
            'providers' => $providers->available(),
            'billingIntervals' => array_map(
                fn (BillingInterval $interval): array => ['value' => $interval->value, 'label' => ucfirst($interval->value)],
                BillingInterval::cases(),
            ),
        ]);
    }

    public function store(StoreSubscriptionCheckoutRequest $request, TeamContext $teamContext, StartSubscriptionCheckoutAction $checkout): JsonResponse
    {
        $validated = $request->validated();

        $attempt = $checkout->execute(
            $teamContext->teamId(),
            (int) $validated['plan_id'],
            $validated['provider'],
            BillingInterval::from($validated['billing_interval']),
            $validated['idempotency_key'],
            $request->user(),
        );

        return response()->json([
            'id' => $attempt->id,
            'status' => $attempt->status,
            'checkout_url' => $attempt->checkout_url,
            'failure_reason' => $attempt->failure_reason,
        ], $attempt->status === 'creating' ? 202 : 200);
    }

    public function refund(RefundPaymentRequest $request, PaymentAttempt $paymentAttempt, TeamContext $teamContext, RefundPaymentAction $refund): JsonResponse
    {
        $validated = $request->validated();
        $attempt = $refund->execute($teamContext->teamId(), $paymentAttempt->id, $validated['reason']);

        return response()->json(['id' => $attempt->id, 'status' => $attempt->status]);
    }
}
