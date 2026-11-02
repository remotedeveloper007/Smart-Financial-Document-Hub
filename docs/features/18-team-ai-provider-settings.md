# Team AI Provider Settings

## 1. Purpose

Allow each team to choose a supported AI provider and model without changing the assistant's core request flow. In this milestone, the supported real provider is OpenAI. A simulator remains available only in local/testing.

## 2. Business reason

Teams need ownership of their AI provider choice and credentials. A team-owned configuration also prevents one tenant's key from being used for another tenant's prompts.

## 3. Architecture

The React page at `/ai` displays the settings form only to users with `manage_ai_settings` (Owner/Admin by default). `AiController` validates provider/model choices and delegates persistence to `SaveTeamAiProviderSettingsAction`. That action writes a team-scoped model with Laravel encrypted-array casting and records a secret-free audit event.

When a user submits a prompt, the controller snapshots the selected provider and model on the `ai_requests` row and dispatches the existing queue job. The job still contains only its request ID and team ID. `ProcessAiRequestAction` resolves the selected provider through `AiProviderSelector` and `AiProviderManager`; if the current team provider no longer matches the snapshot, processing stops instead of sending the prompt to a different provider. OpenAI credentials are loaded from that same team's encrypted setting at execution time.

```text
Owner/Admin -> AI settings form -> validation -> encrypted team setting
User prompt -> AiRequest(provider/model snapshot) -> queue job(team/request IDs)
           -> team provider selector -> provider contract -> OpenAI Responses API
```

The assistant sends only the user-entered prompt. It does not retrieve invoices, transactions, uploads, or other financial records.

### Step-by-step backend flow

1. `GET /ai` calls `AiController::page()`. The controller first authorizes `viewAny` on `AiRequest`, then uses the current `TeamContext` to load only that team's provider record.
2. The controller sends the browser a safe provider catalog, selected provider/model, availability, and a boolean saying whether a key exists. It never sends credential contents.
3. `POST /ai/settings` first checks the `manage_ai_settings` gate. It validates the provider key, configured model allow-list, and key input. Missing keys cannot be used to activate OpenAI, but an existing key can be retained without resending it.
4. `SaveTeamAiProviderSettingsAction` obtains the team ID from trusted server context, encrypts the credential through the model cast, and writes an audit event containing only provider and model.
5. `POST /ai/create` checks `create` on `AiRequest`, enforces provider availability, validates the prompt length, and stores the selected provider/model with the prompt. It dispatches `ProcessAiRequestJob` with only the request ID and team ID.
6. The job establishes `TeamContext`, looks up the request by both IDs, and calls `ProcessAiRequestAction`. The action checks that the team's currently selected provider still matches the request snapshot before calling the adapter.
7. `OpenAiProvider` sends the prompt using the team's encrypted key, configured API base URL, timeout, and snapshotted model. It converts the API result to provider/model/text/usage. HTTP or malformed-response errors fail the queued request; they are not returned as success.

### Route contract

| Route name | Method and path | Access | Input | Success |
|---|---|---|---|---|
| `ai.page` | `GET /ai` | Authenticated; `viewAny` | None | Inertia `Ai/Index` and non-secret settings props |
| `ai.list` | `GET /ai/list` | Authenticated; `viewAny` | Pagination query | Paginated requests from the active team |
| `ai.create` | `POST /ai/create` | Authenticated; `create`; throttled | `input` string | `202` with request ID |
| `ai.settings.update` | `POST /ai/settings` | Authenticated; `manage_ai_settings` | Provider, model, optional replacement key or clear flag | Provider/model and key-presence boolean only |

## 4. Database changes

Migration `2026_11_02_000001_add_team_ai_provider_settings.php`:

- adds `provider` to `ai_requests`, defaulting old rows to `simulated`;
- creates `team_ai_provider_settings` with a unique `team_id`, provider, model, encrypted credentials text, and timestamps;
- adds `manage_ai_settings` to the permissions table and attaches it to existing Owner/Admin roles.

The `TeamAiProviderSetting` model has a `belongsTo(Team::class)` relation. `AiRequest` also belongs to its team and user and is tenant-scoped. The setting is unique per team, so a team has one active provider configuration.

## 5. Folder structure and files

- `app/Contracts/AiProvider.php` — provider interface.
- `app/Data/Ai/SelectedAiProvider.php` — immutable selected provider/model/credential value.
- `app/Services/Ai/AiProviderManager.php` — resolves configured provider implementations.
- `app/Services/Ai/AiProviderSelector.php` — selects a team's provider; falls back to local simulation only when configured.
- `app/Services/Ai/OpenAiProvider.php` — OpenAI Responses API adapter.
- `app/Services/Ai/SimulatedAiProvider.php` — local/testing-only simulator.
- `app/Models/TeamAiProviderSetting.php` — encrypted per-team settings model.
- `app/Actions/Ai/SaveTeamAiProviderSettingsAction.php` — persistence and safe audit event.
- `app/Http/Controllers/Ai/AiController.php` — page, request, and settings route orchestration.
- `resources/js/Pages/Ai/Index.tsx` — team settings and prompt UI.
- `routes/web.php` — `ai.settings.update` route registration.
- `config/ai.php` — provider registry, model allow-list, URL and timeout.
- `tests/Feature/AiFeatureTest.php` — authorization, secret storage/redaction, and mocked provider coverage.

See `docs/directories/ai-provider-configuration.md` for file-by-file inputs, outputs, dependencies, and usage.

## 6. Security and operational notes

- API keys are encrypted with Laravel's `APP_KEY`; preserve that key and protect backups.
- Keys are not included in the Inertia props, JSON response, audit changes, or queued job payload.
- Only Owner/Admin roles may update provider settings by default. AI request access remains controlled by `use_ai`.
- OpenAI calls are external data processing. Do not submit customer financial information until privacy, retention, redaction, and organization approval are complete.
- The request-size limit is configured by `AI_MAX_PROMPT_CHARS`; model names are allow-listed in `config/ai.php`.
- The OpenAI timeout and API base URL are deployment config. Team API keys are managed in the authenticated UI, not `.env`.
- Provider switching does not silently re-route already queued prompts. Those jobs fail with a safe error if the provider differs from their snapshot.

## 7. Developer commands

```bash
php artisan migrate
php artisan route:list --path=ai
php artisan test --filter=Ai
vendor/bin/pint --test app/Actions/Ai app/Contracts/AiProvider.php app/Data/Ai app/Http/Controllers/Ai app/Models/AiRequest.php app/Models/TeamAiProviderSetting.php app/Services/Ai tests/Feature/AiFeatureTest.php
npm run build
```

On Windows PowerShell, use `vendor\bin\pint` and `npm.cmd` if those command shims are required.

## 8. Test procedure and expected behavior

Automated checks prove:

- a user without `manage_ai_settings` receives HTTP 403;
- a permitted team admin can save an OpenAI key and receives only provider/model/key-presence metadata;
- the database value does not contain the plaintext API key;
- the Inertia page reports key presence but never returns the secret;
- an OpenAI HTTP fake receives the selected model, prompt, and team credential and returns normalized text/usage;
- existing simulator, tenant-isolation, request-authorization, and production-unavailable behavior remains covered.

Verification in this workspace:

- `php artisan test` — 141 tests, 140 passed, 1 skipped, 422 assertions. The skipped test requires a live ClamAV daemon.
- `npm.cmd run build` — TypeScript check and production Vite build passed.
- `npm.cmd run test:unit -- --run` — 3 test files and 4 tests passed.
- `vendor\bin\pint --test ...` — passed for all changed PHP files.
- `php artisan route:list --path=ai` — AI page, create, list, and settings routes are registered.

External OpenAI account behavior is not covered by HTTP fakes.

## 9. Troubleshooting

- **`Table '...team_ai_provider_settings' doesn't exist`:** the app code is deployed but the AI settings migration has not run on this database. Confirm with `php artisan migrate:status`. For the full deployment migration sequence, run `php artisan migrate` after reviewing and backing up the target database. If you only need this already-reviewed additive migration and its prerequisite tables exist, run `php artisan migrate --path=database/migrations/2026_11_02_000001_add_team_ai_provider_settings.php`, then confirm the table exists with `Schema::hasTable('team_ai_provider_settings')`. Do not create the table manually; Laravel must record the migration so future deployments stay consistent.
- **Save returns 403:** use a team Owner/Admin or assign the `manage_ai_settings` permission to the user's team role.
- **Save reports a missing key:** enter a key, or retain an existing key. Clearing the key leaves OpenAI unavailable until a new key is saved.
- **Request returns 503:** confirm the selected provider has a saved key and that the model is in `config/ai.php`.
- **Queued request fails after changing settings:** this is intentional fail-closed behavior; submit a new request using the current provider.
- **Authentication fails at OpenAI:** rotate the team key through `/ai`; never include it in support tickets or logs.
- **Encrypted credentials stop decrypting:** confirm the deployment preserved the original `APP_KEY`. Do not change it without a planned credential re-encryption procedure.

## 10. Maintenance and known limitations

Implement future providers by adding an `AiProvider` adapter, registering it and its allow-listed models in `config/ai.php`, and adding contract tests. Do not couple provider-specific HTTP calls to controllers or jobs.

This feature does not implement AI cost metering, quotas, financial data retrieval, citations, prompt-injection defense, retention settings, or complete provider retry strategy. It is not a production-ready financial assistant and does not complete platform billing-provider selection.
