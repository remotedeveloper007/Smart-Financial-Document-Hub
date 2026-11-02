# `resources/js/lib/csrfFetch.ts`

## Purpose

This helper sends Laravel's CSRF token with browser requests that change data. Laravel's `web` middleware rejects unsafe requests without a valid token.

## Business reason

An authenticated user's browser automatically sends its session cookie. Without a CSRF token, a malicious third-party page could try to trigger actions using that session. The token lets Laravel verify that a state-changing request came from the application.

## Dependencies

- The HTML shell at `resources/views/app.blade.php` renders a `csrf-token` meta tag.
- Browser `fetch`, `Headers`, and `document` APIs.
- Callers pass the normal `fetch` URL and options.

## Inputs and outputs

- Input: request URL or resource, plus optional standard `RequestInit` options.
- Output: a `Promise<Response>` from the browser's `fetch`.
- Unsafe method with no token: rejects with a clear error and does not send the request.
- Safe method (`GET`, `HEAD`, `OPTIONS`): sends without a CSRF header.

## Flow

1. Read the HTTP method from the request options.
2. For methods other than `GET`, `HEAD`, and `OPTIONS`, look up the CSRF meta tag.
3. Stop with an error if the tag is absent.
4. Add `X-CSRF-TOKEN` while preserving existing request headers.
5. Call the browser's `fetch` and return its response.

## Tests

`resources/js/lib/csrfFetch.test.ts` checks that unsafe calls include the token and that a missing token prevents the network request.

Run:

```bash
npm.cmd run test:unit -- --run resources/js/lib/csrfFetch.test.ts
```

## Maintenance

Use this helper for direct `fetch` calls that submit, update, delete, upload, or otherwise mutate server data. Current call sites include AI prompts, billing subscriptions, team removal, document uploads, integration settings/import/export, and chunked uploads. Inertia form helpers already manage their request headers. Keep the token name aligned with the meta tag in `resources/views/app.blade.php`.
