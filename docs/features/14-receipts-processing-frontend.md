# 14 - Receipts Processing (Frontend)

Document for: `resources/js/Pages/Receipts/Index.tsx`

## Purpose

Explain the frontend receipts listing page, how it works, and how to test it manually. This file contains a line-by-line explanation to help junior developers maintain and troubleshoot the page.

## Flow

- Inertia page fetches `/receipts/list` and renders a table similar to invoices.

## Manual testing

1. Create a team and add receipts (or upload a document to generate receipts).
2. Log in as user with `view_receipts` permission.
3. Visit `/receipts` and verify rows show `issue_date`, `vendor_name`, `receipt_number`, `total_amount`, `currency`, `status`, `reference`.

Stop after review.
