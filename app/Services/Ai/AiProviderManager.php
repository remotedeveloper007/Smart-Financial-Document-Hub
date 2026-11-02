<?php

namespace App\Services\Ai;

use App\Contracts\AiProvider;
use LogicException;

final class AiProviderManager
{
    public function resolve(string $driver): AiProvider
    {
        $providerClass = config("ai.providers.{$driver}.class");

        if (
            ! is_string($providerClass)
            || ! class_exists($providerClass)
            || ! is_subclass_of($providerClass, AiProvider::class)
        ) {
            throw new LogicException("The AI provider [{$driver}] is not registered.");
        }

        return app($providerClass);
    }
}
