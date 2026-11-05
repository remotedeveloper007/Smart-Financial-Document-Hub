<?php

namespace App\Enums;

use Carbon\CarbonInterface;

enum BillingInterval: string
{
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    public function addTo(CarbonInterface $date): CarbonInterface
    {
        return match ($this) {
            self::Monthly => $date->copy()->addMonthNoOverflow(),
            self::Yearly => $date->copy()->addYearNoOverflow(),
        };
    }

    public function stripeInterval(): string
    {
        return match ($this) {
            self::Monthly => 'month',
            self::Yearly => 'year',
        };
    }

    public function providerInterval(): string
    {
        return match ($this) {
            self::Monthly => 'MONTH',
            self::Yearly => 'YEAR',
        };
    }

    public function razorpayPeriod(): string
    {
        return match ($this) {
            self::Monthly => 'monthly',
            self::Yearly => 'yearly',
        };
    }
}
