# `resources/css/app.css`

## Purpose

This is the main Tailwind stylesheet for the application. It contains the shared font setup and the dark theme colors used by the authenticated layout.

## Dependencies

- Tailwind CSS 4 and its Vite plugin.
- The `data-theme="dark"` attribute set on the root HTML element by the authenticated layout.

## Inputs and outputs

- Input: Tailwind classes in Blade and React files, plus the active theme attribute.
- Output: compiled application styles.

## Step-by-step behavior

1. `@import "tailwindcss"` loads Tailwind's utilities.
2. `@plugin "@tailwindcss/forms"` applies the project's existing form styling.
3. `@theme` defines the shared sans-serif font.
4. `@custom-variant dark` makes Tailwind's `dark:` utilities respond to `data-theme="dark"` instead of relying on the operating system setting.
5. The `html[data-theme='dark']` rules set the dark color scheme and page background.
6. The remaining dark rules change common white and light-gray surfaces, common text colors, and neutral borders. Pages can adopt the theme without changing their routes or data behavior.

## How to add a dark color for a new component

1. Prefer adding the matching Tailwind `dark:` utility to the component. For example, pair `bg-white` with `dark:bg-slate-900`.
2. If the component uses a common existing neutral class, check whether that class is already covered by the shared theme selectors.
3. Add a new shared selector only when several pages need the same color behavior. Avoid broad selectors for colored status badges because they may hide important status meaning.
4. Run `npm run build` and inspect the component in both themes.

## Verification

The layout tests verify that choosing dark mode sets the root theme and saves the choice. The production build verifies that Tailwind accepts the custom variant and CSS.
