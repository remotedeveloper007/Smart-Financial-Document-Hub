<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::PERMISSION_VIEW_TRANSACTIONS);
    }

    public function view(User $user, Transaction $transaction): bool
    {
        return $user->belongsToTeam($transaction->team) && $user->hasPermission(Permission::PERMISSION_VIEW_TRANSACTIONS);
    }

    public function export(User $user): bool
    {
        return $user->hasPermission(Permission::PERMISSION_EXPORT_TRANSACTIONS);
    }
}
