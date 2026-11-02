<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\User;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::PERMISSION_VIEW_AUDIT_LOGS);
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return $user->belongsToTeam($auditLog->team)
            && $user->hasPermission(Permission::PERMISSION_VIEW_AUDIT_LOGS);
    }
}