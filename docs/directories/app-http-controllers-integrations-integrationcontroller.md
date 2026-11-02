# app/Http/Controllers/Integrations/IntegrationController.php

## Purpose

Handle tenant-scoped integration settings for the current team, including page rendering and CRUD operations.

## Business Reason

The controller centralizes authorization and validation so the rest of the application can manage integration credentials safely without duplicating rules.

## Architecture

- Renders the Inertia settings page.
- Lists only integrations for the current team.
- Validates provider, status, and credentials payloads.
- Enforces policy checks before creating, updating, or deleting a record.

## Dependencies

- `App\Models\Integration`
- `App\Services\Tenancy\TeamContext`
- `Illuminate\Http\Request`
- `Inertia\Inertia`

## Inputs

- Page render: no payload.
- List: current team context.
- Store: provider, external_id, status, credentials.
- Update: same fields plus route model binding.
- Delete: route model binding and current team context.

## Outputs

- JSON list of current team integrations.
- Created integration payload with HTTP 201.
- Updated integration payload with HTTP 200.
- Empty 204 response on successful deletion.

## Usage

```php
Route::post('/integrations', [IntegrationController::class, 'store']);
Route::put('/integrations/{integration}', [IntegrationController::class, 'update']);
Route::delete('/integrations/{integration}', [IntegrationController::class, 'destroy']);
```

## Key logic

- `page()` checks `viewAny` authorization and renders the Inertia screen.
- `list()` applies the team_id filter from `TeamContext`.
- `store()` validates uniqueness per team and saves the payload.
- `update()` and `destroy()` enforce team ownership before changing any row.
- Chunk upload sessions are bound to both the active team and integration. Filenames must be plain CSV names, IDs/checksums/sizes are validated, and assembled bytes must match stored size and checksum metadata.
- A file-path import requires a matching session, integration, team, private assembled-file path, and explicit clean ClamAV sidecar. A pending, failed, infected, or mismatched scan is rejected before the import job is dispatched.
