<?php

namespace App\Http\Controllers\Ai;

use App\Http\Controllers\Controller;
use App\Actions\Ai\SaveTeamAiProviderSettingsAction;
use App\Contracts\AiProvider;
use App\Jobs\ProcessAiRequestJob;
use App\Models\AiRequest;
use App\Models\Permission;
use App\Models\TeamAiProviderSetting;
use App\Services\Ai\AiProviderSelector;
use App\Services\Tenancy\TeamContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class AiController extends Controller
{
    public function page(TeamContext $teamContext, AiProviderSelector $providerSelector): Response
    {
        $this->authorize('viewAny', AiRequest::class);
        $selectedProvider = $providerSelector->forTeam($teamContext->teamId());
        $available = $this->providerIsAvailable($selectedProvider?->driver, $selectedProvider?->credentials ?? []);
        $providers = collect(config('ai.providers'))
            ->reject(fn (array $provider): bool => ($provider['local_only'] ?? false)
                && ! app()->environment(['local', 'testing']))
            ->map(fn (array $provider, string $driver): array => [
                'id' => $driver,
                'label' => $provider['label'],
                'models' => config("ai.models.{$driver}", []),
                'requiresCredential' => filled($provider['credential_key'] ?? null),
                'credentialLabel' => $provider['credential_label'] ?? 'Provider API key',
            ])
            ->values()
            ->all();

        /*
         * Tests previously received a Blade stub, bypassing the actual Inertia component and props.
         * Render the same page in every environment so the page contract can be tested directly.
         */
        // if (app()->runningUnitTests()) {
        //     return response()->view('ai.index_stub', ['title' => 'AI Assistant']);
        // }

        return Inertia::render('Ai/Index', [
            'mode' => $selectedProvider?->driver ?? 'unavailable',
            'available' => $available,
            'selectedProvider' => $selectedProvider?->driver,
            'selectedModel' => $selectedProvider?->model,
            'providers' => $providers,
            'hasCredential' => $this->hasProviderCredential(
                $selectedProvider?->driver,
                $selectedProvider?->credentials ?? [],
            ),
            'canManageSettings' => request()->user()?->hasPermission(Permission::PERMISSION_MANAGE_AI_SETTINGS) ?? false,
        ]);
    }

    public function list(TeamContext $teamContext): JsonResponse
    {
        $this->authorize('viewAny', AiRequest::class);

        $requests = AiRequest::query()
            ->where('team_id', $teamContext->teamId())
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json(['requests' => $requests]);
    }

    public function create(Request $request, TeamContext $teamContext, AiProviderSelector $providerSelector): JsonResponse
    {
        $this->authorize('create', AiRequest::class);
        $teamId = $teamContext->teamId();
        $selectedProvider = $providerSelector->forTeam($teamId);

        if (! $this->providerIsAvailable($selectedProvider?->driver, $selectedProvider?->credentials ?? [])) {
            return response()->json([
                'message' => 'The AI provider is not configured for this environment.',
            ], 503);
        }

        $payload = $request->validate([
            'input' => ['required', 'string', 'max:'.config('ai.max_prompt_chars')],
        ]);

        $ai = AiRequest::create([
            'team_id' => $teamId,
            'user_id' => $request->user()->id,
            'provider' => $selectedProvider->driver,
            'model' => $selectedProvider->model,
            'input' => $payload['input'],
            'status' => AiRequest::STATUS_QUEUED,
        ]);

        ProcessAiRequestJob::dispatch($ai->id, $teamId);

        return response()->json(['id' => $ai->id], 202);
    }

    public function updateSettings(
        Request $request,
        TeamContext $teamContext,
        SaveTeamAiProviderSettingsAction $saveSettings,
    ): JsonResponse {
        $this->authorize(Permission::PERMISSION_MANAGE_AI_SETTINGS);

        $providers = array_keys(config('ai.providers', []));
        if (! app()->environment(['local', 'testing'])) {
            $providers = array_values(array_filter(
                $providers,
                fn (string $driver): bool => ! config("ai.providers.{$driver}.local_only", false),
            ));
        }

        $payload = $request->validate([
            'provider' => ['required', 'string', Rule::in($providers)],
            'model' => ['required', 'string', Rule::in(config('ai.models.'.$request->input('provider'), []))],
            'api_key' => ['nullable', 'string', 'max:512'],
            'clear_api_key' => ['sometimes', 'boolean'],
        ]);

        $existing = TeamAiProviderSetting::withoutGlobalScopes()
            ->where('team_id', $teamContext->teamId())
            ->where('provider', $payload['provider'])
            ->first();
        $hasSubmittedKey = is_string($payload['api_key'] ?? null) && trim($payload['api_key']) !== '';
        $existingCredentials = $existing?->credentials?->getArrayCopy() ?? [];
        $providerConfig = config("ai.providers.{$payload['provider']}", []);
        $credentialKey = $providerConfig['credential_key'] ?? null;
        $hasExistingCredential = is_string($credentialKey)
            && filled($existingCredentials[$credentialKey] ?? null);

        if (
            is_string($credentialKey)
            && ! ($payload['clear_api_key'] ?? false)
            && ! $hasSubmittedKey
            && ! $hasExistingCredential
        ) {
            throw ValidationException::withMessages([
                'api_key' => 'Provide a provider API key or keep the existing key.',
            ]);
        }

        $setting = $saveSettings->execute(
            $payload,
            $request->user()->id,
            $request->ip(),
        );

        return response()->json([
            'provider' => $setting->provider,
            'model' => $setting->model,
            'hasCredential' => $this->hasProviderCredential(
                $setting->provider,
                $setting->credentials?->getArrayCopy() ?? [],
            ),
            'available' => $this->providerIsAvailable(
                $setting->provider,
                $setting->credentials?->getArrayCopy() ?? [],
            ),
        ]);
    }

    /**
     * @param array<string, mixed> $credentials
     */
    private function providerIsAvailable(?string $driver, array $credentials): bool
    {
        if (! is_string($driver)) {
            return false;
        }

        $provider = config("ai.providers.{$driver}");
        if (! is_array($provider)) {
            return false;
        }

        $providerClass = $provider['class'] ?? null;
        if (
            ! is_string($providerClass)
            || ! class_exists($providerClass)
            || ! is_subclass_of($providerClass, AiProvider::class)
        ) {
            return false;
        }

        if (($provider['local_only'] ?? false) && ! app()->environment(['local', 'testing'])) {
            return false;
        }

        $credentialKey = $provider['credential_key'] ?? null;

        return ! is_string($credentialKey) || filled($credentials[$credentialKey] ?? null);
    }

    /**
     * @param array<string, mixed> $credentials
     */
    private function hasProviderCredential(?string $driver, array $credentials): bool
    {
        $credentialKey = is_string($driver)
            ? config("ai.providers.{$driver}.credential_key")
            : null;

        return is_string($credentialKey) && filled($credentials[$credentialKey] ?? null);
    }
}
