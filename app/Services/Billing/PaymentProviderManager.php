<?php

namespace App\Services\Billing;

use App\Contracts\Billing\PaymentProvider;
use InvalidArgumentException;

final class PaymentProviderManager
{
    /**
     * @param  iterable<PaymentProvider>  $providers
     */
    public function __construct(private readonly iterable $providers) {}

    public function get(string $key): PaymentProvider
    {
        foreach ($this->providers as $provider) {
            if ($provider->key() === $key) {
                return $provider;
            }
        }

        throw new InvalidArgumentException('Unsupported payment provider.');
    }

    public function available(): array
    {
        $providers = [];

        foreach ($this->providers as $provider) {
            if ($provider->configured()) {
                $providers[] = $provider->key();
            }
        }

        return $providers;
    }
}
