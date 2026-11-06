# `tests/Feature/TenantIsolationTest.php`

## Purpose

This feature-test file checks that tenant context is selected, used, and cleared
at request and job boundaries.

## Active-team tests

- `test_active_team_switch_is_limited_to_team_memberships`
  - Confirms a member can switch to a second team.
  - Confirms the next request uses that team.
  - Confirms an unrelated team ID is rejected and does not change the active
    workspace.
- `test_forged_active_team_session_value_falls_back_to_a_member_team`
  - Puts an unrelated team ID in the session.
  - Confirms middleware ignores it and selects the user's primary team.
- `test_team_context_is_resolved_before_route_authorization_and_model_binding`
  - Confirms route authorization can read the selected context.
  - Confirms a tenant-scoped document binding finds the current team's
    document, and returns `404` for another team's document.
- `test_all_models_using_belongs_to_team_apply_the_team_scope`
  - Checks that each model using `BelongsToTeam` binds the current team ID in
    its query.
- `test_tenant_model_queries_fail_when_no_team_context_is_available`
  - Confirms a tenant query fails closed instead of returning every team's
    records.
- `test_new_tenant_records_ignore_a_forged_team_id_when_context_exists`
  - Confirms the active context replaces a different team ID supplied during
    model creation.

## Other tests

The file also checks request context, cross-team document query scoping,
automatic team assignment on new documents, and job context cleanup.

## Run the tests

```bash
php artisan test --filter=TenantIsolationTest
```
