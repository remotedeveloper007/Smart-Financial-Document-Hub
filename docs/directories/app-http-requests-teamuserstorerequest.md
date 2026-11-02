# app/Http/Requests/TeamUserStoreRequest.php

## Purpose

Validate request input for team member creation.

## Business Reason

Ensures only valid member details are submitted and prevents invalid role slugs from reaching business logic.

## Architecture

- Extends `FormRequest`.
- Authorizes the request by default.
- Validates required `name` and `email`.
- Ensures `role_slug` exists in the `roles.slug` column.

## Dependencies

- `Illuminate\Validation\Rule`

## Flow

1. The controller receives the request.
2. Laravel validates the request using this class.
3. If validation passes, the controller executes the action.

## Line-by-Line Explanation

- `authorize()`: returns `true` because route-level authorization is handled in the controller.
- `rules()`: defines the validation rules for `name`, `email`, and `role_slug`.
