# `resources/js/Layouts/AuthenticatedLayout.tsx`

## Purpose

Provides the authenticated application frame, including desktop sidebar navigation, responsive navigation, user identity, and page header.

## Dependencies

- Inertia React `Link` and `usePage`.
- Shared `ApplicationLogo`, `Dropdown`, `NavLink`, and `ResponsiveNavLink` components.
- Route helper names defined in `routes/web.php`.

## Inputs and outputs

- Input: optional page header and child page content; authenticated user and capability flags come from Inertia props.
- Output: the shared responsive layout. Admin-only links are hidden unless `can.viewAdmin` is true.

## Usage

Wrap authenticated Inertia pages with this layout. Server routes and policies remain the security boundary; hiding a link is not authorization.
