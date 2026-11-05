<?php

namespace App\Http\Controllers\Billing;

use App\Actions\Billing\ProcessPaymentWebhookAction;
use App\Http\Controllers\Controller;
use App\Services\Billing\PaymentProviderManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

final class PaymentWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        string $provider,
        PaymentProviderManager $providers,
        ProcessPaymentWebhookAction $processWebhook,
    ): JsonResponse {
        try {
            $gateway = $providers->get($provider);
        } catch (InvalidArgumentException) {
            return response()->json(['message' => 'Unsupported payment provider.'], 404);
        }

        try {
            $event = $gateway->parseWebhook($request);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 401);
        }

        try {
            $processed = $processWebhook->execute($provider, $event);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['received' => true, 'duplicate' => ! $processed]);
    }
}
