# app/Http/Requests/TeamUserUpdateRequest.php

## Purpose

Validate request input for updating a team member's role.

## Business Reason

Prevents invalid or missing role slugs from being used when changing a member's permissions.

## Architecture

- Extends `FormRequest`.
- Authorizes by default.
- Requires `role_slug` to exist in the `roles.slug` column.

## Dependencies

- `Illuminate\Validation\Rule`

## Flow

1. The controller receives the role update request.
2. Laravel validates the request using this class.
3. If validation passes, the action updates the role.

## Line-by-Line Explanation

- `authorize()`: returns `true` because controller authorization is handled separately.
- `rules()`: enforces the required `role_slug` rule.
