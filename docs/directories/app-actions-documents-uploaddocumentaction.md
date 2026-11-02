# app/Actions/Documents/UploadDocumentAction.php

## Purpose

Save uploaded PDF files to storage and create tenant-scoped document records.

## Business Reason

The action isolates file persistence and document creation logic away from the controller so the controller remains thin.

## Architecture

- Stores uploaded files on the `public` disk.
- Creates a `Document` model with `name`, `file_path`, and status.
- Relies on `Document` model global tenant scope to assign `team_id`.

## Line-by-line explanation

- `execute(UploadedFile $file, string $name)`
  - `storeAs('tenant-documents', now()->timestamp.'-'.$file->getClientOriginalName(), 'public')`: saves the file under `tenant-documents` with a timestamp prefix.
  - `Document::create([...])`: creates the document record and returns it.

## Why this is useful

This action keeps upload persistence logic reusable and easy to test separately from the controller.
