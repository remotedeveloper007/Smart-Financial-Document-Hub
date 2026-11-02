# Zoho Books Integration

## Purpose

This document explains how the Zoho Books integration works in this project and how junior developers can follow the same flow for future integrations.

## Business reason

Teams often use accounting tools such as Zoho Books to sync financial data from their business operations. This project needs a secure way to connect a tenant to Zoho Books, store tokens, and reuse that connection later for imports or sync jobs.

## Where the code lives

- Service: `app/Services/Integrations/ZohoBooksOAuthService.php`
- Controller: `app/Http/Controllers/Integrations/IntegrationController.php`
- Routes: `routes/web.php`
- Configuration: `config/services.php`
- Test file: `tests/Feature/ZohoBooksOAuthTest.php`

## Flow in simple English

### 1. User clicks connect
A logged-in user with permission opens the integration page. They click the Zoho Books connection action.

### 2. Route triggers controller
The route `integrations.zoho.connect` calls `IntegrationController::connectZoho()`.

### 3. State is generated
A random UUID is created and saved in the session under `zoho_oauth_state`.

### 4. Authorization URL is built
The `ZohoBooksOAuthService::buildAuthorizationUrl()` method builds the Zoho authorization URL with the client ID, redirect URL, scope, and state.

### 5. User is redirected to Zoho
The browser leaves the app and opens Zoho Accounts. The user signs in and approves access.

### 6. Zoho redirects back
Zoho redirects back to `/integrations/zoho/callback` with `code` and `state` query parameters.

### 7. Callback validates state
`IntegrationController::zohoCallback()` checks the incoming state matches what was stored earlier. If the state is different, it stops with 419.

### 8. Code is exchanged for tokens
The service calls Zoho token endpoint and sends:
- grant_type
- client_id
- client_secret
- redirect_uri
- code

### 9. Tokens are saved per team
The project stores the tokens in the `integrations` table under the active team and with provider name `zoho`.

### 10. User is redirected to the integrations page
The user returns to the app and sees the integration saved.

## Code-by-code explanation

### `buildAuthorizationUrl()`
This method builds the URL that starts the OAuth flow.

Important lines:
- `state` is generated for security.
- `scope` declares the permissions requested.
- `client_id`, `redirect_uri`, `response_type` are required for OAuth.

### `exchangeCodeForTokens()`
This method exchanges the authorization code for access and refresh tokens.

Important lines:
- `Http::asForm()->post()` is used because OAuth token requests are form encoded.
- `throw()` is called so failed token exchange surfaces as a proper error.
- The result is JSON and is saved into the integration record.

### `connectZoho()`
This controller method authorizes the user, generates state, and redirects to Zoho.

### `zohoCallback()`
This controller method validates the callback, exchanges the code, saves the integration record, clears the state, and redirects back.

## Database effect

The integration is stored in `integrations` with:
- `team_id`: current team
- `provider`: `zoho`
- `external_id`: optional organization or realm id
- `credentials`: JSON containing tokens
- `status`: `enabled`

## Security considerations

- The state is stored in session to prevent CSRF-style OAuth tampering.
- The app uses team scope, so a user cannot attach a Zoho connection to another team.
- Tokens are stored as JSON in the integration record.
- Production should encrypt secrets before long-term storage.

## Legacy comment rule

Older strict config checks were intentionally left commented in this service.
Reason:
- It keeps the original production expectation visible for the team.
- It allows local/test development without real Zoho credentials.
- It keeps rollback and debugging easier for junior developers.

## Testing

Command run:

```bash
cd "c:\Users\Mazhar\Documents\github.com\remotedeveloper007\Smart Financial Document Hub" && php artisan test --filter=ZohoBooksOAuthTest
```

Result:
- 2 tests passed
- 11 assertions
- exit code 0

## Maintenance notes

- Add real `ZOHO_CLIENT_ID`, `ZOHO_CLIENT_SECRET`, and `ZOHO_REDIRECT_URI` in `.env` for production.
- Update scopes if the app needs more Zoho Books permissions later.
- Keep provider names normalized as `zoho`.
