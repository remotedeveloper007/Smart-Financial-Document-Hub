# app/Actions/Team/RemoveTeamMemberAction.php

## Purpose

Remove a user from a tenant team.

## Business Reason

Tenant membership must be reversible while preserving data integrity and user records.

## Architecture

- Detaches the user from the `team_user` pivot.
- Updates the user's primary `team_id` if they were assigned to the removed team.
- Keeps the user record intact for audit and future reuse.

## Dependencies

- `Team`
- `User`

## Flow

1. `action->execute()` detaches the user from the team.
2. If the removed team was the user's primary team, the action selects another team membership if available.
3. It updates `team_id` to the replacement team or null.

## Line-by-Line Explanation

- `team->members()->detach($user->id)`: removes the membership link.
- `if ($user->team_id === $team->id)`: checks if the removed team was the user's assigned primary team.
- `replacement = $user->teams()->where('team_id', '!=', $team->id)->first()`: picks another team membership when available.
- `save()`: persists the change.
