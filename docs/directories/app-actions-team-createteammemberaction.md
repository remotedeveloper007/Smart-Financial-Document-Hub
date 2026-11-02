# app/Actions/Team/CreateTeamMemberAction.php

## Purpose

Create or invite a new tenant team member and assign a role.

## Business Reason

Centralize team member creation logic so the controller remains thin and the code is reusable.

## Architecture

- Checks for existing users by email.
- Creates a new user if necessary.
- Associates the user with the current team.
- Assigns the requested role via `role_slug`.

## Dependencies

- `HashManager`
- `Team`
- `User`
- `Role`

## Flow

1. `execute()` looks for a user with the supplied email.
2. If not found, it creates a new user with a secure random password.
3. It attaches the user to the team membership pivot.
4. It assigns the role from the current team using `role_slug`.

## Line-by-Line Explanation

- `User::query()->firstWhere('email', $data['email'])`: prevents duplicate user accounts.
- `HashManager`: creates a safe temporary password for invited users.
- `team->addMember($user)`: ensures the user belongs to the tenant team.
- `assignRole(...)`: only assigns roles that exist for the tenant.
