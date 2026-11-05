<?php

namespace App\Data\Billing;

use RuntimeException;

final class Money
{
    private const ZERO_DECIMAL_CURRENCIES = [
        'BIF', 'CLP', 'DJF', 'GNF', 'ISK', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG',
        'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF',
    ];

    private const THREE_DECIMAL_CURRENCIES = ['BHD', 'IQD', 'JOD', 'KWD', 'LYD', 'OMR', 'TND'];

    public static function toMinorUnits(string $amount, string $currency): int
    {
        $precision = self::precision($currency);
        if (! preg_match('/^\d+(?:\.\d+)?$/', $amount)) {
            throw new RuntimeException('Plan price must be a non-negative decimal amount.');
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        if (strlen($fraction) > $precision && trim(substr($fraction, $precision), '0') !== '') {
            throw new RuntimeException('Plan price has more fractional digits than its currency supports.');
        }

        if ($precision === 0) {
            return (int) $whole;
        }

        return ((int) $whole * (10 ** $precision))
            + (int) str_pad(substr($fraction, 0, $precision), $precision, '0');
    }

    public static function fromMinorUnits(int $amount, string $currency): string
    {
        $precision = self::precision($currency);
        if ($precision === 0) {
            return (string) $amount;
        }

        $factor = 10 ** $precision;

        return intdiv($amount, $factor).'.'.str_pad((string) ($amount % $factor), $precision, '0', STR_PAD_LEFT);
    }

    private static function precision(string $currency): int
    {
        $currency = strtoupper($currency);
        if (in_array($currency, self::ZERO_DECIMAL_CURRENCIES, true)) {
            return 0;
        }
        if (in_array($currency, self::THREE_DECIMAL_CURRENCIES, true)) {
            return 3;
        }

        return 2;
    }
}
