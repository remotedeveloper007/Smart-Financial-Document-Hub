<?php

namespace App\Actions\Team;

use App\Models\Role;
use App\Models\Team;
use App\Models\User;

final class UpdateTeamMemberRoleAction
{
    public function execute(Team $team, User $user, string $roleSlug): User
    {
        $role = Role::query()
            ->where('team_id', $team->id)
            ->where('slug', $roleSlug)
            ->firstOrFail();

        $user->roles()->detach(
            $user->roles()->where('team_id', $team->id)->pluck('roles.id')->all()
        );

        $user->roles()->attach($role->id);

        return $user->refresh();
    }
}
