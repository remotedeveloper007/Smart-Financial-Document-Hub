<?php

namespace App\Models;

use App\Enums\BillingInterval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug', 'name', 'description', 'price', 'yearly_price', 'currency', 'features', 'provider_plan_ids',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
        'features' => 'array',
        'provider_plan_ids' => 'array',
    ];

    public function priceFor(BillingInterval $interval): string
    {
        if ($interval === BillingInterval::Monthly) {
            return (string) $this->price;
        }

        if ($this->yearly_price !== null) {
            return (string) $this->yearly_price;
        }

        if ((float) $this->price === 0.0) {
            return (string) $this->price;
        }

        throw new InvalidArgumentException("The {$this->name} plan is not available for yearly billing.");
    }

    public function supportsInterval(BillingInterval $interval): bool
    {
        return $interval === BillingInterval::Monthly
            || $this->yearly_price !== null
            || (float) $this->price === 0.0;
    }
}
