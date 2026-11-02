# tests/Feature/Authorization/DocumentPolicyTest.php

## Purpose

Verify RBAC policy enforcement for document access within tenant boundaries.

## Dependencies

- `App\Models\Document`
- `App\Models\Role`
- `App\Models\Team`
- `App\Models\User`
- `Database\Seeders\AuthorizationSeeder`
- `Illuminate\Foundation\Testing\RefreshDatabase`

## What it tests

- An Owner role can view a document.
- A Viewer role cannot delete a document.
- A user cannot view a document belonging to another team.

## Workflow

1. Seed authorization permissions.
2. Create required teams and roles.
3. Attach roles to users.
4. Create documents and assert policy decisions.

## Expected Result

- `view` returns true for owned tenant document.
- `delete` returns false for a viewer role.
- cross-team document authorization is denied.
