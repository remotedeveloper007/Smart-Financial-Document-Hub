# app/Http/Controllers/DocumentController.php

## Purpose

Handle document upload page rendering and API requests in a thin controller.

## Business Reason

Separating upload logic into a controller keeps routing and authorization centralized while delegating file persistence to an action.

## Architecture

- Uses Inertia to render the frontend upload page.
- Uses `StoreDocumentRequest` for validation.
- Uses `UploadDocumentAction` to save files and create document records.
- Applies policy authorization for `Document::class`.

## Flow

1. `page()` verifies upload permission and returns the upload page.
2. `index()` returns tenant-scoped documents for future listing.
3. `store()` validates input and delegates persistence to the action.

## Line-by-line explanation

- `page()`
  - `authorize('create', Document::class)`: ensures the user can create documents.
  - `Inertia::render('Documents/Upload')`: renders the frontend upload page.

- `index()`
  - `authorize('create', Document::class)`: reuses the same permission for listing upload data.
  - `Document::query()->orderByDesc('created_at')->get(...)`: fetches tenant-scoped documents from the global scope.
  - `response()->json(['documents' => $documents])`: returns a JSON document list.

- `store()`
  - `StoreDocumentRequest $request`: validates PDF uploads.
  - `$uploadDocumentAction->execute(...)`: saves the file and creates the record.
  - `response()->json($document, 201)`: returns the created document payload.
