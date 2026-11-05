<?php

namespace App\Models;

use App\Enums\BillingInterval;
use App\Traits\BelongsToTeam;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use BelongsToTeam, HasFactory;

    protected $fillable = [
        'team_id', 'plan_id', 'status', 'starts_at', 'ends_at', 'meta', 'billing_interval',
        'provider', 'provider_subscription_id', 'current_period_start', 'current_period_end', 'canceled_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'canceled_at' => 'datetime',
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

    public function paymentAttempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class);
    }
}
