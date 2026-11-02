<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\Receipt;
use App\Models\User;

class ReceiptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::PERMISSION_VIEW_RECEIPTS);
    }

    public function view(User $user, Receipt $receipt): bool
    {
        return $user->belongsToTeam($receipt->team) && $user->hasPermission(Permission::PERMISSION_VIEW_RECEIPTS);
    }
}
