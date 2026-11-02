**Horizon Security & Deployment**

Purpose:
- Secure the Laravel Horizon dashboard so only authorized team roles (Owner/Admin) can access.

Implementation summary:
- Added `Gate::define('viewHorizon', ...)` in `App\Providers\AuthServiceProvider`.
- `config/horizon.php` sets `middleware` => `['web','auth','can:viewHorizon']` so Horizon routes are protected.

How it works:
- When a user visits `/horizon`, Laravel evaluates the middleware stack. The `can:viewHorizon` middleware uses the Gate to check the logged-in user's roles.
- The Gate uses `User::hasRole('owner') || User::hasRole('admin')` for the current team context.

Testing (manual):
1. Create two users: one with `owner` role in Team A, one with `viewer` role.
2. Login as the `owner` user and visit `/horizon` — you should see the Horizon dashboard.
3. Login as the `viewer` user and visit `/horizon` — you should get a 403.

Notes:
- Ensure `auth` middleware is active and session driver is configured.
- For single-sign-on or internal networks, consider restricting Horizon to internal IP ranges or a VPN.

