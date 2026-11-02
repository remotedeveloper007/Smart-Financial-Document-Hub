# app/Providers/AuthServiceProvider.php

## Purpose

Register authorization services, policies, and application gates.

## Dependencies

- `App\Models\Document`
- `App\Models\Team`
- `App\Models\Permission`
- `App\Policies\DocumentPolicy`
- `App\Policies\TeamPolicy`
- `Illuminate\Foundation\Support\Providers\AuthServiceProvider`
- `Illuminate\Support\Facades\Gate`

## Inputs

- Registered policies mapping in `$policies`
- Default permissions from `Permission::defaultPermissions()`

## Outputs

- A set of authorization gates usable via `Gate::allows()` and `User::can()`.
- Policy registration for `Document` and `Team` models.

## What it does

1. Maps `Document` and `Team` to their respective policies.
2. Registers the policies using `$this->registerPolicies()`.
3. Defines gates for every default permission slug, delegating checks to `User::hasPermission()`.

## Usage

This provider is loaded automatically by `bootstrap/providers.php`.
It allows controllers and views to use `can` gates and policies such as:

- `$request->user()->can('view_documents')`
- `Gate::allows('manage_users')`
- `$user->can('update', $team)`

## Maintenance

- Add new policy mappings to `$policies`.
- Add new permission slugs to `Permission::defaultPermissions()`.
- Keep the gate definitions in sync with permission slugs.
