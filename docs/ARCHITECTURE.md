Purpose:

Single source of architectural truth.

Contains:

```text
Architecture Style:
Modular Monolith

Backend:
Laravel 13

Frontend:
Inertia + React + TypeScript

Database:
PostgreSQL  (Query must be compatible with MySQL DB)

Cache:
Redis

Storage:
Cloudflare R2

Queue:
Redis + Horizon

Monitoring:
Pulse + Horizon + Sentry

Search:
Scout + Meilisearch + Elasticsearch

Feature Flags:
Pennant
```

Also include:

```text
Tenant Strategy
Folder Structure
Queue Strategy
Storage Strategy
Event Strategy
Deployment Strategy
```
