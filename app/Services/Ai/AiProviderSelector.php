<?php

namespace App\Services\Ai;

use App\Data\Ai\SelectedAiProvider;
use App\Models\TeamAiProviderSetting;

final class AiProviderSelector
{
    public function forTeam(int $teamId): ?SelectedAiProvider
    {
        $setting = TeamAiProviderSetting::withoutGlobalScopes()
            ->where('team_id', $teamId)
            ->first();

        if ($setting !== null) {
            return new SelectedAiProvider(
                $setting->provider,
                $setting->model,
                $setting->credentials?->getArrayCopy() ?? [],
            );
        }

        if (config('ai.driver') === 'simulated') {
            return new SelectedAiProvider('simulated', config('ai.model'));
        }

        return null;
    }

}
