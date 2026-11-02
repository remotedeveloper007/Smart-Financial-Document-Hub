# app/Http/Controllers/Team/TeamUserController.php

## Purpose

Handle tenant team member management requests in a thin controller.

## Business Reason

A dedicated controller keeps team membership actions separated from other user and profile flows, and enforces permission checks in one place.

## Architecture

- Uses dependency-injected actions for create, update, and remove operations.
- Uses `TeamContext` to resolve the active tenant team.
- Uses policy authorization for `manageUsers`.
- Returns Inertia page render for the team members page.
- Exposes JSON API endpoints for the frontend management page.

## Dependencies

- `CreateTeamMemberAction`
- `UpdateTeamMemberRoleAction`
- `RemoveTeamMemberAction`
- `TeamUserStoreRequest`
- `TeamUserUpdateRequest`
- `TeamContext`
- `Role`
- `User`

## Flow

1. `page()` resolves the current team and returns the Inertia page.
2. `index()` returns all members and roles as JSON.
3. `store()` validates input and delegates member creation to `CreateTeamMemberAction`.
4. `update()` validates role updates and delegates to `UpdateTeamMemberRoleAction`.
5. `destroy()` removes a member via `RemoveTeamMemberAction`.

## Line-by-Line Explanation

- `page(TeamContext $teamContext)`: fetches the current tenant and renders the page.
- `index(TeamContext $teamContext)`: loads the current team members and available roles.
- `store(...)`: authorizes access, creates or invites a member, and returns created member data.
- `update(...)`: ensures the requested user belongs to the team and updates the team-scoped role.
- `destroy(...)`: prevents removing non-members and removes a team member.
- `serializeMember()`: normalizes the user payload for frontend consumption.
