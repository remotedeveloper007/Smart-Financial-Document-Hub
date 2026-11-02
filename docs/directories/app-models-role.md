# app/Models/Role.php

## Purpose

Represents a tenant-specific role with permissions and user assignments.

## Dependencies

- `App\Models\Permission`
- `App\Models\Team`
- `App\Models\User`

## Inputs

- `team_id`
- `name`
- `slug`
- `description`

## Outputs

- A persistent role record for a tenant team.
- Role-to-user and role-to-permission relationships.

## What it does

- Stores tenant context through `team_id`.
- Creates default roles and attaches permissions using `CreateDefaultRolesAction`.
- Provides helper data for role permission mapping.

## Relationships

- `team()` belongs to `Team`
- `permissions()` belongs to many `Permission`
- `users()` belongs to many `User`

## Usage

Used during tenant onboarding and authorization checks.
Role assignment is managed through the `role_user` pivot table.
