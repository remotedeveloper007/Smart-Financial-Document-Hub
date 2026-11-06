# `bootstrap/app.php`

## Purpose

This file configures Laravel's route files, web middleware, middleware order,
and exception reporting.

## Tenant middleware order

The web group contains session middleware before `ResolveTeam`. Laravel's
middleware priority list is also updated so `ResolveTeam` runs after
authentication and before `SubstituteBindings` (route model binding). This
matters because a model such as `Document` applies its tenant scope while
Laravel is binding a route parameter.

The order verified by `php artisan route:list --name=team.active.update -vv`
includes:

1. `StartSession`
2. `Authenticate`
3. `ResolveTeam`
4. `SubstituteBindings`
5. Inertia and security middleware

## Maintenance

When adding middleware that authenticates a user, resolves a tenant, binds
tenant-scoped models, or authorizes a request, preserve that order. Confirm it
with `php artisan route:list -vv` and a regression test that requests another
team's model ID.
