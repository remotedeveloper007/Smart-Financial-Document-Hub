<?php

namespace App\Actions\Billing;

use App\Models\PaymentAttempt;
use App\Models\Subscription;
use App\Services\Billing\PaymentProviderManager;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class RefundPaymentAction
{
    public function __construct(private readonly PaymentProviderManager $providers) {}

    public function execute(int $teamId, int $attemptId, string $reason): PaymentAttempt
    {
        $attempt = DB::transaction(function () use ($teamId, $attemptId): PaymentAttempt {
            $attempt = PaymentAttempt::query()
                ->where('team_id', $teamId)
                ->lockForUpdate()
                ->findOrFail($attemptId);

            if (! in_array($attempt->status, ['succeeded', 'refunding'], true)) {
                throw new RuntimeException('Only a confirmed successful payment can be refunded.');
            }

            if ($attempt->status === 'succeeded') {
                $attempt->forceFill(['status' => 'refunding'])->save();
            }

            return $attempt;
        }, attempts: 3);

        try {
            $refund = $this->providers->get($attempt->provider)->refund($attempt, $reason);
        } catch (\Throwable $exception) {
            PaymentAttempt::withoutGlobalScopes()->whereKey($attempt->id)->where('status', 'refunding')->update([
                'status' => 'succeeded',
                'failure_reason' => $exception->getMessage(),
                'updated_at' => now(),
            ]);

            throw $exception;
        }

        $completed = in_array($refund['status'] ?? null, ['succeeded', 'processed', 'completed', 'COMPLETED'], true);

        return DB::transaction(function () use ($attempt, $completed): PaymentAttempt {
            $lockedAttempt = PaymentAttempt::withoutGlobalScopes()->lockForUpdate()->findOrFail($attempt->id);
            $lockedAttempt->forceFill([
                'status' => $completed ? 'refunded' : 'refunding',
                'refunded_at' => $completed ? now() : null,
                'failure_reason' => null,
            ])->save();

            if ($completed) {
                Subscription::withoutGlobalScopes()
                    ->where('id', $lockedAttempt->subscription_id)
                    ->update(['status' => 'canceled', 'canceled_at' => now(), 'updated_at' => now()]);
            }

            return $lockedAttempt;
        }, attempts: 3);
    }
}
