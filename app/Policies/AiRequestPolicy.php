<?php

namespace App\Policies;

use App\Models\AiRequest;
use App\Models\Permission;
use App\Models\User;

class AiRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::PERMISSION_USE_AI);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::PERMISSION_USE_AI);
    }

    public function view(User $user, AiRequest $request): bool
    {
        return $user->belongsToTeam($request->team)
            && $user->hasPermission(Permission::PERMISSION_USE_AI)
            && $request->user_id === $user->id;
    }
}
