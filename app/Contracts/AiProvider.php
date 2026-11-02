<?php

namespace App\Contracts;

use App\Models\AiRequest;

interface AiProvider
{
    /**
     * @param array<string, mixed> $credentials
     * @return array<string, mixed>
     */
    public function generate(AiRequest $request, array $credentials, string $model): array;
}
