# 05 - User Management Verification Checklist

## Feature Scope

- [x] Team users can be listed.
- [x] Team users can be invited.
- [x] Team user roles can be updated.
- [x] Team users can be removed.
- [x] Access is restricted by `TeamPolicy::manageUsers`.
- [x] Team membership stays tenant-scoped.
- [x] Frontend uses Inertia and React.
- [x] Controller remains thin through actions.

## Manual Verification

- [ ] Log in as a user with `manage_users` permission.
- [ ] Click `Team Members` in the top navigation.
- [ ] Confirm the page loads and the invite form displays.
- [ ] Use sample input: `Jane Doe`, `jane.doe@example.com`, `Manager`.
- [ ] Confirm the invited member appears in the list.
- [ ] Change the invited member's role and confirm the updated role displays.
- [ ] Remove the invited member and confirm they disappear.
- [ ] Check browser network activity for successful POST, PUT, and DELETE requests.

## Test Verification

- [x] `php artisan test --filter=TeamUserManagementTest`

## Documentation Verification

- [x] `docs/features/05-user-management.md`
- [x] `docs/features/05-user-management-frontend.md`
- [x] `docs/directories/app-http-controllers-team-teamusercontroller.md`
- [x] `docs/directories/app-actions-team-createteammemberaction.md`
- [x] `docs/directories/app-actions-team-updateteammemberroleaction.md`
- [x] `docs/directories/app-actions-team-removeteammemberaction.md`
- [x] `docs/directories/app-http-requests-teamuserstorerequest.md`
- [x] `docs/directories/app-http-requests-teamuserupdaterequest.md`
- [x] `docs/directories/resources-js-pages-team-members.md`
