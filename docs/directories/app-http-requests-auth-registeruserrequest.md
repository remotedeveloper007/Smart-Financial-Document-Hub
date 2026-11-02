# app/Http/Requests/Auth/RegisterUserRequest.php

## Purpose

This file defines the request validation rules for user registration. It ensures the controller receives only valid input.

## Dependencies

- `App\Models\User`
- `Illuminate\Foundation\Http\FormRequest`
- `Illuminate\Validation\Rules`

## Inputs

- `name`
- `email`
- `password`
- `password_confirmation`

## Outputs

- A validated request instance.

## What it does

1. Authorizes every request by returning `true`.
2. Validates the registration fields:
   - `name` is required, string, and maximum 255 characters.
   - `email` is required, string, lowercase, valid email, maximum 255 characters, and unique.
   - `password` is required, confirmed, and uses Laravel password defaults.

## Usage

Injected into `App\Http\Controllers\Auth\RegisteredUserController::store`. Laravel automatically validates the request before the controller logic executes.

## Why this is useful

- Keeps validation logic out of the controller.
- Makes validation rules reusable and easier to maintain.
- Keeps the controller thin and aligned with Laravel best practices.
