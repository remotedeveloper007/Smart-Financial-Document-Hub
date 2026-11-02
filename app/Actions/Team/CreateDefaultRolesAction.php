<?php

namespace App\Actions\Team;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;

final class CreateDefaultRolesAction
{
    /**
     * Create default roles for a tenant team and assign permissions.
     *
     * @return array<string, Role>
     */
    public function execute(Team $team): array
    {
        collect(Permission::defaultPermissions())->each(function (string $label, string $slug): void {
            Permission::firstOrCreate(['slug' => $slug], ['name' => $label]);
        });

        $roles = [];

        foreach (Role::defaultRolePermissions() as $roleName => $permissionSlugs) {
            $slug = \Illuminate\Support\Str::slug($roleName);

            $role = Role::firstOrCreate(
                ['team_id' => $team->id, 'slug' => $slug],
                ['name' => $roleName, 'description' => "{$roleName} role for {$team->name}"]
            );

            $permissionIds = Permission::query()
                ->whereIn('slug', $permissionSlugs)
                ->pluck('id')
                ->all();

            $role->permissions()->sync($permissionIds);
            $roles[$roleName] = $role;
        }

        return $roles;
    }
}
