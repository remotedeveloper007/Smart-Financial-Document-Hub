# tests/Feature/DocumentUploadTest.php

## Purpose

Ensure the document upload flow is protected by permissions and successful file uploads are persisted.

## Business Reason

The upload API must only be usable by authorized team members and must store valid PDF files correctly.

## Tests

- `test_user_with_create_documents_permission_can_view_upload_page`
  - Creates a user with `create_documents` permission.
  - Asserts the upload page returns HTTP 200.

- `test_user_with_create_documents_permission_can_upload_pdf`
  - Uses `Storage::fake('public')` to isolate file operations.
  - Uploads a fake PDF to `documents.store`.
  - Asserts the response is `201 Created`.
  - Asserts the `documents` table contains the new record.

## Why this is useful

These tests verify both frontend route access and backend upload processing for tenant-scoped document ingestion.
