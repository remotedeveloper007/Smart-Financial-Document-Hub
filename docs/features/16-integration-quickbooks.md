# QuickBooks Integration

## Purpose

This document explains how the QuickBooks OAuth integration works in this project and why the implementation is structured the way it is.

## Business reason

Many businesses store accounting data in QuickBooks. The app needs a secure way to connect a team to QuickBooks, save its tokens, and reuse that connection later for imports or sync tasks.

## Where the code lives

- Service: `app/Services/Integrations/QuickBooksOAuthService.php`
- Controller: `app/Http/Controllers/Integrations/IntegrationController.php`
- Routes: `routes/web.php`
- Configuration: `config/services.php`
- Test file: `tests/Feature/QuickBooksOAuthTest.php`

## Flow in simple English

### 1. The user starts the connection
The user opens the integrations page and clicks the QuickBooks connection button.

### 2. The route is called
The route `integrations.quickbooks.connect` triggers `IntegrationController::connectQuickBooks()`.

### 3. A unique state is created
A random UUID is generated and placed in the session. This is used to verify the callback is valid.

### 4. The authorization URL is built
`QuickBooksOAuthService::buildAuthorizationUrl()` creates the URL that sends the user to Intuit.

### 5. The user signs in to QuickBooks
The browser leaves the app and opens QuickBooks. The user approves the requested permissions.

### 6. QuickBooks redirects back
The user returns to `/integrations/quickbooks/callback` with `code` and `state`.

### 7. The callback is validated
The controller checks that the `state` matches the original session value. If it does not, the app stops the request.

### 8. The code is exchanged for tokens
The service posts to Intuit token endpoint using the authorization code.

### 9. Tokens are saved to the current team
The integration record is created or updated for this team with the provider `quickbooks`.

### 10. The user is returned to the app
The app redirects back to the integrations page after saving the connection.

## Code-by-code explanation

### `buildAuthorizationUrl()`
This method prepares the authorization URL.

Important pieces:
- `client_id`: identifies the app in QuickBooks
- `redirect_uri`: tells QuickBooks where to return after approval
- `response_type=code`: asks QuickBooks to return an authorization code
- `scope`: lists the data access the app is requesting
- `state`: protects against tampered or replayed callbacks

### `exchangeCodeForTokens()`
This method sends the code to the token endpoint and receives the real access and refresh tokens.

Important details:
- `Http::withBasicAuth()` is used because QuickBooks expects basic auth for token exchange
- `asForm()` ensures the request is encoded as form data
- `throw()` makes failed exchanges surface clearly

### `connectQuickBooks()`
This method authorizes the user, creates a safe state, and redirects the browser to QuickBooks.

### `quickBooksCallback()`
This callback method:
- validates state
- exchanges the code for tokens
- saves the token payload into the `integrations` table
- clears the session state
- redirects back to the integrations page

## Database effect

The project stores this connection in the `integrations` table with:
- `team_id`: current tenant team
- `provider`: `quickbooks`
- `external_id`: optional QuickBooks realm or company id
- `credentials`: JSON with access token, refresh token, expiry, and token type
- `status`: `enabled`

## Security notes

- `state` validation is a mandatory OAuth protection step.
- Team-level scoping ensures one tenant cannot see another tenant's credentials.
- Credentials must be protected in production with encryption or a secure secret store.

## Legacy comment rule

Older strict configuration checks were intentionally left commented in this service.
Reason:
- it preserves the original production expectation for future review
- it keeps local/test development working without real QuickBooks credentials
- it makes rollback and debugging easier for the team

## Testing

Command run:

```bash
cd "c:\Users\Mazhar\Documents\github.com\remotedeveloper007\Smart Financial Document Hub" && php artisan test --filter=QuickBooksOAuthTest
```

Result:
- 2 tests passed
- 11 assertions
- exit code 0

## Maintenance notes

- Add real values for `QUICKBOOKS_CLIENT_ID`, `QUICKBOOKS_CLIENT_SECRET`, and `QUICKBOOKS_REDIRECT_URI` in the environment for production.
- Keep the provider name normalized as `quickbooks`.
- Add token refresh logic when the actual accounting sync is implemented.
