**Redis + Horizon Integration — Feature Doc**

Purpose:
- Configure Redis for cache and queues and integrate Laravel Horizon for production queue management.
- Improve reliability, throughput, and observability of background processing.

Business reason:
- Redis is low-latency in-memory store ideal for caching and queues.
- Horizon provides job metrics, retry dashboards, and supervisor management.

Scope:
- Change cache default to Redis (configurable).
- Change queue default to Redis (configurable).
- Document installation and deployment steps for Horizon and Redis.
- Provide supervisor/systemd examples and verification checklist.

Files changed:
- `config/cache.php` — default cache store set to `redis`.
- `config/queue.php` — default queue connection set to `redis`.

Environment variables:
- `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`, `REDIS_DB` (default connection)
- `REDIS_CACHE_DB`, `REDIS_QUEUE_DB` (optional separate DB indexes)
- `QUEUE_CONNECTION=redis`
- `CACHE_STORE=redis`

Installation (production):
1. Install Redis on server or use managed Redis (Cloudflare, AWS Elasticache, etc.).
2. Add Redis connection details to `.env`:

   REDIS_HOST=127.0.0.1
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   REDIS_DB=0
   REDIS_CACHE_DB=1
   REDIS_QUEUE_DB=2

3. Require Horizon (once, on dev machine or CI build):

   composer require laravel/horizon --with-all-dependencies

4. Publish Horizon config and assets:

   php artisan horizon:install
   php artisan vendor:publish --provider="Laravel\Horizon\HorizonServiceProvider" --tag="config"

5. Configure `config/horizon.php` supervisors as needed (example below).

NOTE: Installing `laravel/horizon` requires PHP extensions (`pcntl`, `posix`) which are not available on Windows CLI. Run `composer require laravel/horizon` on a Linux/macOS host, inside a Docker container, or in CI before deploying. Alternatively install Horizon on your production Linux hosts directly. If you cannot install Horizon in your local Windows environment, use Docker for local workers or test on a Linux staging host.

Horizon supervisor example (production `/etc/systemd/system/horizon.service`):

[Unit]
Description=Laravel Horizon
After=redis.service

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/html/artisan horizon

[Install]
WantedBy=multi-user.target

Or use Supervisor config (supervisord):

[program:horizon]
process_name=%(program_name)s
command=php /var/www/html/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/horizon.log

Horizon config snippet (`config/horizon.php`)
- Create supervisors for logical queues:

'supervisors' => [
    'default' => [
        'connection' => 'redis',
        'queue' => ['default', 'high', 'ocr', 'exports'],
        'balance' => 'simple',
        'processes' => 3,
        'tries' => 3,
    ],
],

Operational recommendations:
- Use separate Redis DBs for cache and queue when possible.
- Use persistent Redis connection pooling and monitor latency.
- Configure Horizon to supervise queue groups by workload (ocr=heavy CPU/IO, exports=IO-heavy).
- Set `retry_after` sufficiently larger than expected job runtime.
- Use tags and metrics in Horizon to monitor job durations and failures.

Testing and verification

Local dev (quick test):
- In `.env.testing` or `.env` set:
  QUEUE_CONNECTION=sync (for unit tests)
  CACHE_STORE=array (for unit tests)
- For integration test with Redis locally use:
  QUEUE_CONNECTION=redis
  CACHE_STORE=redis
  Start Redis locally: `redis-server`
  Start worker: `php artisan queue:work --queue=default --sleep=3 --tries=3`

Verify:
- Dispatch a job (e.g., `ProcessDocumentJob`) and confirm it appears in Redis list and processed by worker.
- `php artisan horizon` (or visit `/horizon` on app if secured) shows job metrics.

CI considerations

- CI should start a Redis service in test job (GitHub Actions has redis service).
- Use `QUEUE_CONNECTION=sync` for most unit tests; use integration tests with `redis` for end-to-end queue behavior.

Security

- Protect Horizon dashboard with authentication (only Owners/Admins). Use middleware in routes or place behind internal network.
- Use strong Redis password and TLS for managed services.

Verification checklist

- [ ] Redis accessible from app servers.
- [ ] `QUEUE_CONNECTION=redis` and `CACHE_STORE=redis` are set in production `.env`.
- [ ] Horizon installed and `config/horizon.php` configured with supervisors.
- [ ] systemd/supervisor configured to keep `horizon` running.
- [ ] Workers process jobs for `ocr`, `exports`, `default` queues reliably.
- [ ] Monitoring/alerts for queue backlog and failed jobs (Horizon + external alerting).

Rollout plan

1. Deploy Redis and configure `.env` on staging.
2. Deploy app with `QUEUE_CONNECTION=redis` and `CACHE_STORE=redis` to staging; run smoke tests.
3. Install Horizon and start supervisor with limited processes.
4. Monitor for errors, tune `processes` and `retry_after` settings.
5. Promote to production incrementally.

Notes

- Keep `QUEUE_CONNECTION=sync` in testing environments unless explicitly testing async behavior.
- Use Redis for cache only after verifying eviction and memory policies.

Local fallback (No Docker / limited resources)
---------------------------------------------
If you cannot run Redis or install Horizon on the local machine (Windows or low-resource systems), use the MySQL-backed queue and file/database cache which are supported by the current `.env` defaults in this repo.

1. Ensure `.env` contains the MySQL settings and queue/cache configuration:

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=smart_financial_document_hub
   DB_USERNAME=root
   DB_PASSWORD=
   QUEUE_CONNECTION=database
   CACHE_STORE=database

2. Run migrations (creates `jobs`, `job_batches`, `failed_jobs`, `cache` tables):

```bash
php artisan migrate
```

3. Start a local queue worker (Linux):

```bash
php artisan queue:work database --queue=default,high,ocr,exports --sleep=3 --tries=3
```

Create a systemd unit on Linux to run the worker as a service (example):

[Unit]
Description=Laravel Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/html/artisan queue:work database --sleep=3 --tries=3 --queue=default,high,ocr,exports

[Install]
WantedBy=multi-user.target

4. Start a local queue worker (Windows)

Use Task Scheduler to run `php artisan queue:work` at system startup, or use a Windows service helper such as NSSM (free) to run the worker as a service. Example Task Scheduler (PowerShell) command to create a startup task:

```powershell
$action = New-ScheduledTaskAction -Execute 'php' -Argument 'artisan queue:work database --sleep=3 --tries=3 --queue=default,high,ocr,exports'
$trigger = New-ScheduledTaskTrigger -AtStartup
Register-ScheduledTask -Action $action -Trigger $trigger -TaskName 'LaravelQueueWorker' -Description 'Start Laravel queue worker at startup'
```

5. Monitoring and logs

- Ensure `storage/logs/laravel.log` and worker stdout are monitored for failures.
- Use `php artisan queue:failed` and `php artisan queue:retry` to inspect and retry failed jobs.

6. CI considerations (GitHub Actions)

Use MySQL service in CI for integration tests (no Docker required on runner):

```yaml
services:
  mysql:
    image: mysql:8
    env:
      MYSQL_ROOT_PASSWORD: ''
      MYSQL_DATABASE: smart_financial_document_hub
    ports: ['3306:3306']
    options: >-
      --health-cmd='mysqladmin ping --silent' --health-interval=10s --health-timeout=5s --health-retries=3

env:
  DB_CONNECTION: mysql
  DB_HOST: 127.0.0.1
  DB_PORT: 3306
  DB_DATABASE: smart_financial_document_hub
  DB_USERNAME: root
  DB_PASSWORD: ''

steps:
  - uses: actions/checkout@v4
  - name: Install PHP deps
    run: composer install --no-progress --no-suggest --prefer-dist
  - name: Run migrations
    run: php artisan migrate --force
  - name: Run tests
    run: php artisan test
```

Notes
-----
- Horizon provides superior observability and supervisor management but requires `ext-pcntl`/`ext-posix` and a Linux runtime. Use Horizon on staging/production Linux hosts when available.
- The database-backed queue is modestly slower than Redis but requires no additional services and is sufficient on low-resource hosts.
