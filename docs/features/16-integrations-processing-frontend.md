# Integrations Processing Frontend

## 1. Purpose

This document explains the frontend page that displays the integrations connected to a team. It is designed for junior developers and testers who need to understand how the page is built, how it fetches data, how to test it manually, and how to fix it if it breaks.

The page allows a logged-in user to review which third-party systems are connected to their team, such as accounting providers. It keeps the experience simple, tenant-aware, and consistent with the rest of the Laravel + Inertia application.

## 2. Business Reason

This frontend gives teams a simple overview of the tools connected to their accounting workflow. In a SaaS product like this, teams need to know:

- which provider integrations are active;
- whether an integration is connected successfully;
- which external system ID is assigned to the team;
- whether there are missing or broken integrations.

This reduces confusion and helps administrators maintain data connections without checking the database directly.

## 3. Architecture

The integration page is a thin React/Inertia page. The backend supplies the list of integrations for the current team, and the page renders a basic table view.

Main idea:

- Laravel route returns an Inertia page;
- the page fetches tenant-scoped integration data from `/integrations/list`;
- a table is rendered with provider, status, and external ID;
- loading and empty states are handled in the component.

This follows a simple and maintainable pattern:

- Controller stays thin;
- page component handles UI state;
- API endpoint returns JSON for the page;
- team isolation is enforced in the backend.

## 4. Database Changes

This feature relies on the `integrations` table.

Expected table structure:

- `id`
- `team_id`
- `provider`
- `external_id`
- `credentials` (JSON)
- `status`
- `created_at`
- `updated_at`

This table is critical for multi-tenant isolation. Every integration row belongs to a team, and the controller filters results by `team_id`.

## 5. Eloquent Relationships

Model involved: `App\Models\Integration`

### Relationship details

- `Integration belongsTo Team`
  - Each integration belongs to one team.
  - This keeps provider connections isolated to the correct tenant.

```php
public function team(): BelongsTo
{
    return $this->belongsTo(Team::class);
}
```

This relationship is important because the UI should never show integrations from another tenant. Team-scoped queries enforce that rule.

## 6. Folder Structure

Frontend files:

- `resources/js/Pages/Integrations/Index.tsx`
- `resources/js/Layouts/AuthenticatedLayout.tsx`

Backend files:

- `app/Http/Controllers/Integrations/IntegrationController.php`
- `app/Models/Integration.php`
- `app/Policies/IntegrationPolicy.php`
- `routes/web.php`

## 7. File Paths

- Frontend page: `resources/js/Pages/Integrations/Index.tsx`
- Shared layout: `resources/js/Layouts/AuthenticatedLayout.tsx`
- Controller: `app/Http/Controllers/Integrations/IntegrationController.php`
- Model: `app/Models/Integration.php`
- Policy: `app/Policies/IntegrationPolicy.php`
- Route registration: `routes/web.php`

## 8. Flow Diagram

```text
User visits /integrations
        ↓
Laravel route resolves page
        ↓
IntegrationController::page()
        ↓
Inertia renders resources/js/Pages/Integrations/Index.tsx
        ↓
React useEffect calls fetch(route('integrations.list'))
        ↓
Backend returns tenant-scoped integrations JSON
        ↓
Table renders provider, status, external_id
```

## 9. Frontend File Review

File: `resources/js/Pages/Integrations/Index.tsx`

### Purpose

This component loads the current team's integrations and renders them in a clean table.

### Dependencies

- `@inertiajs/react` for `Head` and layout rendering
- `React` hooks: `useEffect`, `useState`
- global `route()` helper for route building

### Component behavior

1. On mount, `useEffect(() => fetchIntegrations(), [])` executes.
2. `fetchIntegrations()` sets loading to true.
3. It calls `fetch(route('integrations.list'))`.
4. The server responds with `json.integrations.data`.
5. The state is updated with `setIntegrations(...)`.
6. The table is rendered from the state.

### Line-by-line explanation

#### Imports

```ts
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';
```

- `AuthenticatedLayout` wraps the page in the app shell.
- `Head` sets the browser tab title.
- `useEffect` and `useState` are the standard React state hooks.

#### Type definition

```ts
type Integration = {
    id: number;
    provider: string;
    status: string;
    external_id?: string | null;
};
```

This ensures TypeScript knows the shape of each row. This prevents mistakes like reading a property that does not exist.

#### State

```ts
const [integrations, setIntegrations] = useState<Integration[]>([]);
const [loading, setLoading] = useState(false);
```

- `integrations` stores the current result set.
- `loading` controls whether the page shows the loading state.

#### Data fetch

```ts
const fetchIntegrations = async () => {
    setLoading(true);

    try {
        const response = await fetch(route('integrations.list')); // eslint-disable-line no-undef
        const json = await response.json();
        setIntegrations(json.integrations.data || []);
    } catch (error) {
        // ignore for now
    } finally {
        setLoading(false);
    }
};
```

The loop is small and clear:

- set loading true;
- request JSON from backend;
- parse response;
- store rows in UI state;
- always stop loading in `finally`.

#### Render table

The component renders:

- header title;
- section description;
- table with columns: Provider, Status, External ID;
- loading message if data is fetching;
- empty state if no integrations are available.

This page is intentionally simple and safe for junior developers to maintain.

## 10. Testing Procedure (Manual)

### QA steps

1. Log in as a user who belongs to a team.
2. Ensure the team has at least one integration row in the `integrations` table.
3. Visit `/integrations` in the browser.
4. Confirm the page loads without a blank screen.
5. Check that the table shows the provider name, status, and external ID.
6. Confirm that when no integrations exist, the UI shows `No integrations configured.`
7. Confirm that while fetching, the UI shows `Loading...`.

### Sample input

Browser URL:

```text
/integrations
```

API URL:

```text
/integrations/list
```

Expected JSON shape:

```json
{
  "integrations": {
    "data": [
      {
        "id": 1,
        "provider": "quickbooks",
        "status": "connected",
        "external_id": "abc-123"
      }
    ]
  }
}
```

### Expected output

- The page title should display `Integrations`.
- The table rows should match the backend response.
- No foreign team data should appear.
- The page should be inaccessible to users without the required permission.

## 11. Troubleshooting Guide

### Issue: Page shows `Loading...` forever

Possible reasons:

- the backend route `/integrations/list` is broken;
- the request returns a 500 error;
- the user is not authenticated;
- the route name is missing in Ziggy config.

Check:

- browser Network tab for `/integrations/list`;
- Laravel logs;
- the route registration in `routes/web.php`;
- team permission checks in `IntegrationController`.

### Issue: Page shows an empty state but data exists

Possible reasons:

- data belongs to another team;
- `team_id` filter is wrong;
- user has not been assigned to the right team.

Check:

- database rows in `integrations` table;
- `team_id` comparison in `IntegrationController::list()`;
- tenant context in the request.

### Issue: 403 Forbidden

Possible reasons:

- missing permission on the user role;
- policy denies access;
- user is not in the right team.

Check:

- `Image`/policy auth layer;
- `IntegrationPolicy` methods;
- user role assignment.

## 12. Maintenance Guide

- Keep the component simple and thin.
- Do not add complex business logic in the page component.
- Move filtering or transformation to backend actions if logic grows.
- If provider data grows, add pagination or server-side filtering.
- Keep the TypeScript model aligned with backend JSON keys.

## 13. Artisan Commands and Developer Notes

For this feature, the following developer tasks were relevant:

```bash
php artisan make:model Integration -m
php artisan make:policy IntegrationPolicy --model=Integration
php artisan make:controller Integrations/IntegrationController
php artisan route:list
php artisan test --filter=Integration
```

These commands help create the model, migration, policy, controller, and verify the routes and behavior.

## 14. Test Results

### Backend verification command

```bash
php artisan test --filter=Integration
```

Expected result:

- Feature tests pass for page access.
- Tenant isolation is enforced.
- Policy checks return expected results.

### Manual browser verification

```text
Open: /integrations
Expected: page loads with a table or empty state.
Check: provider names are correct.
Check: no data from another team appears.
```

## 15. Summary

This frontend is intentionally simple but important. It helps the team verify that integrations are connected, visible, and properly isolated by tenant. It also provides a safe and maintainable interface for the next stages of the application, where more providers and deeper automation will be added.

## 16. Checklist

- [x] Feature page exists
- [x] Tenant-scoped backend list endpoint exists
- [x] UI renders provider, status, and external ID
- [x] Empty and loading states exist
- [x] Policy and route protection are enforced
- [x] Documentation for junior developers is created
- [x] Manual QA steps are defined
- [x] Backend verification command is recorded
