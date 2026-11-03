# `resources/js/Layouts/AuthenticatedLayout.tsx`

## Purpose

This file draws the shared page frame for signed-in users. It contains the desktop sidebar, the small-screen navigation menu, the user links, and the theme button.

## Dependencies

- `usePage` reads the signed-in user and permissions that Laravel shares with Inertia.
- `Link` changes pages through Inertia without a full browser reload.
- `ApplicationLogo`, `Dropdown`, `NavLink`, and `ResponsiveNavLink` are existing shared components.
- `NavLink.tsx` and `ResponsiveNavLink.tsx` provide dark colors for their desktop and mobile navigation links.
- Tailwind CSS provides the responsive and color utility classes.
- The browser's `localStorage` remembers the sidebar and theme choices.
- The route helper creates URLs for the application's named routes.

## Inputs and outputs

### Inputs

- `header`: optional page title or page actions.
- `children`: the page content.
- Inertia props: signed-in user, permission slugs, and the admin capability.
- Current route name: used to mark the active page.

### Output

- An expanded or icon-only desktop sidebar.
- A responsive navigation menu for narrow screens.
- A light or dark color theme.
- The page header and page content.

## Step-by-step behavior

1. The layout reads the signed-in user and permissions from Inertia.
2. It builds the same permission checks used by the existing navigation. This change does not add permissions or change backend routes.
3. `sidebarCollapsed` reads `financial-hub-sidebar-collapsed` from browser storage. When it is `true`, the sidebar is 5rem wide and the content is padded by the same amount. Otherwise both use the original 18rem width.
4. The sidebar button updates that state. It also changes its accessible name between **Collapse sidebar** and **Expand sidebar**.
5. `NavigationIcon` selects a small inline SVG path from `navigationIconPaths`. This avoids adding an icon package.
6. `SidebarLink` draws one link. In collapsed mode, it hides the visible label but keeps an accessible label and a tooltip. The `aria-current="page"` attribute, indigo background, colored icon, and left marker identify the current page.
7. `NavigationSection` draws a group heading. The heading remains available to screen readers while its visible text is hidden in collapsed mode.
8. Each existing navigation link remains guarded by its existing permission check. System Health and Queue Monitor remain limited to `can.viewAdmin`.
9. `theme` reads `financial-hub-theme` from browser storage. The page starts in light mode unless the saved value is `dark`.
10. The theme effect sets `data-theme` on the root `<html>` element and saves the new value. The CSS file uses this attribute to style the shared shell and common page surfaces.
11. `AppearanceToggle` displays a moon or sun icon and says which mode it will switch to. Its `aria-pressed` value tells assistive technology whether dark mode is active.
12. The desktop has a theme button in the top bar. The mobile navigation row has a matching button that changes the same layout state.
13. The existing small-screen menu and its links are kept. Its open/close button now also reports its state to assistive technology.

## Important component responsibilities

- `NavigationIcon`: maps a typed icon name to an SVG.
- `SidebarLink`: keeps link behavior, active-page feedback, and collapsed accessibility together.
- `NavigationSection`: keeps group headings readable in both sidebar widths.
- `AppearanceToggle`: draws a controlled theme button; it does not make authorization decisions.
- `Authenticated`: reads the application state and arranges the components.

## Usage

Authenticated Inertia pages already wrap their page content in this layout. Do not copy the sidebar into individual pages. Use a named route and preserve its permission check when adding a navigation entry.

## Safety and authorization

Hiding a link is only a user-interface choice. Laravel route middleware and policies remain the security boundary. Do not remove or weaken backend authorization when changing this layout.

## Verification

Run the layout tests:

```bash
npm run test:unit -- resources/js/Layouts/AuthenticatedLayout.test.tsx
```

Check TypeScript and create the production frontend bundle:

```bash
npm run build
```
