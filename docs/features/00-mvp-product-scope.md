# First MVP Scope: Bank Statement Workflow

**Status:** Scope decisions confirmed by the product owner  
**Version:** 1.0  
**Date:** 2026-11-05  
**Related plan:** [Development Roadmap](../DEVELOPMENT-ROADMAP.md)  
**Audit source:** [Project audit](../Analyses%20and%20recomended%20fix.md)

## 1. Purpose

This document defines the first useful version of Smart Financial Document Hub.
It keeps the first release focused on one complete task for accountants and
bookkeeping firms. It is not a promise that every bank or accounting product is
already supported.

## 2. First users and their task

### First users

- Accountants
- Bookkeeping firms

### Main task

1. A user uploads a bank statement.
2. The system scans and processes the file.
3. The user checks the transactions and any warnings.
4. The user corrects or approves items that need attention.
5. The user downloads the approved data.

The original statement must remain available as evidence and be linked to the
transactions created from it.

## 3. Supported files and bank formats

### File types in scope

- PDF
- PNG
- JPG
- JPEG

The first release will support statements from **one or two banks**. The bank
names are intentionally not selected in this document. Select them after
checking pilot-customer needs, sample-file availability, extraction feasibility,
and data-use permission.

Do not claim that every statement from a selected bank works. Record supported
statement layouts and known limits in the customer documentation.

### Balanced first-release boundary

- Prefer one reliable statement layout over many weakly supported layouts.
- Keep the extraction service replaceable so another parser or provider can be
  added without changing the product's core workflow.
- Clearly mark an unsupported or unreadable file. Do not create made-up
  transactions or present sample data as extracted results.

## 4. Transaction information

For each extracted transaction, the product should store and show:

- Transaction date
- Description
- Debit amount, when present
- Credit amount, when present
- Running balance, when present
- Currency
- Link to the source statement

Amounts must be handled with decimal-safe backend and database logic. The
frontend may format amounts for display, but must not calculate financial
balances with JavaScript floating-point arithmetic.

Where the source format permits it, keep a page, row, or other source location
so a reviewer can find the original value in the statement.

## 5. Review, uncertainty, and duplicates

- Flag uncertain, incomplete, inconsistent, or suspicious values for human
  review.
- Flag duplicate statements or transactions for review or apply an approved
  duplicate rule.
- Never silently accept a balance mismatch or silently discard a possible
  duplicate.
- Show the user why an item needs review and what action is available.
- Keep the original extracted value when a user corrects it, and record who
  made the correction and when. Detailed audit requirements are completed in
  the later review-and-evidence milestone.

The exact extraction confidence threshold and duplicate-matching rules are not
set yet. Define them from real, approved sample statements and test results;
do not guess a threshold.

## 6. Exports

### First export

CSV is the first export format. Export only the records allowed by the user's
permissions and the workflow's review rules.

### Accounting software formats

The product should be able to add accounting-specific export formats without
changing the core transaction workflow. Tally is an audit-recommended candidate,
but the first accounting format should be prioritized with pilot users. Other
accounting systems already integrated with the application can be considered
after their input/output requirements are verified.

Do not state that an accounting export is supported until its field mapping,
file shape, import behavior, and tests are confirmed for that product. Each
format should have its own adapter and acceptance tests.

## 7. Original statement retention

The agreed direction is to keep each original statement until the team deletes
it, subject to an approved retention rule.

Before real customer data is accepted, the product owner must approve the
retention period and rules for:

- Customer-requested deletion
- Legal or accounting retention duties
- Backups and backup expiry
- Copies held by extraction or accounting providers
- What happens to extracted transactions when a source statement is deleted

Until these rules are approved, use synthetic or consented test documents only.
Keep uploaded files in private, team-scoped storage and authorize every view
and download.

## 8. Product and safety boundaries

The first MVP is a statement-processing workflow, not a general financial
assistant and not a promise of fully automatic bookkeeping.

- A human must be able to review flagged data before export.
- A provider outage, unsupported file, or failed scan must produce a clear
  failure/review state.
- Do not use fake extraction in production.
- Do not send customer statements to a third party until provider terms,
  permissions, privacy, and retention have been reviewed.
- Preserve tenant isolation for statements, transactions, searches, exports,
  and background jobs.

## 9. Acceptance checklist

The MVP scope is accepted when implementation can demonstrate all of these:

1. An authorized accountant can upload a PDF, PNG, JPG, or JPEG statement.
2. The file is scanned before extraction.
3. The application handles only the explicitly supported bank/layout versions.
4. A supported statement produces the approved transaction fields without
   inventing values.
5. Every transaction links to its source statement and, where possible, source
   page or row.
6. Uncertain data, balance mismatches, and possible duplicates are visible and
   require review under approved rules.
7. A user can review and correct data while the original value remains
   traceable.
8. The user can download a correctly formatted CSV of permitted records.
9. A user cannot view, correct, export, or download another team's data.
10. Tests cover success, invalid input, unsupported formats, scan failure,
    extraction failure, retries, duplicates, balance mismatch, permissions,
    tenant separation, and export safety.
11. The product documentation states exactly which bank layouts and accounting
    exports are supported.

## 10. Decisions made and items deferred

### Confirmed

- First users: accountants and bookkeeping firms.
- First workflow: upload, inspect extracted transactions, then export.
- Initial file types: PDF, PNG, JPG, JPEG.
- Initial bank coverage: one or two banks.
- Transaction fields: date, description, debit, credit, balance, currency, and
  source statement link.
- Uncertain or repeated data must be flagged for a person; it must not be
  silently accepted.
- CSV is the first export.
- Accounting-specific formats should be extendable and based on verified user
  needs.
- Retain statements until team deletion, subject to an approved retention rule.

### Deliberately deferred

- The first bank names and statement layout versions.
- The final extraction provider and confidence threshold.
- Exact duplicate-detection rules.
- The first accounting-specific format after CSV.
- Retention durations, backup expiry, legal holds, and deletion effects.
- Pilot accuracy targets and supported-language/currency limits.

These are not silently assumed. Resolve them at the relevant roadmap milestone
before accepting real customer statements or claiming support.

## 11. Milestone 0 exit result

The MVP workflow and its boundaries are documented. The remaining choices above
are explicitly deferred rather than guessed. This is sufficient to move to
Milestone 1 planning: prove tenant and security boundaries. It is not approval
to begin Milestone 1 implementation; stop after this scope document and wait for
the product owner's approval to proceed.
