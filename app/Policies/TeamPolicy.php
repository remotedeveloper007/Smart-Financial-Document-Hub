<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    public function view(User $user, Team $team): bool
    {
        return $user->team_id === $team->id || $user->teams()->where('team_id', $team->id)->exists();
    }

    public function update(User $user, Team $team): bool
    {
        return $user->hasPermission(Permission::PERMISSION_MANAGE_TEAM) && $this->view($user, $team);
    }

    public function manageUsers(User $user, Team $team): bool
    {
        return $user->hasPermission(Permission::PERMISSION_MANAGE_USERS) && $this->view($user, $team);
    }
}
