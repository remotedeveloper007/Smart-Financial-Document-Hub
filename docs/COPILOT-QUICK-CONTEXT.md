# Smart Financial Document Hub

## Copilot Quick Context

Version: 1.0

---

# What This Product Is

Smart Financial Document Hub is a multi-tenant SaaS platform that converts financial documents into structured financial intelligence.

Target Users:

* Accountants
* Bookkeepers
* Audit Firms
* Tax Consultants
* SMB Finance Teams

The product is NOT a PDF converter.

The product is a structured financial data platform.

---

# Core Philosophy

Always think:

Document → Data → Intelligence → Automation

NOT:

Document → Export

Documents are temporary.

Structured financial data is the real asset.

Every feature must strengthen this principle.

---

# Architecture

Architecture Style:

Modular Monolith

Stack:

Backend:

* Laravel 13
* PHP 8.4+

Frontend:

* Inertia.js
* React
* Advance statemanagement for React
* TypeScript
* Tailwind

Database:

* PostgreSQL

Cache:

* Redis

Queues:

* Horizon

Search:

* Scout
* Meilisearch

Storage:

* Cloudflare R2

Infrastructure:

* Docker
* GitHub Actions
* Cloudflare

---

# Most Important Rule

Multi-Tenancy comes first.

Never build features before tenant isolation is complete.

Every business table must contain:

tenant_id

Examples:

* documents
* transactions
* invoices
* contracts
* audit_logs
* exports
* subscriptions

Cross-tenant access must never be possible.

---

# Data Pipeline

Every feature must support this pipeline:

Upload
↓
Store
↓
Queue
↓
Extract
↓
Normalize
↓
Persist
↓
Audit
↓
Search
↓
Report
↓
Export

Never bypass the pipeline.

---

# Development Rules

Build one feature at a time.

Never build the whole application.

After every feature:

1. Generate code
2. Generate tests
3. Generate documentation
4. Generate verification checklist
5. Stop

Wait for approval.

---

# Coding Rules

Follow:

* SOLID
* DRY
* KISS

Avoid:

* Fat Controllers
* Business Logic in Controllers
* Premature Microservices

Prefer:

* Actions
* Services
* DTOs
* Events
* Listeners
* Jobs

---

# Queue Rules

Document processing must always be asynchronous.

Never process large files inside controllers.

Use queues for:

* OCR
* Extraction
* Exports
* Reports
* Notifications

Jobs must be idempotent.

---

# Security Rules

Mandatory:

* Authentication
* Authorization
* Tenant Isolation
* Audit Logging
* Virus Scanning
* Rate Limiting

Every security-sensitive action must be traceable.

---

# Search Rules

Search is a tenant isolation risk.

Every search query must be scoped by:

tenant_id

No exceptions.

---

# Fault Detection Rules

The application must include:

System Health & Fault Detection Center

Every fault record must contain:

* Error ID
* Severity
* Module
* File Name
* File Path
* Class Name
* Method Name
* Root Cause
* Recommended Fix

Severity Levels:

* Critical
* High
* Medium
* Low

The system must help junior developers identify and resolve issues quickly.

---

# Testing Rules 

Minimum Coverage:

95%

Required:

* Unit Tests
* Feature Tests
* Integration Tests
* Tenant Isolation Tests
* Authorization Tests
* Queue Tests

No feature is complete without tests.

---

# Documentation Rules

Every feature must generate:

docs/features/{feature-name}.md

Include:

* Purpose
* Architecture
* Database Changes
* Eloquest Relationship (with explaination for each Models created in details)
* File Paths
* Testing Steps
* Troubleshooting Guide
* Maintenance Guide
* Add artisan cli used to create files or run any command for (migration, new column added to schema, controller, models, etc.)
* Also follow Laravel 13 documentation for Laravel 13 guideline and development structure
* Add every test result in documents feature wise including `bash` command run.
* Documant must be maintain for every packages install using artisan cli for both either Frontend and Backend implementation to their related documents

IMPORTANT NOTES: Documentation must be understandable by junior developersin simple English OR Hinglish. Must document all debugging process for each features

---

# MVP Build Order

1. Multi-Tenancy
2. Authentication
3. Authorization
4. Teams
5. Document Upload
6. Queue Processing
7. Transaction Extraction
8. Search
9. Export
10. Audit Logs
11. Billing Foundation

Do not skip steps.

---

# Final Rule

Always optimize for:

Accurate Financial Data Extraction

Not architectural complexity.

Success means:

User uploads a financial document
→ data is extracted correctly
→ data is trusted
→ workflows become automated
→ business value is created

---