<?php

namespace App\Models;

use App\Enums\BillingInterval;
use App\Traits\BelongsToTeam;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAttempt extends Model
{
    use BelongsToTeam, HasFactory;

    protected $fillable = [
        'team_id',
        'plan_id',
        'subscription_id',
        'provider',
        'status',
        'idempotency_key',
        'provider_checkout_id',
        'provider_subscription_id',
        'payment_reference',
        'amount_minor',
        'currency',
        'billing_interval',
        'checkout_url',
        'failure_reason',
        'metadata',
        'refunded_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'refunded_at' => 'datetime',
    ];

    protected function billingInterval(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => BillingInterval::from($value ?? BillingInterval::Monthly->value),
            set: fn (BillingInterval|string $value) => $value instanceof BillingInterval ? $value->value : $value,
        );
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
