<?php

namespace App\Policies;

use App\Models\Integration;
use App\Models\Permission;
use App\Models\User;

class IntegrationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::PERMISSION_MANAGE_INTEGRATIONS);
    }

    public function view(User $user, Integration $integration): bool
    {
        return $user->belongsToTeam($integration->team) && $user->hasPermission(Permission::PERMISSION_MANAGE_INTEGRATIONS);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::PERMISSION_MANAGE_INTEGRATIONS);
    }

    public function update(User $user, Integration $integration): bool
    {
        return $user->belongsToTeam($integration->team) && $user->hasPermission(Permission::PERMISSION_MANAGE_INTEGRATIONS);
    }

    public function delete(User $user, Integration $integration): bool
    {
        return $user->belongsToTeam($integration->team) && $user->hasPermission(Permission::PERMISSION_MANAGE_INTEGRATIONS);
    }
}
