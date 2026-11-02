<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\Permission;
use App\Models\User;

class DocumentPolicy
{
    public function view(User $user, Document $document): bool
    {
        return $user->belongsToTeam($document->team) && $user->hasPermission(Permission::PERMISSION_VIEW_DOCUMENTS);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::PERMISSION_CREATE_DOCUMENTS);
    }

    public function update(User $user, Document $document): bool
    {
        return $user->belongsToTeam($document->team) && $user->hasPermission(Permission::PERMISSION_EDIT_DOCUMENTS);
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->belongsToTeam($document->team) && $user->hasPermission(Permission::PERMISSION_DELETE_DOCUMENTS);
    }

    public function export(User $user, Document $document): bool
    {
        return $user->belongsToTeam($document->team) && $user->hasPermission(Permission::PERMISSION_EXPORT_DOCUMENTS);
    }
}
