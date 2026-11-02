# resources/js/Pages/Documents/Upload.tsx

## Purpose

Render the document upload page and send the uploaded PDF to the backend.

## Business Reason

A dedicated upload UI allows tenants to add financial documents into their workspace through a simple form.

## Architecture

- Uses `AuthenticatedLayout` to wrap the page in the secure dashboard.
- Uses Inertia `Head` to set the page title.
- Uses React `useForm` to manage form state.
- Sends `FormData` to `POST /documents`.

## Flow

1. `useForm` initializes `name` and `file` fields.
2. User fills the document name and selects a PDF.
3. The `submit` handler builds `FormData` and sends it via `fetch`.
4. On success, the page reloads.

## Line-by-line explanation

- `import AuthenticatedLayout ...`: imports shared layout.
- `useForm({ name: '', file: null })`: tracks the form fields.
- `submit(event)`: prevents default form submission and posts the form via `fetch`.
- `formData.append('name', form.data.name)`: sends the document name.
- `formData.append('file', form.data.file)`: sends the selected PDF.
- `window.location.reload()`: refreshes the page after upload.

## Manual frontend test

- Input: name `Bank Statement July`, file `statement.pdf`.
- Output: request sent as `multipart/form-data`, no console error, response status `201`.
- If upload fails, inspect the browser `Network` tab and the request payload.

## Why this is useful

Provides a clean upload experience and ensures file uploads are handled securely.
