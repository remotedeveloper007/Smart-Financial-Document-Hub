# app/Http/Requests/StoreDocumentRequest.php

## Purpose

Validate the document upload request payload.

## Business Reason

Validating uploads at the request layer prevents invalid files and malformed data from reaching the controller or storage.

## Architecture

- Uses Laravel `FormRequest` authorization and validation.
- Ensures only authorized users can upload documents.
- Enforces required PDF file type and maximum file size.

## Rules

- `name`: required, string, maximum 255 characters.
- `file`: required, must be a PDF, maximum 10 MB.

## Authorization

- `authorize()`: checks whether the user can create a `Document`.

## Why this is useful

This request separates validation concerns from the controller and makes upload rules easy to maintain.
