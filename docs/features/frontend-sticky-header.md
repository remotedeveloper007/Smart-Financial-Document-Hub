# Sticky Header for Authenticated Pages

## Purpose

The shared authenticated layout keeps the main navigation visible while users scroll through any page that uses it. Page title headers remain stacked below the desktop navigation bar.

## Files changed

- `resources/js/Layouts/AuthenticatedLayout.tsx`
- `docs/directories/resources-js-layouts-authenticatedlayout.md`

## How it works

1. Authenticated pages already use `AuthenticatedLayout`, so changing the shared layout applies the behavior across those pages.
2. The desktop top bar uses CSS `position: sticky` at the top of the viewport.
3. The mobile navigation bar uses the same sticky top position.
4. A high `z-index` keeps the navigation visible above scrolling page content.
5. On desktop, the optional page title header sticks at 4rem from the top, directly below the 4rem navigation bar.
6. On small screens, the optional page title remains in normal flow. This prevents it from covering the mobile menu when that menu expands.
7. Sticky positioning keeps each header in the page layout. It does not overlay the beginning of page content like `position: fixed` can.

## Verification steps

1. Open a signed-in page with enough content to scroll.
2. Scroll down on a desktop-sized screen. The navigation bar should remain at the top, and a page title header should remain just below it when provided.
3. Scroll on a narrow screen. The mobile navigation bar should remain at the top.
4. Open the mobile navigation menu and confirm its links remain visible and are not covered by the page title.
5. Check that the sidebar, page content, and navigation links continue to work.

## Automated checks

Run the layout tests and production build:

```bash
npm run test:unit -- resources/js/Layouts/AuthenticatedLayout.test.tsx
npm run build
```

## Maintenance notes

- Keep the desktop title offset (`top-16`) aligned with the top bar height (`h-16`).
- Preserve the `z-index` ordering so the top bar stays above page content and the page title stays below the top bar.
- If the top bar height changes, update the desktop title offset at the same time.
- This is a presentation-only change. It does not change routes, permissions, or page data.
