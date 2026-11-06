# `app/Http/Middleware/HandleInertiaRequests.php`

## Purpose

This middleware shares server-side page data with React pages through Inertia.
It provides the active team and the teams the signed-in user may choose.

## Dependencies

- `Request` identifies the authenticated user.
- `TeamContext` provides the team selected for this request.
- `User::teams()` and `User::team` provide only the user's allowed teams.
- `Permission` and Laravel `Gate` provide the existing permission props.

## Step-by-step behavior

1. Read the user authenticated for the current request.
2. Share the selected team as `tenant.activeTeam`.
3. Build `tenant.teams` from team memberships and the user's primary team.
4. Return only each team's numeric ID and name; do not share team records or
   unrelated fields.
5. Keep existing user and permission props available to the frontend.

Guests receive an empty teams list and no active team. The frontend display is
not an authorization check; the switch controller and backend policies remain
the security boundary.
