# AI Provider Configuration Files

This guide covers every new file in the team AI-provider milestone. The related feature flow is documented in `../features/18-team-ai-provider-settings.md`.

## `app/Contracts/AiProvider.php`

- **Purpose:** defines the common service boundary for AI providers.
- **Dependencies:** `AiRequest`.
- **Input:** one stored request, provider credentials as an array, and an allow-listed model name.
- **Output:** an array containing a normalized response payload.
- **Usage:** every provider adapter must implement `generate()`. Add providers to the `providers` registry in `config/ai.php`.

## `app/Data/Ai/SelectedAiProvider.php`

- **Purpose:** carries the selected driver, model, and credentials between the selector and provider action.
- **Dependencies:** none.
- **Input:** driver name, model name, optional credential array.
- **Output:** immutable value object.
- **Usage:** constructed by `AiProviderSelector`; not serialized into the queue payload.

## `app/Models/TeamAiProviderSetting.php`

- **Purpose:** stores one AI provider configuration per team.
- **Dependencies:** Laravel Eloquent, `BelongsToTeam`, `Team`.
- **Input:** `team_id`, provider, model, and credential array.
- **Output:** a tenant-scoped Eloquent model; credentials are encrypted and hidden from serialization.
- **Usage:** query settings in trusted backend services. Never return the model's credential value to frontend code.

## `app/Services/Ai/AiProviderManager.php`

- **Purpose:** resolve a provider adapter by its configured driver key.
- **Dependencies:** Laravel container and `config/ai.php`.
- **Input:** provider key such as `openai`.
- **Output:** an object implementing `AiProvider`; an unregistered/invalid driver raises `LogicException`.
- **Usage:** called by `ProcessAiRequestAction`, not by controllers.

## `app/Services/Ai/AiProviderSelector.php`

- **Purpose:** find the active provider configuration for a specified team.
- **Dependencies:** `TeamAiProviderSetting` and app AI configuration.
- **Input:** integer team ID.
- **Output:** `SelectedAiProvider`, or `null` when no production provider is configured.
- **Usage:** called before request creation and again by the queue action. Explicit team filtering prevents credential crossover.

## `app/Services/Ai/OpenAiProvider.php`

- **Purpose:** translate an AI request to the OpenAI Responses API and normalize its result.
- **Dependencies:** `AiProvider`, Laravel HTTP client, OpenAI config.
- **Input:** request prompt, team-owned API key, selected model.
- **Output:** provider/model/text and provider usage details.
- **Usage:** reached through `AiProviderManager`. HTTP/network/response errors are surfaced to the job; the secret is not logged.

## `app/Services/Ai/SimulatedAiProvider.php`

- **Purpose:** provide deterministic responses for local development and automated tests.
- **Dependencies:** `AiProvider`, `AiRequest`.
- **Input:** request and selected model.
- **Output:** synthetic text explicitly marked by the simulator response type.
- **Usage:** local/testing only. It never reads financial records and is not suitable for financial decisions.

## `app/Actions/Ai/SaveTeamAiProviderSettingsAction.php`

- **Purpose:** save the active team's provider settings and record a secret-free audit event.
- **Dependencies:** `TeamContext`, `RecordAuditLogAction`, `TeamAiProviderSetting`.
- **Input:** validated provider/model and optional replacement/clear-key fields; actor ID and IP.
- **Output:** the saved setting model.
- **Usage:** invoked only after `manage_ai_settings` authorization and request validation in `AiController`.

## `database/migrations/2026_11_02_000001_add_team_ai_provider_settings.php`

- **Purpose:** add request provider snapshots, encrypted team settings storage, and the settings permission.
- **Dependencies:** existing `ai_requests`, `teams`, `permissions`, `roles`, and `permission_role` tables.
- **Input:** no runtime input; migration uses the database schema and current timestamp.
- **Output:** a reversible schema change and permission assignments for existing Owner/Admin roles.
- **Usage:** run with `php artisan migrate`; do not test rollback on a production database.
