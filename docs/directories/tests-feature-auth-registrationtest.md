# tests/Feature/Auth/RegistrationTest.php

## Purpose

This file verifies the authentication registration workflow and the request validation logic.

## Dependencies

- `App\Models\User`
- `Illuminate\Foundation\Testing\RefreshDatabase`
- `Tests\TestCase`

## Tests included

- `test_registration_screen_can_be_rendered`
  - Confirms the registration page loads successfully.
- `test_new_users_can_register`
  - Confirms valid registration data creates a user and redirects to the dashboard.
- `test_new_user_registration_creates_a_personal_team`
  - Confirms registration creates a user personal team and team membership pivot.
- `test_registration_fails_with_missing_name`
  - Confirms missing required fields trigger validation errors.

## Why this is useful

- Ensures the registration flow works end to end.
- Provides coverage for validation and tenant-aware onboarding.
- Helps junior developers understand how feature tests are structured.
