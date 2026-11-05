<?php

namespace App\Contracts\Billing;

use App\Models\PaymentAttempt;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;

interface PaymentProvider
{
    public function key(): string;

    public function configured(): bool;

    public function createCheckout(PaymentAttempt $attempt, Plan $plan, User $user): array;

    public function parseWebhook(Request $request): array;

    public function refund(PaymentAttempt $attempt, string $reason): array;
}
