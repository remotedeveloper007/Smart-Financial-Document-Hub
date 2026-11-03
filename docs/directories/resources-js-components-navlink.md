# `resources/js/Components/NavLink.tsx`

## Purpose

This reusable link component draws the compact horizontal navigation links used by the existing responsive header.

## Dependencies

- Inertia React `Link` handles navigation without a full page reload.
- Tailwind CSS utility classes style the link and its active state.
- The authenticated layout provides the `active` value.

## Inputs and outputs

- Inputs: Inertia link properties, the link text, and an `active` boolean.
- Output: a horizontal link with an underline for the current page.

## Step-by-step behavior

1. The component receives the route and link label from its caller.
2. The caller passes `active=true` when the current route matches this link.
3. The component applies an indigo underline and stronger text for the active page.
4. It applies hover and keyboard-focus styles to inactive links.
5. Dark-specific classes change the text and underline colors when the root element has `data-theme="dark"`.
6. It forwards the remaining Inertia link properties to the shared `Link`.

## Usage

Use this component for the existing compact horizontal navigation. Do not use it to create another route or permission rule.

## Verification

Run the frontend suite and production build:

```bash
npm run test:unit
npm run build
```
