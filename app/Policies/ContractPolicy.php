<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\Permission;
use App\Models\User;

class ContractPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::PERMISSION_VIEW_CONTRACTS);
    }

    public function view(User $user, Contract $contract): bool
    {
        return $user->belongsToTeam($contract->team) && $user->hasPermission(Permission::PERMISSION_VIEW_CONTRACTS);
    }
}
