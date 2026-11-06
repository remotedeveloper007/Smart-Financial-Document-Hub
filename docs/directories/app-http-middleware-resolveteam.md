# `app/Http/Middleware/ResolveTeam.php`

## Purpose

This middleware chooses a team for each web request and sets the request's
`TeamContext`. Tenant-scoped models then use that context to filter their
queries.

## Dependencies

- The authenticated user from the request.
- The user's primary team and `team_user` memberships.
- The web session key `active_team_id`.
- `TeamContext`, which holds the team for the current request.

## Step-by-step behavior

1. Clear any old in-memory team context.
2. If the request has no signed-in user, remove the active-team session key.
3. Load the user's team memberships and primary team.
4. Use the session team only if it is in that allowed list.
5. Otherwise choose the primary team, or the first allowed membership.
6. Save this valid fallback in the session. If the user has no team, remove the
   session key.
7. Set `TeamContext` before route model binding, route handlers, and policies
   run. `bootstrap/app.php` registers this middleware after authentication in
   Laravel's middleware priority list.
8. Clear the in-memory context after resolution and response handling, even if
   a membership query, session write, binding, or controller throws an
   exception.

## Safety notes

The session is not an authorization source by itself. Every session value is
checked against current team access on each request. If a team is deleted or a
membership is removed, the stale selection cannot keep that team active.

## Verification

Run:

```bash
php artisan test --filter=TenantIsolationTest
```
