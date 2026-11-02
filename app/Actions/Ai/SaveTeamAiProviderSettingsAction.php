<?php

namespace App\Actions\Ai;

use App\Actions\Audit\RecordAuditLogAction;
use App\Models\TeamAiProviderSetting;
use App\Services\Tenancy\TeamContext;

final class SaveTeamAiProviderSettingsAction
{
    public function __construct(
        private readonly TeamContext $teamContext,
        private readonly RecordAuditLogAction $recordAuditLogAction,
    ) {
    }

    /**
     * @param array{provider: string, model: string, api_key?: string|null, clear_api_key?: bool} $data
     */
    public function execute(array $data, ?int $userId, ?string $ip): TeamAiProviderSetting
    {
        $teamId = $this->teamContext->teamId();
        $setting = TeamAiProviderSetting::withoutGlobalScopes()
            ->firstOrNew(['team_id' => $teamId]);
        $previousProvider = $setting->provider;
        $credentials = $setting->credentials?->getArrayCopy() ?? [];
        $credentialKey = config("ai.providers.{$data['provider']}.credential_key");

        if (
            ! $setting->exists
            || $previousProvider !== $data['provider']
            || ! is_string($credentialKey)
            || ($data['clear_api_key'] ?? false) === true
        ) {
            $credentials = [];
        }

        if (
            is_string($credentialKey)
            && ($data['clear_api_key'] ?? false) !== true
            && isset($data['api_key'])
            && trim($data['api_key']) !== ''
        ) {
            $credentials[$credentialKey] = trim($data['api_key']);
        }

        $setting->fill([
            'team_id' => $teamId,
            'provider' => $data['provider'],
            'model' => $data['model'],
            'credentials' => $credentials,
        ]);
        $setting->save();

        $this->recordAuditLogAction->execute(
            $teamId,
            $userId,
            'ai.provider.settings.updated',
            TeamAiProviderSetting::class,
            $setting->id,
            ['provider' => $data['provider'], 'model' => $data['model']],
            'info',
            'Team AI provider settings updated',
            null,
            $ip,
        );

        return $setting;
    }
}
