# resources/js/Pages/Team/Members.tsx

## Purpose

Render the team member management page and interact with team membership endpoints.

## Business Reason

Provides a UI for administrators to invite members, assign roles, and remove users within the current tenant.

## Architecture

- Uses React state hooks to manage members and role selection.
- Uses `useForm` from Inertia for POST and PUT submissions.
- Fetches team member and role data from the `team.members.index` endpoint.
- Sends remove requests using the browser Fetch API.

## Folder Structure

- `resources/js/Pages/Team/Members.tsx`

## Dependencies

- `@inertiajs/react`
- `react`
- `tailwindcss`

## Flow

1. `useEffect` loads existing team members when the component mounts.
2. The invite form submits name, email, and role.
3. The role dropdown sends changes to the backend.
4. The remove button deletes a team member.

## Testing Procedure

1. Open `/team/members` in the browser.
2. Confirm the invite form and member list are visible.
3. Add a member with name, email, and selected role.
4. Update a member role and ensure the list refreshes.
5. Remove a member and ensure the list updates.

## Tests included

- Covered by `tests/Feature/TeamUserManagementTest.php`

## Troubleshooting

- If the page fails to load, confirm `resources/views/app.blade.php` uses `@vite(['resources/js/app.tsx'])`.
- If the member list is empty, inspect the network response from `/team/members/list`.
- If role update fails, confirm `role_slug` value matches an existing team role slug.

## Manual Sample Data

- `name`: `Jane Doe`
- `email`: `jane.doe@example.com`
- `role`: `Manager`

## Expected Output

- The new member appears in the UI after invite.
- Role updates are reflected immediately after clicking `Update role`.
- Remove hides the deleted member card.

## Line-by-Line Explanation

- `import AuthenticatedLayout ...`: uses shared layout for authenticated pages.
- `type Role` / `type Member`: define the data structure received from the backend.
- `useState` definitions: store members, roles, selected role, and selected user.
- `useForm` definitions: prepare form state for invite and role update.
- `useEffect(() => { loadMembers(); }, []);`: loads data when the page appears.
- `loadMembers()`: fetches JSON data from the backend.
- `createMember()`: submits the invite form and reloads data.
- `updateRole()`: submits the selected role change for the chosen member.
- `removeMember()`: calls DELETE with CSRF token and reloads members.
- `AuthenticatedLayout`: ensures the page uses the application header and navigation.
- The form and list markup: renders input controls and member cards.
