# app/Actions/Auth/RegisterUserAction.php

## Purpose

This file contains the registration business logic separated from the controller. It keeps the controller thin and makes the registration workflow easier to test and maintain.

## Dependencies

- `App\Models\User`
- `App\Models\Team`
- `Illuminate\Auth\Events\Registered`

## Inputs

- `array $data`
  - `name`
  - `email`
  - `password`

## Outputs

- Returns a fully created `User` model instance.

## What it does

1. Creates a new `User` record.
2. Creates a new personal `Team` record.
3. Associates the user with the created team.
4. Persists the user team relationship.
5. Adds the user to the team membership pivot.
6. Fires the `Registered` event for email verification and other listeners.

## Usage

Used by `App\Http\Controllers\Auth\RegisteredUserController` in the `store` method. The controller delegates the registration workflow to this action.

## Why this is useful

- Keeps controller logic simple.
- Supports future changes to registration flow without controller changes.
- Makes registration easier to reuse and test.
