# AI Assistant Processing Frontend

## 1. Purpose

This document explains the frontend for the AI assistant page. It describes how the page is built, how it sends prompts to the backend, how it loads recent requests, and how to test the flow from the browser.

The goal is to give users a simple way to submit a prompt, review request status, and see a provider response in a tenant-safe environment. The current assistant is not grounded in stored financial documents.

## 2. Business Reason

The AI assistant is a core part of the product vision. The current implementation supports:

- submit a prompt from the application UI;
- submit the request from the application UI;
- see the request as part of a recent history list;
- receive a result without leaving the app.

This feature makes the platform feel useful beyond raw document storage. It gives the user an interface to interact with the financial intelligence layer.

## 3. Architecture

The AI assistant frontend is a small Inertia page connected to backend routes.

**Production limitation:** the app can now call OpenAI with a team-owned encrypted key. The assistant does not retrieve the team's financial records, and usage/cost metering, provider-specific retry strategy, retention controls, and financial-context safety are not complete. The deterministic simulator is available only in local/testing. Do not represent the current prompt-only assistant as a financial-data-aware assistant.

Request flow:

- user opens `/ai`;
- the page loads prior requests via `ai.list`;
- the user enters a prompt;
- the page submits the prompt to `ai.create`;
- the backend creates a queued AI request record;
- the page refreshes the list after submission.

This keeps the component simple and pushes provider calls into Laravel jobs and actions. Each request stores the provider and model selected at submission time. The queue job resolves the current encrypted credential for its own team; credentials are never included in the job payload.

## 4. Database Changes

This feature relies on the `ai_requests` table.

Expected structure:

- `id`
- `team_id`
- `user_id`
- `model`
- `input`
- `response` (JSON or text payload)
- `status`
- `error_message`
- `created_at`
- `updated_at`

This table stores a request history and allows the frontend to display both queued and completed AI interactions.

## 5. Eloquent Relationships

Model involved: `App\Models\AiRequest`

### Relationship details

- `AiRequest belongsTo Team`
  - each AI request is scoped to one team;
  - this prevents cross-tenant access to prompt history.

- `AiRequest belongsTo User`
  - the user who submitted the request is recorded.

```php
public function team(): BelongsTo
{
    return $this->belongsTo(Team::class);
}

public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}
```

These relationships help with auditing and tenant isolation.

## 6. Folder Structure

Frontend files:

- `resources/js/Pages/Ai/Index.tsx`
- `resources/js/Layouts/AuthenticatedLayout.tsx`

Backend files:

- `app/Http/Controllers/Ai/AiController.php`
- `app/Models/AiRequest.php`
- `app/Jobs/ProcessAiRequestJob.php`
- `app/Actions/Ai/ProcessAiRequestAction.php`
- `routes/web.php`

## 7. File Paths

- Frontend page: `resources/js/Pages/Ai/Index.tsx`
- Shared layout: `resources/js/Layouts/AuthenticatedLayout.tsx`
- Controller: `app/Http/Controllers/Ai/AiController.php`
- Model: `app/Models/AiRequest.php`
- Job: `app/Jobs/ProcessAiRequestJob.php`
- Route registration: `routes/web.php`

## 8. Flow Diagram

```text
User visits /ai
        ↓
Laravel route loads Inertia page
        ↓
AI page runs useEffect and fetches request history
        ↓
User enters a question and clicks Send
        ↓
Frontend submits POST to route('ai.create')
        ↓
Backend creates AiRequest record with team_id and user_id
        ↓
Job starts processing asynchronously
        ↓
Response is stored and the list is refreshed
```

## 9. Frontend File Review

File: `resources/js/Pages/Ai/Index.tsx`

### Purpose

This component provides the full AI conversation UI for a user. It allows:

- entering a prompt;
- submitting a request;
- showing the request list;
- observing status and response text.

### Dependencies

- `@inertiajs/react` for `Head` and layout rendering;
- `React` state hooks;
- global `route()` helper for endpoint generation.

### Component behavior

1. On page load, it calls `fetchRequests()` in `useEffect`.
2. `fetchRequests()` gets the team’s recent request history.
3. The user types a prompt into the textarea.
4. `submit()` prevents empty input.
5. `fetch(route('ai.create'), { method: 'POST', headers: ... })` sends the request.
6. The input is cleared and the request list is refreshed.

### Line-by-line explanation

#### Imports

```ts
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';
```

- `AuthenticatedLayout` keeps the entire app shell consistent.
- `Head` updates the browser tab title.
- `useEffect` and `useState` manage the data lifecycle.

#### Type definition

```ts
type AiRequest = {
    id: number;
    model: string;
    input: string;
    response?: { text?: string } | null;
    status: string;
};
```

This keeps the UI typed and predictable. It also documents the expected JSON fields for the request history.

#### State

```ts
const [requests, setRequests] = useState<AiRequest[]>([]);
const [input, setInput] = useState('');
const [loading, setLoading] = useState(false);
```

- `requests`: stores the list of AI requests.
- `input`: stores what the user is typing.
- `loading`: handles UI loading state.

#### Fetch existing requests

```ts
const fetchRequests = async () => {
    setLoading(true);

    try {
        const res = await fetch(route('ai.list')); // eslint-disable-line no-undef
        const json = await res.json();
        setRequests(json.requests.data || []);
    } catch (e) {
        // ignore
    } finally {
        setLoading(false);
    }
};
```

This is the basic pattern needed to load history. It keeps the UI simple and the data flow clear.

#### Submit request

```ts
const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!input.trim()) return;

    setLoading(true);
    try {
        await fetch(route('ai.create'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ input }),
        });
        setInput('');
        fetchRequests();
    } catch (e) {
        // ignore
    } finally {
        setLoading(false);
    }
};
```

The current page uses `csrfFetch`, checks response status, displays request/network errors, clears the prompt only after acceptance, and refreshes the recent history. It refreshes request history periodically to show queued/processing/completed states.

## 10. Testing Procedure (Manual)

### QA steps

1. Log in as a user who belongs to a team.
2. Go to `/ai`.
3. Type a short message such as: `Summarize the latest invoice activity.`
4. Click the `Send` button.
5. Confirm the frontend sends the request and clears the textarea.
6. Confirm a new row appears under `Recent requests`.
7. Confirm the request status is shown, such as `queued`, `processing`, or `completed`.
8. Confirm the response text appears when the backend job finishes.

### Sample input

Browser URL:

```text
/ai
```

Request body:

```json
{
  "input": "Summarize the most important invoice activity for this month."
}
```

### Expected output

- A new request record is created.
- The UI updates without page reload.
- The response is displayed after processing.
- No prompt history from another team is shown.

## 11. Troubleshooting Guide

### Issue: request never appears in the list

Possible reasons:

- backend create route is failing;
- AJAX request is rejected because of CSRF/config issues;
- the page is using the wrong route name;
- the user lacks a valid team context.

Check:

- browser Network tab;
- Laravel logs;
- route names in `routes/web.php`;
- `team_id` assignment in the backend request.

### Issue: page loads but `Send` does nothing

Possible reasons:

- textarea value is empty;
- fetch fails due to network error;
- backend rejects the request with a validation error.

Check:

- console logs;
- `input.trim()` check;
- Laravel validation logic in `AiController`.

### Issue: response is missing

Possible reasons:

- request status is still queued;
- job not processed;
- `ProcessAiRequestJob` failed;
- in local/testing, the deterministic response is a demo only;
- in production, the team may not have saved an OpenAI key, or the provider configuration may have changed while the request was queued.

Check:

- configured queue worker/Horizon dashboard, if installed;
- failed jobs table;
- queue worker logs;
- `error_message` on the `ai_requests` row.

`AI_DRIVER` controls only the local/testing fallback. Select a production provider and save its team-owned credentials in the `/ai` settings form. Unknown provider keys fail explicitly.

## 12. Maintenance Guide

- Keep the page component focused on UI state and presentation.
- Move any AI business rules to queue jobs and actions.
- Never perform heavy processing directly in the UI layer.
- Use proper validation in the backend before creating request rows.
- If the response format changes, update the TypeScript model and rendering logic together.

## 13. Artisan Commands and Developer Notes

Examples of commands relevant to this feature:

```bash
php artisan make:model AiRequest -m
php artisan make:job ProcessAiRequestJob
php artisan make:controller Ai/AiController
php artisan route:list
php artisan queue:work
php artisan test --filter=Ai
```

These commands support model creation, job processing, controller wiring, queue verification, and testing.

## 14. Test Results

### Backend verification command

```bash
php artisan test --filter=Ai
```

Expected result:

- AI request creation works;
- tenant-scoping is enforced;
- queued status is stored correctly;
- policy checks pass for permitted users.

### Manual browser verification

```text
Open: /ai
Type: Summarize recent financial activity.
Expected: request is submitted and a recent entry appears.
Check: the AI status updates from queued to completed if the background job runs.
```

## 15. Summary

This frontend is the visible face of the AI layer. It is intentionally simple, but it proves the system can accept user input and route it through a tenant-aware, queue-driven workflow. It is a strong foundation for future AI assistants and higher-value financial workflows.

## 16. Checklist

- [x] Feature page exists
- [x] Input form is implemented
- [x] Request history loads from backend
- [x] Submission sends JSON to backend API
- [x] Queue-driven processing model is supported
- [x] Team isolation is enforced
- [x] Manual QA steps are documented
- [x] Backend verification command is recorded

## 17. Current Runtime Audit and Tester Setup

The original examples above describe the intended request flow. Older example code blocks are historical and do not match the current React component; use the current files listed below as the source of truth.

### What is implemented today

- The page is an authenticated, team-scoped request history and prompt form.
- `AiController` validates the prompt, stores an `AiRequest`, and dispatches `ProcessAiRequestJob`.
- `AiProvider` is the provider contract. `AiProviderManager` resolves registered implementations from `config/ai.php`.
- AI provider and model settings are stored per team. OpenAI API keys use Laravel encrypted-array casting, are never returned to Inertia/JSON, and are omitted from audit details and queued job payloads.
- Owner/Admin roles can configure the team's provider on `/ai`; other users may use AI only if they have `use_ai`.
- Supported provider: OpenAI Responses API. The model allow-list is in `config/ai.php`. Each submitted request stores a provider/model snapshot.
- `ProcessAiRequestAction` rejects queued work if a team's provider selection has changed since submission. If the provider remains selected, its latest key is resolved only for that team's job.
- The deterministic simulator is restricted to local/testing.
- OpenAI is connected through its Responses API adapter. The prompt-only assistant does not read invoices, receipts, transactions, uploads, or other application records. The simulator does not read those records either. Do not use its text for business or financial decisions.
- AI usage/cost metering, financial-context retrieval, request redaction, retention controls, and provider-specific retry policies are not implemented. This feature is not production-ready for customer financial prompts.
- The AI POST now uses the shared `csrfFetch` helper and the app layout supplies Laravel's CSRF meta token. A missing token causes a visible request error instead of sending an unsafe request.

### Files involved

- Frontend: `resources/js/Pages/Ai/Index.tsx`
- Shared CSRF utility: `resources/js/lib/csrfFetch.ts`
- HTML shell / token: `resources/views/app.blade.php`
- Controller, models, job, action, contract, provider manager, selector, and adapters: `app/Http/Controllers/Ai/AiController.php`, `app/Models/AiRequest.php`, `app/Models/TeamAiProviderSetting.php`, `app/Jobs/ProcessAiRequestJob.php`, `app/Actions/Ai/ProcessAiRequestAction.php`, `app/Actions/Ai/SaveTeamAiProviderSettingsAction.php`, `app/Contracts/AiProvider.php`, `app/Services/Ai/`
- Tests: `tests/Feature/AiFeatureTest.php`, `resources/js/lib/csrfFetch.test.ts`
- Synthetic data: `database/seeders/AiDemoSeeder.php`

### Local MySQL synthetic data

The local database used during this audit is `smart_financial_document_hub`. The idempotent demo seeder was run successfully and created three synthetic AI request rows for the `ai-demo-workspace` team.

Run only in a local or testing environment:

```bash
php artisan db:seed --class=AiDemoSeeder
```

Tester account:

```text
Email: ai.tester@local.test
Password: password
URL: /ai
```

These are local-only credentials. Never reuse them in staging or production. The sample requests are marked `[AI-DEMO]`; they contain synthetic text only. The seeder refuses to run outside `local` and `testing`.

### Verification performed in this workspace

- `php artisan db:seed --class=AiDemoSeeder --force` — completed successfully against the configured local MySQL database.
- `php artisan test` — 124 passed, 1 failed, 1 skipped before the export test fixture was corrected. See the latest test run after this correction before treating the backend suite as green.
- `npm.cmd run build` — passed before the CSRF fix; rerun after changes.
- `npm.cmd run test:unit -- --run` — one test file passed but Vitest reported a worker-start timeout while the build and PHP suite were competing for resources. Rerun serially.

### Manual smoke test

1. Start the application and a queue worker with the local environment.
2. Log in using the local-only tester account above.
3. Open `/ai`, submit `Summarize synthetic test data: three transactions total 325.75 USD.`
4. In browser DevTools, verify `POST /ai/create` returns `202` and includes an `X-CSRF-TOKEN` request header.
5. Confirm the request appears in history. With the simulated driver and a running worker, the response begins `Simulated AI response for input:`.
6. Confirm the demo banner remains visible and the UI does not imply it queried financial records.

### Remaining launch gates

- Add AI usage/cost quotas, privacy/retention controls, request redaction, and staging/browser tests before sending real customer prompts.
- Add provider retries and more failure-mode coverage; verify OpenAI behavior and organization policy in an approved staging account.
- Implement financial-context retrieval only with explicit authorization, tenant-safe source filtering, source citations, and prompt-injection safeguards.
- Keep billing provider configuration platform-wide and separate from team-level AI credentials.

## 18. Team AI provider setup

1. Run `php artisan migrate` to add `provider` to `ai_requests` and create `team_ai_provider_settings`.
2. The same migration adds `manage_ai_settings` and assigns it to existing Owner/Admin roles. New role sets receive it through `Role::defaultRolePermissions()`.
3. Sign in as an Owner/Admin, open `/ai`, choose OpenAI and an allowed model, and save the team's API key.
4. The key is encrypted at rest using `APP_KEY`. Keep `APP_KEY` stable, restrict database backups, and rotate the key by entering a new one. Never copy a tenant key into a global environment variable.
5. Submit a prompt. The database row snapshots the provider and model; the queue job uses that team's current encrypted key. A changed provider selection makes the old queued request fail closed.
6. To test without an external AI account, use the local/testing simulator. It returns synthetic text and must not be used for financial decisions.

The route names are `ai.page`, `ai.list`, `ai.create`, and `ai.settings.update`. The settings endpoint is protected by the `manage_ai_settings` authorization gate.

### Latest verification after the CSRF and export-fixture fixes

These results supersede the earlier pre-fix results in section 17:

- `php artisan test` — 128 tests, 127 passed, 1 skipped, 332 assertions.
- `npm.cmd run test:unit -- --run` — 3 test files and 4 tests passed.
- `npm.cmd run build` — TypeScript checking and production Vite build passed.
- `php artisan route:list --except-vendor` — 80 registered application routes, including `ai.page`, `ai.list`, and `ai.create`.
- `composer audit --no-interaction` — no advisories.
- `npm.cmd audit --audit-level=high` — zero vulnerabilities.
- `vendor\bin\pint --test ...` — modified PHP files passed formatting checks.
- `php artisan db:seed --class=AiDemoSeeder --force` — completed successfully in local MySQL. The seeder is repeatable and inserts three synthetic request examples.

The backend and frontend checks prove local behavior only. They do not prove a real LLM provider, live integrations, production infrastructure, PostgreSQL compatibility, or browser behavior on a deployed site.
