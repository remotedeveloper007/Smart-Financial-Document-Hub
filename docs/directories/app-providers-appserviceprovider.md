# `app/Providers/AppServiceProvider.php`

## Purpose

Registers application-wide container bindings and boot-time configuration.

## Dependencies

- `App\Contracts\MalwareScanner` - the abstraction resolved by scan jobs.
- `App\Services\Security\ClamAvScanner` - configured production implementation.
- Existing Laravel service-provider and facade APIs used by the rest of the application.

## Inputs and outputs

- Input: Laravel application container during provider registration.
- Output: resolving `MalwareScanner` yields `ClamAvScanner`, unless a test or another provider explicitly replaces the binding.

## Usage

Add service bindings in `register()`, not in a controller or queued job. Replace the interface binding in tests to avoid contacting a real scanner.
