# 05 - User Management Frontend

## Purpose

Document the React/Inertia team member management page and how it interacts with the backend.

## Business Reason

Team administrators need a simple interface to invite members, assign roles, and remove users while keeping tenant boundaries enforced.

## Architecture

- `resources/js/Pages/Team/Members.tsx` is the Inertia page component.
- The page loads team members and roles from the JSON endpoint at `team.members.index`.
- The page submits member invitations and role changes through REST endpoints.
- The page uses `useForm` from `@inertiajs/react` to manage form state.

## Folder Structure

- `resources/js/Pages/Team/Members.tsx`

## Dependencies

- `@inertiajs/react` for Inertia page rendering and form handling.
- `react` for component state and lifecycle.
- `tailwindcss` classes for layout styling.

## Flow

1. The page mounts and calls `loadMembers()`.
2. `loadMembers()` fetches `/team/members/list` using `route('team.members.index')`.
3. The returned JSON payload sets `members` and `roles` state.
4. The invite form submits a POST request to `team.members.store`.
5. The role update button sends a PUT request to `team.members.update`.
6. The remove button sends a DELETE request to `team.members.destroy`.

## Manual Testing Procedure

1. Log in as a user with `manage_users` permission.
2. Click `Team Members` in the top navigation.
3. Confirm the page title is `Team Members` and the invite form is visible.
4. Enter a user name, email, and role, then click `Invite member`.
   - Sample input:
     - `Name`: `Jane Doe`
     - `Email`: `jane.doe@example.com`
     - `Role`: `Manager`
   - Expected output: a new member appears in the list with the same name and email.
   - Expected browser console: no fetch network errors and a successful POST to `/team/members`.
5. Use the role dropdown for a member and click `Update role`.
   - Expected output: the displayed role updates immediately.
   - Expected network request: PUT to `/team/members/{user}` with `role_slug`.
6. Click `Remove` for a member.
   - Expected output: the member disappears from the list.
   - Expected network request: DELETE to `/team/members/{user}`.

## Tests Included

- `tests/Feature/TeamUserManagementTest.php`

## Test Result

- Command: `php artisan test --filter=TeamUserManagementTest`
- Result: `3 tests, 3 passed`.

## Troubleshooting

- If the page fails to load, verify the user is authenticated and has the `manage_users` permission.
- If the JSON payload is empty, confirm the backend route name `team.members.index` resolves correctly and the current team exists.
- If role update fails, check that `role_slug` is provided and the selected role exists for the team.

## Why This Is Added and Useful

This page puts team membership management directly into the tenant admin interface. It reduces manual backend work and supports onboarding new users quickly.

## Line-by-Line Explanation

- `import AuthenticatedLayout ...`: imports the shared authenticated page layout.
- `type Role` and `type Member`: define the expected data shapes.
- `useState` for `members`, `roles`, `selectedRole`, and `selectedUserId`: tracks page data and current selection.
- `useForm` for `memberForm` and `roleForm`: manage form fields and Inertia submissions.
- `useEffect(() => { loadMembers(); }, []);`: fetches team data on first render.
- `loadMembers()`: fetches the list endpoint and updates state.
- `createMember()`: sends the invite payload and resets the form.
- `updateRole()`: sends the selected role for the chosen member.
- `removeMember()`: deletes the member through the backend endpoint.
- `AuthenticatedLayout`: wraps the page inside the authenticated dashboard UI.
- Form fields and buttons: capture user input and trigger the backend actions.
- `members.map(...)`: renders each member card with role controls and remove button.
