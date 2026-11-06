# Developer Manual — Smart Financial Document Hub

Purpose

- Provide a step-by-step guide so a beginner can set up, develop, maintain, and extend the application.

Prerequisites

- OS: Linux/macOS/Windows (WSL recommended on Windows)
- PHP 8.4+
- Composer
- Node.js 18+ and npm or pnpm
- MySQL or PostgreSQL for development (this repo uses MySQL for tests)
- Redis (for queues/cache)
- Docker (optional, recommended for parity)

Repository layout (high level)

- `app/` — Laravel app code (Models, Actions, Jobs, Middleware, etc.)
- `bootstrap/` — app bootstrapping
- `config/` — configuration
- `database/` — migrations, seeders, factories
- `docs/` — architecture and manuals
- `public/` — web entry
- `resources/` — frontend assets and views
- `routes/` — route definitions
- `tests/` — unit & feature tests

Getting started (local)

1. Clone repository:

   git clone <repo>
   cd Smart Financial Document Hub

2. Install PHP dependencies:

   composer install --no-interaction --prefer-dist

3. Install node dependencies and build frontend:

   npm install --legacy-peer-deps
   npm run build

4. Copy env and configure DB/Redis:

   cp .env.example .env
   # Edit .env: DB_CONNECTION=mysql, DB_DATABASE=smart_financial_document_hub, DB_USERNAME, DB_PASSWORD
   # Fault records use the configured application database connection.

5. Generate app key and run migrations:

   php artisan key:generate
   php artisan migrate --seed

6. Start dev server:

   php artisan serve

Architecture overview

- Multi-tenant, team-based tenancy.
- `TeamContext` service holds current tenant.
- Models use `BelongsToTeam` trait and `BelongsToTeamScope` to enforce tenant scoping.
- Jobs use `SetTeamContext` middleware to set tenant when processed by workers.
- Queues should use Redis/Horizon in production. Local default is `sync` for tests.

Core patterns

- Actions and Services: heavy logic lives in `app/Actions` and `app/Services`.
- Jobs and Middleware for async pipelines.
- Policies for authorization.

Fault handling and observability

- `CaptureFaults` middleware captures exceptions and persists to both DB and a persistent file.
- Add Sentry/DataDog in `AppServiceProvider` and configure DSN via env.
- Use Laravel Pulse for lightweight health metrics.

Queues, Redis & Horizon (production)

- In `.env` set `QUEUE_CONNECTION=redis` and configure `REDIS_URL`.
- Install and configure Horizon as per Laravel docs.
- Example Supervisor config (systemd) provided in `docs/features/13-production-hardening.md`.

Testing

- Tests run against MySQL in CI (see `phpunit.xml`).
- Run all tests:

   php artisan test

- Running a single test:

   php artisan test --filter FaultCaptureTest

- If a test depends on the `logging` DB connection, ensure migrations for that connection are run in CI pre-steps.

Coding guidelines

- Thin controllers; use Actions/Services for business logic.
- Jobs must be idempotent.
- All queries that read/write tenant data must be scoped by `team_id`.

How to add a feature (step-by-step)

1. Create an Action or Service for core business logic in `app/Actions`.
2. Add Eloquent models and migrations in `database/migrations` with `team_id` where relevant.
3. Add policies in `app/Policies` and wire them in `AuthServiceProvider`.
4. Add controllers with minimal logic and call Actions.
5. Add routes in `routes/web.php` or `routes/api.php`.
6. Add frontend components under `resources/js` with Inertia + React.
7. Add Feature and Unit tests under `tests/Feature` and `tests/Unit`.
8. Run `php artisan test` and fix issues.
9. Write docs under `docs/features/{NN}-{feature}.md` describing the feature, tests, and maintenance steps.

Debugging tips

- Use `php artisan tinker` to inspect models and DB.
- Tail logs: `tail -f storage/logs/laravel.log`.
- Use Xdebug and the IDE for step-through debugging.
- Ensure `APP_DEBUG` is false in production.

CI and Deployment

- CI pipeline should run `composer install`, `npm ci`, `php artisan migrate --env=testing`, and `php artisan test`.
- For deployment, build assets, run migrations, and restart queue workers.

Maintenance checklist

- Ensure backups for DB and storage are scheduled.
- Monitor queue length and Horizon dashboard.
- Rotate logs and monitor `storage/logs` size.

Appendix: Useful commands

- `php artisan migrate --force`
- `php artisan queue:work --queue=default --sleep=3 --tries=3`
- `php artisan horizon`
- `php artisan route:list`
- `composer dump-autoload`

---

# Guide for Developers to set up

This guide is for developers who need to set up, understand, change, test, and operate the Smart Financial Document Hub. It describes the code and routes currently present in this repository; a roadmap item or a frontend file alone does not mean the feature is available to users.

## 1. Before You Begin

Read the project rules in this order:

1. [Project Constitution](../PROJECT-CONSTITUTION.md): security, tenancy, product principles, and mandatory engineering constraints.
2. [Architecture](../ARCHITECTURE.md): intended runtime and infrastructure.
3. [Development Roadmap](../DEVELOPMENT-ROADMAP.md): delivery order and project status.
4. [Feature Development Workflow](../FEATURE-DEVELOPMENT-WORKFLOW.md): what to deliver and review for every change.
5. The applicable feature guide in `docs/features/` and this manual's [coverage register](manual-index.md#current-ui-and-documentation-coverage).

Do not treat planned integrations, sample extraction output, or a page component that has no route as production functionality.

## 2. Requirements and Local Setup

The repository targets Laravel 13 (`composer.json`), PHP `^8.4`, React 18, TypeScript, Vite, and Tailwind. Use PHP 8.4 or newer, Composer, Node/npm, and a database configured for Laravel. The current local runtime is PHP 8.3.14 and is below the declared minimum; use the PHP 8.4 CI job for supported-runtime verification. Redis is needed when exercising Redis-backed jobs; Docker is optional unless the team standardizes local services through it. The exact test database settings come from `phpunit.xml` and `.env.testing`, not from assumptions in old docs.

From the repository root, install and configure the app:

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
```

On Bash/WSL use `cp .env.example .env` instead of `copy`. Edit `.env` for the local database, mail, cache, queue, and any provider credentials you actually use. Never paste production secrets into source control or test reports. Confirm the configured database exists before migrating:

```bash
php artisan migrate --seed
```

Run the app using the project's Composer development process, or start its services separately:

```bash
composer run dev
```

The Composer `dev` script starts Laravel, the queue listener, log output, and Vite. Once started, the Laravel server normally prints its local address (often `http://127.0.0.1:8000`); use the address actually printed by the command. If running only Laravel, use `php artisan serve`; the Vite server is still needed for frontend development assets.

Build frontend assets before verifying a production-like bundle:

```bash
npm run build
```

## 3. Project Map

- `app/Actions/`: discrete application operations such as upload, extraction, persistence, search, or export.
- `app/Http/Controllers/` and `app/Http/Requests/`: HTTP boundary, response composition, and request validation.
- `app/Jobs/`: asynchronous work; jobs must be safe to retry and must carry the tenant/document identifiers they need.
- `app/Models/`, `app/Scopes/`, `app/Traits/`: persistence and tenant-aware model behavior.
- `app/Policies/`: user/team capability checks.
- `app/Services/`: reusable infrastructure or domain services.
- `database/migrations/`, `database/factories/`, `database/seeders/`: schema, test data, and initial reference data.
- `resources/js/Pages/`: Inertia React pages; `resources/js/Layouts/` contains shared app shells.
- `routes/web.php`, `routes/auth.php`: current web and authentication endpoints.
- `tests/Feature/`, `tests/Unit/`, and frontend `__tests__/`: regression coverage.
- `docs/features/`: feature-specific design, test, and troubleshooting notes.

## 4. Request and Data Flow

For a typical page, the browser requests a named route, Laravel authenticates the user and resolves the current team, a policy authorizes the operation, a controller validates/delegates, and Inertia renders a React page. For JSON-backed pages, React requests the associated list endpoint. Business writes belong in an Action or Service, not in a large controller.

The intended financial pipeline is:

```text
Upload -> tenant storage -> queued job -> extract -> normalize -> persist idempotently
       -> audit -> read/search/report/export
```

Some stages are not complete for every document type. In particular, sample/fake extraction must be described as sample output, not as verified OCR or financially reliable extraction.

## 5. Patterns and Why We Use Them

### Team tenancy and global scope

`TeamContext` is the request/job boundary for the active team. Business models use the repository's `BelongsToTeam` trait and `BelongsToTeamScope` so ordinary Eloquent reads are scoped, and newly created rows receive the active `team_id`. This is defense in depth, not permission to trust a client-supplied team identifier. Keep explicit tenant predicates on sensitive query boundaries and verify that related records belong to the same team.

### Thin controller and Action

The controller handles HTTP concerns: authorize, validate or accept a Form Request, call an Action, and return a response. Actions hold a single use-case operation and can be tested without coupling all business rules to route code. Keep UI serialization at the edge and avoid putting slow OCR, provider calls, or large exports in the request cycle.

### Queue job and idempotency

Use a job for slow or retryable work. Pass stable identifiers, reload records on the worker, establish the tenant context, and do not serialize mutable model state as the source of truth. Give persisted extraction rows a deterministic idempotency key and database uniqueness constraint. A retry should update or safely recognize existing work, not duplicate financial records. Record failure context without logging secrets or document contents unnecessarily.

### Policy and permission

Use a policy for resource access and register it with the authorization provider. A route being behind `auth` only proves that the caller has a session; it does not grant access to the resource. Test permitted, denied, and cross-team cases, including list endpoints.

### Inertia/React page

Page components render and manage local interaction state. Keep tenant filtering, validation, authorization, pagination, and data correctness on the server. Type the expected response shape; surface loading, empty, and error states. Do not rely on hiding navigation as the only access control.

## 6. Adding a Feature: Beginner Workflow

Before coding, verify that the requested item is next in the roadmap and is not already implemented under another name.

1. Write down the user outcome, tenant-owned records, permissions, failure cases, and route/UI entry point.
2. Inspect nearby models, Actions, policies, tests, and feature docs. Reuse local patterns rather than adding a second architecture.
3. Generate scaffolding only when it saves time; examples include `php artisan make:model Invoice -mf`, `php artisan make:request StoreInvoiceRequest`, `php artisan make:controller InvoiceController`, `php artisan make:policy InvoicePolicy --model=Invoice`, and `php artisan make:test InvoiceFeatureTest`. Review generated files rather than accepting defaults blindly.
4. Add a migration with foreign keys, indexes, tenant ownership, uniqueness/idempotency constraints, and a reversible `down()` method. Avoid database-specific SQL unless documented and tested against the supported engines.
5. Add the model, relationships, casts, tenant scope behavior, and factory/seeder as appropriate. Never accept `team_id` from an untrusted request as authorization.
6. Add an Action for the use case, a Job for slow work, a policy, a validated request, and a thin controller.
7. Register authenticated routes and named routes. Build a frontend page only when its server page route, data endpoint, authorization, and navigation state all exist.
8. Add feature tests for request/response and tenant isolation, policy tests for access decisions, Action/Job tests for behavior and retry/idempotency, and frontend tests for significant interactions.
9. Run focused tests first, then the full test suite and asset build. Record actual commands and results in feature docs; never write an anticipated pass as a result.
10. Update feature docs, tester steps, customer guidance if user-visible, and the manual coverage register. Review the diff and stop at the requested milestone for approval.

## 7. Testing and Quality Gates

Run a focused PHP test by class name, then the full suite:

```bash
php artisan test --filter=InvoiceFeatureTest
php artisan test
npm run test:unit -- --run
npm run build
```

Only run a frontend test command if that test exists and the installed package script supports it. `npm run build` includes TypeScript checking in this repository. For each tenant-owned endpoint, test Tenant A and Tenant B, unauthenticated behavior, missing permission behavior, empty data, valid data, validation failures, and retry behavior when relevant. Keep tests deterministic and do not depend on live third-party OAuth or AI credentials.

Before merging, check:

- migrations work from a fresh database and can roll back;
- model reads/writes, relationships, and database constraints preserve tenant boundaries;
- queue jobs carry team/document IDs and retries do not duplicate records;
- external credentials are encrypted or otherwise protected and never returned to the UI;
- routes are named, authenticated as required, authorized, and reachable from navigation;
- loading, empty, success, and error states are usable at mobile and desktop widths;
- docs contain executed verification results and explicitly name limitations.

## 8. Local Debugging

Useful inspection commands:

```bash
php artisan route:list --except-vendor
php artisan migrate:status
php artisan test --filter=SomeTestName
php artisan tinker
```

Check `storage/logs/laravel.log`, the browser console, and the Network panel. For a failing page, inspect (in order): whether its route is registered; whether the authenticated request returns 200/403/500; whether the page component path matches `Inertia::render`; whether its JSON endpoint returns the shape the component expects; and whether the request is tenant scoped. Do not debug by disabling authorization or global scopes.

## 9. Security and Tenant Safety

- Tenant keys currently use `team_id` in the implementation; follow the existing schema rather than adding a parallel `tenant_id` naming scheme without an approved migration plan.
- Every business read/write must be scoped to the active team. Never expose a record by guessing an integer ID.
- Apply policy checks to both HTML and JSON endpoints; ensure exports and background jobs are scoped too.
- Validate file extension, content/size as supported by the request, store under a tenant-specific prefix, and use private access for confidential financial files in production.
- Keep OAuth tokens, AI keys, signing keys, DB passwords, and webhook secrets in environment/secret management. Do not put secrets in docs, fixtures, logs, or browser payloads.
- Use CSRF protection for session-authenticated writes and rate limits for sensitive or costly endpoints.
- Before production, review the security standards, HTTPS/proxy configuration, backup encryption, retention, access logs, and incident response.

## 10. Stability, Performance, and Scaling

Measure before optimizing. Establish baselines for request latency, database query count, queue wait time, failed-job rate, storage growth, and memory use. Use the repository's system-health and queue pages where authorized, plus database and worker metrics in the deployment environment.

- Add indexes based on real filters and sort orders, commonly `team_id` plus date/status; verify with query plans and realistic tenant-sized data.
- Paginate large lists on the server. Avoid loading full tables or doing expensive filtering in React.
- Eager-load relationships needed by the page; check for N+1 queries.
- Use bounded retries, timeouts, backoff, and dedicated queues for workloads with different latency/resource profiles.
- Keep worker counts and memory limits explicit; restart workers during deploys so code is refreshed.
- Use cache only for data with clear tenant-aware keys and invalidation rules. Never share cached tenant results across teams.
- Make external calls time-bounded and observable; use circuit-breaking/fallback behavior only where product semantics are clear.
- Test data volume and queue backlog before increasing concurrency; more workers can overload a database or provider.
- Schedule database and storage backups, test restore procedures, and monitor capacity/retention.

## 11. Deploy and Operate

Use the deployment process in [Deployment Standards](../DEPLOYMENT-STANDARDS.md) and feature-specific deployment notes. A production rollout should build immutable frontend assets, run reviewed backward-compatible migrations, deploy application code, restart queue workers, verify health checks and critical user flows, and retain a rollback path. Do not run destructive or irreversible database changes without a reviewed backup and recovery plan. Set `APP_DEBUG=false` in production.

Regular maintenance:

- review failed jobs and fault logs; retry only after understanding whether the operation is safe;
- check queue wait, job duration, error rates, DB connections/slow queries, storage usage, and disk space;
- patch supported PHP, Laravel, and frontend dependencies and run the full verification suite;
- rotate credentials and verify OAuth refresh/expiry behavior;
- rehearse backup restoration and deploy rollback;
- remove/retain data according to a documented customer retention policy.

## 12. Current UI and Documentation Coverage

See the [manual index coverage register](manual-index.md#current-ui-and-documentation-coverage). The live route list is the authority for what a tester can currently open. Do not claim a feature is complete just because its controller or React file exists.
