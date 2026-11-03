# `resources/js/Components/ResponsiveNavLink.tsx`

## Purpose

This reusable link component draws the full-width navigation links shown in the small-screen menu.

## Dependencies

- Inertia React `Link` handles navigation without a full page reload.
- Tailwind CSS utility classes style active, inactive, hover, and focus states.
- The authenticated layout provides the `active` value.

## Inputs and outputs

- Inputs: Inertia link properties, the link text, and an optional `active` boolean.
- Output: a full-width link with a left marker for the current page.

## Step-by-step behavior

1. The component receives the route and link label from its caller.
2. The caller passes `active=true` when the current route matches this link.
3. The component shows the active link with an indigo background, text, and left border.
4. It provides hover and keyboard-focus styles for inactive links.
5. Dark-specific classes replace the light surfaces and text when `data-theme="dark"` is active.
6. It forwards the remaining Inertia link properties to the shared `Link`.

## Usage

Use this component in the existing small-screen navigation menu. Keep the existing backend route protection and caller permission checks.

## Verification

Run the frontend suite and production build:

```bash
npm run test:unit
npm run build
```
