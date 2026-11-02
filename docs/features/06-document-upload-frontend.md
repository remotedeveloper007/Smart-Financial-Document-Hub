# 06 - Document Upload Frontend

## 1. Purpose

Explain the React/Inertia PDF upload page and the authenticated sidebar that hosts it.

## 2. Business reason

Users need clear upload feedback while sensitive financial files remain private and are scanned asynchronously. The page must distinguish a request accepted by the server from a completed scan.

## 3. Architecture

- `resources/js/Pages/Documents/Upload.tsx` renders the upload form within `AuthenticatedLayout`.
- Inertia `useForm` stores the entered name, selected file, and validation errors.
- `csrfFetch` sends a JSON-accepting multipart request with the CSRF header.
- The page handles field validation, general server/network failures, a disabled submit state, and an accessible success message.
- The desktop sidebar is rendered by `resources/js/Layouts/AuthenticatedLayout.tsx`; the existing responsive navigation remains available on smaller screens.

## 4. Page flow

1. The user opens the authorized `documents.upload` route.
2. The form collects a name and a PDF file.
3. `submit` stops browser-native form navigation, sets the submitting state, clears stale errors, then builds `FormData`.
4. `csrfFetch` posts to the named `documents.store` route.
5. A 422 response maps the server's first field error to the corresponding form control.
6. Any other non-success response produces a general error message.
7. A successful JSON response displays the accepted document ID and queued-scan message, then resets the form. It does not claim that scanning or extraction has finished.

## 5. Component notes (in source order)

- Imports select the shared layout, Inertia page/form helpers, React event/state types, and the CSRF-aware fetch helper.
- `DocumentPayload` describes the response fields used by this page; it is not a backend persistence model.
- `submitting`, `requestError`, and `successMessage` hold request UI state.
- `useForm` holds `name`, nullable `File`, and field-level errors.
- `submit` handles the request lifecycle. Its `try/catch/finally` ensures the submit button is re-enabled even when the network fails.
- `FormData` carries multipart data without manually setting a boundary header.
- The response branches treat validation errors separately from server errors and success.
- The JSX wraps all content in the authenticated shell, labels both inputs, exposes validation/general errors with `role="alert"`, and announces success with `role="status"`.

## 6. Sidebar behavior

- At large breakpoints (`lg`), the layout reserves space for a fixed 18rem left sidebar.
- Navigation links use Laravel named routes and `route().current(...)` to mark the active page.
- Workspace, financial data, tools, and administration are grouped for discoverability.
- Admin links are rendered only when the server-provided `can.viewAdmin` flag is true.
- The existing responsive navigation state is retained for smaller screens; this change does not replace mobile behavior.
- Confirm each named route exists before renaming navigation targets.

## 7. Files and dependencies

- `resources/js/Pages/Documents/Upload.tsx` - upload form and request states.
- `resources/js/Layouts/AuthenticatedLayout.tsx` - authenticated shell and navigation.
- `resources/js/lib/csrfFetch.ts` - CSRF-protected direct fetch requests.
- `app/Http/Controllers/DocumentController.php` - page and JSON upload endpoints.
- `app/Http/Requests/StoreDocumentRequest.php` - server-side validation.
- Runtime dependencies: React, Inertia React, and the application's route helper.
- Styling/build dependencies: Tailwind CSS and Vite/TypeScript configured by the project.

## 8. Manual frontend test

1. Start the Laravel app, Vite assets, and an authorized signed-in session.
2. Open `/documents/upload`; verify the page renders inside the desktop sidebar.
3. Submit an invalid or missing file and verify the server validation message appears beside the relevant field.
4. Simulate an unavailable server/network and verify the general error appears and the button becomes enabled again.
5. Upload a valid PDF and verify one success message appears with a document ID; confirm the form resets.
6. Resize to a narrow viewport and confirm the responsive navigation remains usable.
7. Inspect browser network tools: the request must be `POST /documents`, multipart form data, and include the CSRF token. The response is `201` for an accepted upload.

## 9. Automated verification

```bash
npm run test:unit -- --run
npm run build
php artisan test --filter=DocumentUploadTest
```

Recorded local results for this update:

- Frontend tests: 3 test files, 4 tests passed; the Vitest runner emitted worker timeout warnings after completing.
- `npm.cmd run build`: passed TypeScript validation and production bundling.
- Full backend: `php artisan test` - 137 passed, 1 skipped, 391 assertions on the configured MySQL test database.
- Focused scan/chunk tests: 7 passed, 27 assertions at the time of the focused scanner check.

## 10. Troubleshooting

- **Page route error:** check `documents.upload` in `routes/web.php` and the controller policy.
- **Missing CSRF token:** check the Blade root's CSRF meta tag and `resources/js/lib/csrfFetch.ts`.
- **422 response:** check `StoreDocumentRequest` rules and the JSON `errors` response.
- **No success state:** inspect browser network errors and response status; success indicates upload acceptance only.
- **Sidebar route exception:** compare the route helper names here with `php artisan route:list`.
- **Sidebar covers content:** verify `lg:pl-72` matches the desktop sidebar width and check narrow viewport styles.

## 11. Maintenance

- Keep frontend fields (`name`, `file`) aligned with backend validation.
- Do not set `Content-Type` manually for browser `FormData`.
- Keep status messaging explicit: acceptance is not equivalent to malware scan or document extraction completion.
- When adding navigation, verify route existence, active-state matching, responsive behavior, and server-side authorization independently.
- For frontend tests use the configured Vitest runner; no additional package installation is required.
