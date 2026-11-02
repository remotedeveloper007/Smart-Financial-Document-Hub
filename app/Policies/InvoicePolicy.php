<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\Permission;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::PERMISSION_VIEW_INVOICES);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->belongsToTeam($invoice->team) && $user->hasPermission(Permission::PERMISSION_VIEW_INVOICES);
    }
}
