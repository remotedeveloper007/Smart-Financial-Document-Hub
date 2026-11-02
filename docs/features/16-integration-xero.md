# Xero Integration

## Purpose

This file explains the Xero OAuth flow used in this project so junior developers can understand how the app connects to a third-party accounting platform step by step.

## Business reason

Xero is a popular accounting platform. A team often needs to connect Xero to pull invoices, contacts, and accounting data. This project stores the connection in a tenant-scoped integration record.

## Where the code lives

- Service: `app/Services/Integrations/XeroOAuthService.php`
- Controller: `app/Http/Controllers/Integrations/IntegrationController.php`
- Routes: `routes/web.php`
- Configuration: `config/services.php`
- Test file: `tests/Feature/XeroOAuthTest.php`

## Flow in simple English

### 1. User starts the connection
The user clicks the Xero connect button from the integration page.

### 2. Controller receives the request
The route `integrations.xero.connect` calls `IntegrationController::connectXero()`.

### 3. Random state is created
A UUID is generated and saved in the session.

### 4. Authorization URL is created
`XeroOAuthService::buildAuthorizationUrl()` composes the Xero authorization URL with:
- client_id
- redirect_uri
- response_type
- scope
- state

### 5. User is redirected to Xero
The browser goes to `https://login.xero.com/identity/connect/authorize`.

### 6. User approves access
The user signs into Xero and allows the app to access the requested data.

### 7. Callback is received
Xero sends the user back to `/integrations/xero/callback` with `code` and `state`.

### 8. State validation occurs
The controller checks whether the returned state matches the session value. If not, access is stopped.

### 9. Exchange code for tokens
The service sends a form POST to `https://identity.xero.com/connect/token` to get an access token and refresh token.

### 10. Save tokens per team
The app stores the access and refresh tokens against the current team and provider `xero` in the `integrations` table.

## Code-by-code explanation

### `buildAuthorizationUrl()`
This method constructs the URL to start the OAuth request.

It includes:
- `client_id`: app identifier
- `redirect_uri`: the callback URL in this app
- `scope`: required permissions
- `state`: security token for validation

### `exchangeCodeForTokens()`
This method performs the token exchange that turns the short-lived authorization code into long-lived tokens.

Important details:
- It uses `Http::asForm()` because OAuth token endpoints accept form data.
- `throw()` ensures failed token exchange raises an exception.
- The JSON result is saved into the integration record.

### `connectXero()`
This controller method checks permission, creates a secure state, and redirects the user to Xero.

### `xeroCallback()`
This callback validates state, exchanges the code, saves the tokens, clears the session state, and redirects back to the integrations page.

## Database effect

The `integrations` table stores:
- `team_id`
- `provider = xero`
- `external_id` (tenant id if returned)
- `credentials` JSON with access token, refresh token, expiry, and token type
- `status = enabled`

## Security notes

- The OAuth `state` value prevents tampered callbacks.
- Access is scoped to the current team.
- Any future connector should follow the same session validation pattern.

## Legacy comment rule

The older direct config-check logic was left commented in this service.
Reason:
- it keeps the earlier production-style validation visible for later review
- it allows local development and automated tests to run without real Xero secrets
- it keeps rollback and debugging easier for junior engineers

## Testing

Command run:

```bash
cd "c:\Users\Mazhar\Documents\github.com\remotedeveloper007\Smart Financial Document Hub" && php artisan test --filter=XeroOAuthTest
```

Result:
- 2 tests passed
- 11 assertions
- exit code 0

## Maintenance notes

- Add `XERO_CLIENT_ID`, `XERO_CLIENT_SECRET`, and `XERO_REDIRECT_URI` in the app environment for live use.
- Keep the provider name normalized as `xero`.
- If more Xero permissions are needed, update the scope string carefully.
