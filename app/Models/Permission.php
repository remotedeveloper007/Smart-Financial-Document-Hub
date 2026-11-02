<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'slug', 'description'])]
class Permission extends Model
{
    use HasFactory;

    public const PERMISSION_VIEW_DOCUMENTS = 'view_documents';
    public const PERMISSION_CREATE_DOCUMENTS = 'create_documents';
    public const PERMISSION_EDIT_DOCUMENTS = 'edit_documents';
    public const PERMISSION_DELETE_DOCUMENTS = 'delete_documents';
    public const PERMISSION_EXPORT_DOCUMENTS = 'export_documents';
    public const PERMISSION_VIEW_INVOICES = 'view_invoices';
    public const PERMISSION_VIEW_RECEIPTS = 'view_receipts';
    public const PERMISSION_VIEW_CONTRACTS = 'view_contracts';
    public const PERMISSION_VIEW_TRANSACTIONS = 'view_transactions';
    public const PERMISSION_EXPORT_TRANSACTIONS = 'export_transactions';
    public const PERMISSION_MANAGE_USERS = 'manage_users';
    public const PERMISSION_MANAGE_BILLING = 'manage_billing';
    public const PERMISSION_MANAGE_TEAM = 'manage_team';
    public const PERMISSION_MANAGE_INTEGRATIONS = 'manage_integrations';
    public const PERMISSION_USE_AI = 'use_ai';
    public const PERMISSION_MANAGE_AI_SETTINGS = 'manage_ai_settings';
    public const PERMISSION_VIEW_AUDIT_LOGS = 'view_audit_logs';

    public static function defaultPermissions(): array
    {
        return [
            self::PERMISSION_VIEW_DOCUMENTS => 'View documents',
            self::PERMISSION_CREATE_DOCUMENTS => 'Create documents',
            self::PERMISSION_EDIT_DOCUMENTS => 'Edit documents',
            self::PERMISSION_DELETE_DOCUMENTS => 'Delete documents',
            self::PERMISSION_EXPORT_DOCUMENTS => 'Export documents',
            self::PERMISSION_VIEW_INVOICES => 'View invoices',
            self::PERMISSION_VIEW_RECEIPTS => 'View receipts',
            self::PERMISSION_VIEW_CONTRACTS => 'View contracts',
            self::PERMISSION_VIEW_TRANSACTIONS => 'View transactions',
            self::PERMISSION_EXPORT_TRANSACTIONS => 'Export transactions',
            self::PERMISSION_MANAGE_USERS => 'Manage users',
            self::PERMISSION_MANAGE_BILLING => 'Manage billing',
            self::PERMISSION_MANAGE_TEAM => 'Manage team',
            self::PERMISSION_MANAGE_INTEGRATIONS => 'Manage integrations',
            self::PERMISSION_USE_AI => 'Use AI assistant',
            self::PERMISSION_MANAGE_AI_SETTINGS => 'Manage AI provider settings',
            self::PERMISSION_VIEW_AUDIT_LOGS => 'View audit logs',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'permission_role')->withTimestamps();
    }
}
