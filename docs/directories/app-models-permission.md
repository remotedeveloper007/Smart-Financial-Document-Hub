# app/Models/Permission.php

## Purpose

Represents granular access rights that can be attached to tenant roles.

## Dependencies

- `App\Models\Role`

## Inputs

- `name`
- `slug`
- `description`

## Outputs

- A persistent permission record.
- Permission-to-role relationships.

## What it does

- Stores permission metadata with a unique slug.
- Defines default application permissions.
- Enables role lookups through `roles()`.

## Relationships

- `roles()` belongs to many `Role`

## Usage

Use permissions in policy checks and gates, such as:

- `Permission::PERMISSION_VIEW_DOCUMENTS`
- `Permission::PERMISSION_MANAGE_USERS`

The `AuthServiceProvider` registers a gate for every defined permission slug.
