# 13 - Production Hardening, Observability & Performance

## Goal
Make the application stable, reliable, and scalable. Provide clear, step-by-step maintenance instructions so junior developers can operate and troubleshoot production systems.

---

## Summary of changes applied
- Added `fault_logs` table and `App\Models\FaultLog` to store captured exceptions and operational faults.
- Added `CaptureFaults` middleware to persist unhandled exceptions to `fault_logs` (registered in `bootstrap/app.php`).
- Added job middleware `App\Jobs\Middleware\SetTeamContext` (already added) to ensure queued jobs run with the correct tenant context.
- Attached `SetTeamContext` to key jobs: `ProcessDocumentJob`, `RecordAuditLogJob`, `SendSubscriptionCreatedJob`.
- Added composite DB indexes for common tenant-scoped queries (`team_id, created_at`, etc.).
- Added per-request caching for role/permission checks on `App\Models\User`.

---

## Why these changes
- Fault capture: Persisting faults allows triage, root-cause analysis, and SLA management.
- Tenant-aware job context: prevents cross-tenant leakage and ensures global scopes work inside workers.
- Composite indexes: speed up common queries used for listing and reporting per-tenant.
- Role/permission caching: reduces repeated DB queries during request handling.

---

## How to apply the database migrations (local / production)

1. Ensure you have a backup of your production database before applying migrations.
2. Run migrations:

```bash
php artisan migrate --force
```

3. Verify new tables and indexes exist:

```bash
# Check fault_logs
php artisan tinker --execute="\DB::table('fault_logs')->limit(1)->get();"

# Confirm index exists (MySQL example)
mysql -u root -e "SHOW INDEX FROM documents WHERE Key_name LIKE '%team_id%';"
```

---

## Redis + Horizon (recommendation)

For high throughput and scalable workers, use Redis as a queue backend and Laravel Horizon to manage workers.

1. Install Redis and confirm accessibility from the app server.
2. In your `.env`:

```
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

3. Install Horizon on the project (on a dev machine first):

```bash
composer require laravel/horizon
php artisan vendor:publish --provider="Laravel\Horizon\HorizonServiceProvider"
php artisan horizon:install
```

4. Configure Horizon (resources/config/horizon.php) to create dedicated supervisors for `ocr`, `exports`, `default` queues.
5. Start Horizon (on servers with process supervisor):

```bash
php artisan horizon
# Use a supervisor (systemd / supervisord) to run horizon persistently
```

6. Monitoring: enable metrics exposure and attach Prometheus/Grafana or use Laravel Pulse.

---

## Fault triage & workflow

- `fault_logs` stores faults. Use the `status` field (`open`, `investigating`, `resolved`) for workflow.
- Create an admin view (restricted to Owner role) to list and filter faults by `severity`, `team_id`, `created_at`.
- Add webhooks or Slack notifications for `severity=critical` faults via a lightweight notification job.

---

## Query performance guidance

- Add composite indexes for tenant-scoped queries (applied in migration). Typical patterns:
  - `WHERE team_id = ? ORDER BY created_at DESC` => index(`team_id`, `created_at`)
  - Date-range filtering on tenant data => index(`team_id`, `transaction_date`)
- Avoid N+1 queries: use eager loading (`with()`) in controllers/actions.
- Use database-level pagination (limit/offset) for admin lists; consider seek-pagination for large tables.
- Use read replicas for heavy reporting workloads.

---

## Caching strategy

- Per-request caches are implemented for role/permission checks.
- Tenant-level caches (Redis) are recommended for:
  - Recent documents metadata
  - Frequently-read configuration per-tenant
  - Computed reports with TTLs
- Always include `team_id` in cache keys: `tenant:{team_id}:recent_documents`

---

## Logging and slow query analysis

- Enable slow query logging on your DB and set thresholds (e.g., 100ms).
- Record slow queries in an observability platform (Sentry, DataDog, or custom ingestion).
- Add application-level timing for critical paths (document processing pipeline) and emit metrics to Prometheus.

---

## Operational runbook (quick)

- Deploy steps:
  1. Pull code
  2. `composer install --no-dev --optimize-autoloader`
  3. `npm ci && npm run build`
  4. `php artisan migrate --force`
  5. Restart PHP-FPM / queue workers / horizon

- Emergency steps (DB outage):
  - Stop queue workers
  - Disable writes via feature flag or maintenance mode
  - Restore from backup if needed

---

## Next suggested implementations (I can do these):
- Add an admin UI for `fault_logs` with filters and export.
- Add Horizon supervisor config and systemd unit example.
- Add Prometheus metrics and dashboards for queue length, job latencies, slow queries.
- Add automated tests verifying `CaptureFaults` creates a `FaultLog` on exception.

---

## Files added or modified
- `database/migrations/2026_08_24_000001_create_fault_logs_table.php`
- `database/migrations/2026_08_24_000002_add_tenant_indexes.php`
- `app/Models/FaultLog.php`
- `app/Http/Middleware/CaptureFaults.php`
- `bootstrap/app.php` (registered `CaptureFaults` middleware)


**End of production-hardening documentation.**
