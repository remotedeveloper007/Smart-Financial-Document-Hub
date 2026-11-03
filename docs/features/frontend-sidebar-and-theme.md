# Collapsible Sidebar and Light/Dark Theme

## 1. Purpose

This feature lets signed-in users:

- Make the desktop sidebar wider or smaller.
- Keep only navigation icons visible when it is small.
- See which page is currently open, even when only icons are shown.
- Switch between light and dark colors.
- Keep their choices after moving between pages or refreshing the browser.

The small-screen navigation menu continues to work as before.

## 2. Why it is useful

A narrow sidebar leaves more room for financial data. Icons keep the main links available, and the active marker helps users find their current page. Dark mode gives users a lower-brightness option for dim environments.

## 3. Files

- `resources/js/Layouts/AuthenticatedLayout.tsx`: sidebar controls, icons, theme button, and saved preferences.
- `resources/css/app.css`: dark color rules for the shared layout and common page surfaces.
- `resources/js/Components/NavLink.tsx`: dark styles for the compact horizontal navigation links.
- `resources/js/Components/ResponsiveNavLink.tsx`: dark styles for the mobile menu links.
- `resources/js/Layouts/AuthenticatedLayout.test.tsx`: automated checks for saving and restoring preferences.

No route, database, API, or backend permission behavior is changed.

## 4. How the sidebar works

1. On a desktop-sized screen, the left sidebar is fixed to the side of the window.
2. The layout stores whether the sidebar is collapsed in React state.
3. If there is no saved setting, the sidebar starts expanded.
4. The **Collapse sidebar** button changes the setting to collapsed.
5. In collapsed mode, the sidebar uses a narrow width, the page content moves to match it, and each authorized link shows its icon.
6. Link text is visually hidden, but its accessible name remains. Hovering over an icon also shows its label.
7. The current link gets `aria-current="page"`, an indigo background, and a narrow indigo marker. These signals work even when its text is hidden.
8. The sidebar width choice is stored as `financial-hub-sidebar-collapsed` in browser `localStorage`.
9. The next time the user opens a page, the saved width is restored.

The groups and links still use the permission checks that were already present. The sidebar does not grant access. Laravel routes and policies still decide whether a request is allowed.

## 5. How the theme works

1. The layout reads `financial-hub-theme` from browser `localStorage`.
2. The first visit uses light mode unless `dark` was already saved.
3. The theme button shows a moon in light mode and a sun in dark mode. Its accessible name explains the mode it will switch to.
4. Clicking the button updates the shared theme state.
5. The layout sets `data-theme="dark"` or `data-theme="light"` on the root HTML element.
6. The layout saves the selected value under `financial-hub-theme`.
7. Tailwind's custom `dark:` variant and shared CSS rules use that root attribute to color the sidebar, top bar, page background, common cards, text, and borders.
8. The theme control is available in the desktop top bar and the small-screen navigation row. Both controls use the same state.

The theme is a browser display preference. It does not change financial data, requests, permissions, or account settings on the server.

## 6. Step-by-step guide for using this pattern in another project

1. Create a shared authenticated layout. Keep navigation in one place instead of copying it into every page.
2. Make a typed list of icon names and SVG paths. A type catches misspelled icon names.
3. Make one reusable link component that accepts the URL, label, icon, active state, and collapsed state.
4. Give icon-only links an `aria-label` and `title`. Do not remove the label from assistive technology.
5. Mark the current route with `aria-current="page"`. Add a color change and a shape or marker so active state is not shown by color alone.
6. Add a boolean sidebar state. Load it from local storage, update it when the button is clicked, and save it after it changes.
7. Change the content's left padding together with the sidebar width. If those widths do not match, content will sit under the sidebar.
8. Add one theme state with the allowed values `light` and `dark`.
9. Put a `data-theme` attribute on the root HTML element whenever the theme changes.
10. Add a Tailwind custom dark variant or use a consistent dark class on the root element. Pick one convention and use it throughout the app.
11. Add dark colors for the page background, common surfaces, text, and borders. Do not recolor every element indiscriminately; status colors can carry meaning.
12. Save the theme in local storage and initialize from the saved value on the next visit.
13. Give the theme button a clear accessible name that says what clicking it will do.
14. Write tests for clicking each control and restoring saved choices.
15. Check the layout at desktop and mobile widths, and check both themes before shipping.

## 7. Automated tests

The layout test covers:

- Sidebar collapse and persisted collapsed state.
- The current page marker and icon label in collapsed mode.
- Theme switching and persistence.
- Restoring saved sidebar and theme settings.

Run the focused tests:

```bash
npm run test:unit -- resources/js/Layouts/AuthenticatedLayout.test.tsx
```

Check TypeScript and build the production assets:

```bash
npm run build
```

## 8. Manual verification checklist

1. Sign in and open any page that uses `AuthenticatedLayout`.
2. On a desktop browser, select **Collapse sidebar**.
3. Confirm only icons are visible, and the page content starts to the right of the sidebar.
4. Confirm the current page icon has the active background and marker.
5. Hover over an icon and confirm its label appears.
6. Select **Expand sidebar** and confirm the labels and section headings return.
7. Refresh the browser. Confirm the sidebar choice is remembered.
8. Select the moon button. Confirm the sidebar, top bar, page background, cards, and text use dark colors.
9. Select the sun button. Confirm light colors return.
10. Refresh the browser. Confirm the selected theme is remembered.
11. Use a narrow browser window. Confirm the existing menu still opens and closes, and the theme button remains available.
12. Try a role with fewer permissions. Confirm unauthorized tabs remain hidden. Then verify that protected URLs are still enforced by Laravel as before.

## 9. Troubleshooting

- **The sidebar opens after refresh:** check that `localStorage` allows browser storage and that the key is `financial-hub-sidebar-collapsed`.
- **The theme resets after refresh:** check the `financial-hub-theme` browser storage value.
- **The shell changes but a new page stays bright:** add suitable `dark:` classes to the new component, or extend the shared dark selectors for its neutral colors.
- **The active page is not highlighted:** check the route name passed to `route().current(...)`.
- **A navigation item is missing:** check the permission props and keep the server-side role/permission configuration as the authority.
- **The browser reports a denied request:** do not work around the route policy by changing only the UI. Check Laravel authorization separately.
- **The mobile menu is missing:** confirm the viewport is below the `lg` breakpoint; the desktop sidebar is intentionally hidden on small screens.

## 10. Maintenance

- Keep sidebar entries aligned with their existing route and permission checks.
- Add each new icon name to `NavigationIconName` and `navigationIconPaths`.
- Avoid adding an icon dependency just for this navigation.
- Test both active and inactive links after adding routes.
- Keep storage keys stable. Changing a key resets that saved preference for existing users.
- Keep this document and the layout directory notes up to date if the theme behavior or storage keys change.

## 11. Commands and test results

No packages were added for this feature.

Verified in this change:

- `npm.cmd run build` — passed; TypeScript check and Vite production build completed.
- `npm.cmd run test:unit -- resources/js/Layouts/AuthenticatedLayout.test.tsx` — passed; 1 test file and 3 tests.
- `npm.cmd run test:unit` — passed; 4 test files and 7 tests.

On Windows PowerShell, `npm` may resolve to a blocked `npm.ps1` script. Use `npm.cmd` for the same commands in that terminal. Bash users can use the `npm` commands shown in the test sections above.
