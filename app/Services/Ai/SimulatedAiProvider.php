<?php

namespace App\Services\Ai;

use App\Contracts\AiProvider;
use App\Models\AiRequest;
use LogicException;

final class SimulatedAiProvider implements AiProvider
{
    public function generate(AiRequest $request, array $credentials, string $model): array
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Simulated AI responses are only available in local and testing environments.');
        }

        return [
            'provider' => 'simulated',
            'model' => $model,
            'text' => 'Simulated AI response for input: '.substr($request->input, 0, 200),
        ];
    }
}
