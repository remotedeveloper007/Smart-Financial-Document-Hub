# `app/Http/Controllers/Team/SwitchActiveTeamController.php`

## Purpose

This controller changes the active workspace for the signed-in user. It never
trusts the team ID sent by the browser by itself.

## Dependencies

- `Request` reads and validates the submitted `team_id`.
- `User` provides the user's primary team and team membership relation.
- Laravel's session stores the selected team for later requests.
- The authenticated route ensures a signed-in user reaches this controller.

## Inputs

`POST team/active` receives:

```json
{ "team_id": 12 }
```

The ID must be an integer. The team must be the user's primary team or appear in
the user's `team_user` memberships.

## Step-by-step behavior

1. Validate that `team_id` is present and is a positive integer.
2. Look for that ID in the signed-in user's team memberships.
3. If it is not a membership, allow it only when it is the user's primary team.
4. Return `404` when neither check passes. This does not reveal whether another
   team's ID exists.
5. Save the verified ID as `active_team_id` in the user's session.
6. Redirect back. The next web request resolves the newly selected team.

## Output

- Success: redirect to the page that opened the switcher.
- Invalid input: Laravel validation response.
- Team not owned or joined by this user: `404`.

## Maintenance

If team invitations or membership removal change, keep this endpoint's checks
aligned with those rules. Do not accept a client `team_id` without checking the
authenticated user's current access.
