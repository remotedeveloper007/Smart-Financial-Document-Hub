<?php

namespace App\Http\Controllers\Billing;

use App\Actions\Billing\CreateSubscriptionAction;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\Tenancy\TeamContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class SubscriptionController extends Controller
{
    public function page(): Response
    {
        return Inertia::render('Billing/Plans');
    }

    public function plans(): JsonResponse
    {
        $plans = Plan::query()->orderBy('price')->get();

        return response()->json(['plans' => $plans]);
    }

    public function store(Request $request, TeamContext $teamContext, CreateSubscriptionAction $createSubscriptionAction): JsonResponse
    {
        $validated = $request->validate(['plan_id' => 'required|exists:plans,id']);

        $subscription = $createSubscriptionAction->execute($teamContext->teamId(), (int) $validated['plan_id']);

        return response()->json($subscription, 201);
    }
}
