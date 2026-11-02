<?php

namespace App\Data\Ai;

final readonly class SelectedAiProvider
{
    /**
     * @param array<string, mixed> $credentials
     */
    public function __construct(
        public string $driver,
        public string $model,
        public array $credentials = [],
    ) {
    }
}
