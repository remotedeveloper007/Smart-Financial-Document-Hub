<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['team_id', 'name', 'slug', 'description'])]
class Role extends Model
{
    use HasFactory;

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role')->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user')->withTimestamps();
    }

    public static function defaultRolePermissions(): array
    {
        return [
            'Owner' => [
                Permission::PERMISSION_VIEW_DOCUMENTS,
                Permission::PERMISSION_VIEW_INVOICES,
                Permission::PERMISSION_VIEW_RECEIPTS,
                Permission::PERMISSION_VIEW_CONTRACTS,
                Permission::PERMISSION_VIEW_TRANSACTIONS,
                Permission::PERMISSION_CREATE_DOCUMENTS,
                Permission::PERMISSION_EDIT_DOCUMENTS,
                Permission::PERMISSION_DELETE_DOCUMENTS,
                Permission::PERMISSION_EXPORT_DOCUMENTS,
                Permission::PERMISSION_MANAGE_USERS,
                Permission::PERMISSION_MANAGE_BILLING,
                Permission::PERMISSION_MANAGE_TEAM,
                Permission::PERMISSION_MANAGE_INTEGRATIONS,
                Permission::PERMISSION_USE_AI,
                Permission::PERMISSION_MANAGE_AI_SETTINGS,
            ],
            'Admin' => [
                Permission::PERMISSION_VIEW_DOCUMENTS,
                Permission::PERMISSION_VIEW_INVOICES,
                Permission::PERMISSION_VIEW_RECEIPTS,
                Permission::PERMISSION_VIEW_CONTRACTS,
                Permission::PERMISSION_VIEW_TRANSACTIONS,
                Permission::PERMISSION_CREATE_DOCUMENTS,
                Permission::PERMISSION_EDIT_DOCUMENTS,
                Permission::PERMISSION_DELETE_DOCUMENTS,
                Permission::PERMISSION_EXPORT_DOCUMENTS,
                Permission::PERMISSION_MANAGE_USERS,
                Permission::PERMISSION_MANAGE_TEAM,
                Permission::PERMISSION_MANAGE_INTEGRATIONS,
                Permission::PERMISSION_USE_AI,
                Permission::PERMISSION_MANAGE_AI_SETTINGS,
            ],
            'Manager' => [
                Permission::PERMISSION_VIEW_DOCUMENTS,
                Permission::PERMISSION_VIEW_INVOICES,
                Permission::PERMISSION_VIEW_RECEIPTS,
                Permission::PERMISSION_VIEW_CONTRACTS,
                Permission::PERMISSION_VIEW_TRANSACTIONS,
                Permission::PERMISSION_CREATE_DOCUMENTS,
                Permission::PERMISSION_EDIT_DOCUMENTS,
                Permission::PERMISSION_EXPORT_DOCUMENTS,
            ],
            'Accountant' => [
                Permission::PERMISSION_VIEW_DOCUMENTS,
                Permission::PERMISSION_VIEW_INVOICES,
                Permission::PERMISSION_VIEW_RECEIPTS,
                Permission::PERMISSION_VIEW_CONTRACTS,
                Permission::PERMISSION_VIEW_TRANSACTIONS,
                Permission::PERMISSION_EXPORT_DOCUMENTS,
                Permission::PERMISSION_EXPORT_TRANSACTIONS,
            ],
            'Viewer' => [
                Permission::PERMISSION_VIEW_DOCUMENTS,
                Permission::PERMISSION_VIEW_INVOICES,
                Permission::PERMISSION_VIEW_RECEIPTS,
                Permission::PERMISSION_VIEW_CONTRACTS,
                Permission::PERMISSION_VIEW_TRANSACTIONS,
            ],
        ];
    }
}
