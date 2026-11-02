<?php

namespace App\Actions\Ai;

use App\Models\AiRequest;
use App\Services\Ai\AiProviderManager;
use App\Services\Ai\AiProviderSelector;
use RuntimeException;

final class ProcessAiRequestAction
{
    public function __construct(
        private readonly AiProviderManager $providerManager,
        private readonly AiProviderSelector $providerSelector,
    ) {
    }

    public function execute(AiRequest $request): array
    {
        $selectedProvider = $this->providerSelector->forTeam((int) $request->team_id);
        if ($selectedProvider === null || $selectedProvider->driver !== $request->provider) {
            throw new RuntimeException('The AI provider configuration changed before this request was processed.');
        }

        return $this->providerManager
            ->resolve($request->provider)
            ->generate($request, $selectedProvider->credentials, $request->model);
    }
}
