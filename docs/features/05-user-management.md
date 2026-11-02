# 05 - User Management

## Purpose

Implement tenant-aware team user management with role assignment and permission enforcement.

## Business Reason

Team owners and administrators need to invite, edit, and remove members while enforcing tenant isolation.

## Architecture

- `TeamUserController` exposes team member pages and JSON endpoints.
- `CreateTeamMemberAction`, `UpdateTeamMemberRoleAction`, and `RemoveTeamMemberAction` encapsulate business rules.
- `TeamUserStoreRequest` and `TeamUserUpdateRequest` validate input.
- `TeamPolicy::manageUsers` protects access.
- `TeamContext` resolves the current tenant team.

## Database Changes

No schema changes were required.

## Model Relationships

- `Team` has many `members` through `team_user`.
- `User` belongs to many `Team`.
- `User` belongs to many `Role`.

## Folder Structure

- `app/Actions/Team/CreateTeamMemberAction.php`
- `app/Actions/Team/UpdateTeamMemberRoleAction.php`
- `app/Actions/Team/RemoveTeamMemberAction.php`
- `app/Http/Controllers/Team/TeamUserController.php`
- `app/Http/Requests/TeamUserStoreRequest.php`
- `app/Http/Requests/TeamUserUpdateRequest.php`
- `resources/js/Pages/Team/Members.tsx`
- `resources/js/Layouts/AuthenticatedLayout.tsx`
- `tests/Feature/TeamUserManagementTest.php`

## Flow

1. Authenticated users navigate to `/team/members`.
2. `ResolveTeam` populates `TeamContext` from the current user's primary team or membership.
3. `TeamUserController::page` renders the Inertia page.
4. `TeamUserController::index` loads team members and available roles.
5. `CreateTeamMemberAction` invites or creates a user and assigns a role.
6. `UpdateTeamMemberRoleAction` updates a member's role scoped to the team.
7. `RemoveTeamMemberAction` detaches the member from the team.

## Testing Procedure

### Automated test

- Run `php artisan test --filter=TeamUserManagementTest`

### Manual UI test

1. Log in as a user with the `manage_users` permission.
2. Open the application and click the `Team Members` link in the top navigation.
3. Confirm the page loads without errors and shows the `Invite member` form and existing team members.
4. Enter sample data:
   - `Name`: `Jane Doe`
   - `Email`: `jane.doe@example.com`
   - `Role`: `Manager`
5. Click `Invite member`.
   - Expected output: the page refreshes the member list and the new member appears.
   - Expected browser console output: no network fetch errors.
6. Choose a different role for the new member and click `Update role`.
   - Expected output: the displayed role updates immediately.
7. Click `Remove` on the new member.
   - Expected output: the member card disappears and the team no longer contains that user.

## Troubleshooting

- Ensure the current user has `manage_users` permission for the current team.
- Verify `team_user` membership records exist.
- Confirm the `Role` exists for the given `team_id` and `slug`.

## Maintenance

- Add new team management endpoints through `TeamUserController`.
- Keep `Role` permission structure aligned with `Permission::defaultPermissions()`.
- Extend `TeamUserStoreRequest` for invitation email support.
