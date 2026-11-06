<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $billingPermissionId = DB::table('permissions')
            ->where('slug', Permission::PERMISSION_MANAGE_BILLING)
            ->value('id');

        if ($billingPermissionId === null) {
            return;
        }

        DB::table('permission_role')
            ->where('permission_id', $billingPermissionId)
            ->whereIn(
                'role_id',
                DB::table('roles')->select('id')->where('slug', 'accountant'),
            )
            ->delete();
    }

    public function down(): void
    {
        $billingPermissionId = DB::table('permissions')
            ->where('slug', Permission::PERMISSION_MANAGE_BILLING)
            ->value('id');

        if ($billingPermissionId === null) {
            return;
        }

        $now = now();
        $grants = DB::table('roles')
            ->where('slug', 'accountant')
            ->pluck('id')
            ->map(fn (int $roleId): array => [
                'role_id' => $roleId,
                'permission_id' => $billingPermissionId,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        if ($grants !== []) {
            DB::table('permission_role')->insertOrIgnore($grants);
        }
    }
};
