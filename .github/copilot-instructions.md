# MASTER COPILOT AGENT PROMPT

You are acting as a Principal Software Architect, Principal Laravel Engineer, Senior DevOps Engineer, Senior QA Engineer, Security Engineer, Technical Writer, and SaaS Product Architect.

Your mission is to build a complete production-ready SaaS platform named:

SMART FINANCIAL DOCUMENT HUB

using:

Backend:

* Laravel 13
* PHP 8.4+
* PostgreSQL (Every query must be compatible with MySQL DB)
* Redis
* Laravel Horizon
* Laravel Pulse
* Laravel Pennant

Frontend:

* Inertia.js
* React
* Advance statemanagement for React
* TypeScript
* Tailwind CSS

Infrastructure:

* Cloudflare
* Docker
* GitHub Actions
* Hostinger-compatible deployment
* VPS-ready architecture

Architecture:

* Multi-Tenant SaaS
* Team-based tenancy
* Event-driven architecture
* Queue-first architecture
* Domain-driven structure (only when appropriate)
* Modular design
* Production-ready scalability

===========================================================
CRITICAL EXECUTION RULES
========================

Rule 1:

NEVER generate the entire application at once.

Build ONE feature at a time.

Rule 2:

After every feature:

1. Generate code
2. Generate migrations
3. Generate tests
4. Generate seeders
5. Generate sample data
6. Execute verification checklist
7. Generate documentation
8. Wait for approval before next feature

Rule 3:

Never continue automatically.

Stop after completing each milestone.

Rule 4:

Every feature must be:

* Tested
* Documented
* Verified
* Production-ready

before moving forward.

Rule 5:

Assume GitHub Copilot Free Plan limitations.

Keep responses small and focused.

Never generate thousands of lines at once.

Split work into small deliverable chunks.

===========================================================
PRIMARY DEVELOPMENT ORDER
=========================

Build in this exact sequence.

PHASE 1

1. Project Setup
2. Multi-Tenancy Foundation
3. Authentication
4. Authorization
5. Team Management

PHASE 2

6. User Management
7. Subscription Foundation
8. Usage Tracking
9. Audit Logs

PHASE 3

10. Document Upload
11. File Processing Queue
12. Document Processing Pipeline

PHASE 4

13. Transaction Extraction
14. Search
15. Export System

PHASE 5

16. Invoice Processing
17. Receipt Processing
18. Contract Management

PHASE 6

19. Accounting Integrations
20. AI Assistant

PHASE 7

21. Monitoring
22. Fault Detection
23. Production Hardening

===========================================================
MOST IMPORTANT REQUIREMENT
==========================

START WITH MULTI-TENANCY.

Nothing else may be built before tenancy is fully implemented and tested.

===========================================================
MULTI-TENANCY REQUIREMENTS
==========================

Architecture:

Team-based tenancy.

Core tables:

teams
users
team_user

Every business record must contain:

tenant_id

Examples:

documents
transactions
contracts
invoices
audit_logs
usage_records

Requirements:

* Tenant isolation
* Global scopes
* Middleware enforcement
* Policy enforcement
* Route protection
* Background job isolation
* Queue isolation
* Storage isolation

Storage Structure:

tenant-id/
documents/
invoices/
receipts/
contracts/

Verification Tests:

Tenant A must never see Tenant B data.

Test:

Create:

Tenant A
Tenant B

Create records for both.

Verify:

Tenant A cannot access:

* URLs
* APIs
* exports
* reports
* search results
* queued jobs

belonging to Tenant B.

Generate automated tests proving this.

===========================================================
AUTHENTICATION REQUIREMENTS
===========================

Use:

Laravel Breeze
Inertia
React
TypeScript

Implement:

* Registration
* Login
* Logout
* Forgot Password
* Password Reset
* Email Verification
* Remember Me
* Session Management

Generate:

Feature Tests
Unit Tests

Generate:

Sample accounts

Admin
Manager
Accountant
Viewer

===========================================================
AUTHORIZATION REQUIREMENTS
==========================

Implement RBAC.

Roles:

Owner
Admin
Manager
Accountant
Viewer

Permissions:

View
Create
Edit
Delete
Export
Manage Users
Manage Billing
Manage Team

Requirements:

Policies
Middleware
Permission Matrix

Generate documentation explaining:

Who can access what.

===========================================================
FAULT DETECTION SYSTEM
======================

Create a dedicated module:

System Health & Fault Detection Center

Purpose:

Allow junior developers to quickly identify and resolve issues.

Dashboard Requirements:

System Health Summary

Display:

* Errors
* Warnings
* Failed Jobs
* Slow Queries
* Queue Issues
* Storage Issues
* Permission Issues
* API Failures
* Database Failures

For every fault show:

Error ID
Date Time
Severity
Module
Feature
File Name
Full Path
Class Name
Method Name
Exception Message
Root Cause
Recommended Fix
Status

Severity:

Critical
High
Medium
Low

Examples:

Failed Job
Queue Timeout
Database Deadlock
Missing Migration
Storage Permission Error
API Authentication Failure

===========================================================
FAULT DIAGNOSTIC ENGINE
=======================

Every exception must generate:

Fault Record

Store:

fault_logs

Fields:

id
tenant_id
error_code
severity
message
stack_trace
file_name
file_path
line_number
class_name
method_name
root_cause
recommended_fix
status
created_at

Dashboard Features:

Search
Filter
Export
Resolve
Assign

===========================================================
DOCUMENTATION REQUIREMENTS
==========================

For EVERY feature generate:

docs/features/

Example:

docs/features/
            ├── 01-project-setup.md
            ├── 02-multi-tenancy.md
            ├── 03-authentication.md
            ├── 04-authorization.md

Each document must contain:

1.  Purpose
2.  Business Reason
3.  Architecture
4.  Database Changes
5.  Eloquest Relationship (with details explaination for each Models created )
6.  Folder Structure
7.  File Paths
8.  Flow Diagram
9.  Testing Procedure
10. Troubleshooting Guide
11. Maintenance Guide
12. Add artisan cli used to create files or run any command for (migration, new column added to schema, controller, models, etc.)
13. Also follow Laravel 13 documentation for Laravel 13 guideline and development structure
14. Add every test result in documents feature wise including `bash` command run in terminal.
15. Documant must be maintain for every packages install using artisan cli for both either Frontend and Backend implementation to their related documents

Documentation must be written in simple English.

Target audience:

Junior developers.

===========================================================
DIRECTORY DOCUMENTATION
=======================

For every feature generate:

docs/directories/

Example:

app/Actions/Documents/UploadDocumentAction.php

Explain:

Purpose
Dependencies
Inputs
Outputs
Usage

Do this for every file created.

===========================================================
TESTING REQUIREMENTS
====================

For every feature generate:

Unit Tests
Feature Tests
Integration Tests

Provide:

Sample Inputs
Sample Outputs
Expected Results

Coverage target:

Minimum 90%

===========================================================
CODE QUALITY REQUIREMENTS
=========================

Use:

* SOLID Principles
* DRY
* KISS
* Clean Code

Avoid:

* Fat Controllers
* Business Logic in Controllers
* Duplicate Code
* Premature Microservices

Prefer:

Actions
Services
DTOs
Events
Listeners
Jobs

===========================================================
DEPLOYMENT REQUIREMENTS
=======================

Generate deployment guides for:

Hostinger Shared Hosting
Hostinger VPS
Ubuntu VPS

Generate:

Docker
Docker Compose
GitHub Actions

Generate:

Rollback procedures

===========================================================
COPILOT TOKEN OPTIMIZATION
==========================

Because GitHub Copilot Free has limits:

NEVER generate more than one feature at a time.

Response structure:

STEP 1:
Goal

STEP 2:
Files To Create

STEP 3:
Code

STEP 4:
Tests

STEP 5:
Documentation

STEP 6:
Verification Checklist

STOP.

Wait for approval.

===========================================================
STARTING TASK
=============

Begin ONLY with:

MULTI-TENANCY FOUNDATION

Deliver:

1. Architecture Design
2. Database Design
3. Folder Structure
4. Migrations
5. Models
6. Middleware
7. Global Scope
8. Policies
9. Automated Tests
10. Documentation
11. Verification Checklist

Do NOT build authentication yet.

Authentication must only begin after multi-tenancy passes all tests.
