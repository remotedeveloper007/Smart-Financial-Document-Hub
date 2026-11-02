# app/Actions/Team/UpdateTeamMemberRoleAction.php

## Purpose

Update a team member's role within a single tenant team.

## Business Reason

Role updates must be consistent and tenant-scoped to avoid permission leaks across teams.

## Architecture

- Reads the requested role by slug and team.
- Detaches the team-scoped roles from the user.
- Attaches the new role.
- Returns the refreshed user model.

## Dependencies

- `Team`
- `User`
- `Role`

## Flow

1. `execute()` finds the team-specific role.
2. It removes any role assignments for the team from the user.
3. It attaches the new role.
4. It reloads the user data and returns it.

## Line-by-Line Explanation

- `Role::query()->where('team_id', $team->id)->where('slug', $roleSlug)->firstOrFail()`: ensures the role belongs to the current tenant.
- `detach(...)`: removes roles that belong to the same team before assigning the new role.
- `attach($role->id)`: assigns the selected role.
- `refresh()`: returns the user with updated relationship data.
