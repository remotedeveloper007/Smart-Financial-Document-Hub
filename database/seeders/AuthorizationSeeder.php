<?php

namespace Database\Seeders;

use App\Actions\Team\CreateDefaultRolesAction;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use Illuminate\Database\Seeder;

class AuthorizationSeeder extends Seeder
{
    public function run(): void
    {
        collect(Permission::defaultPermissions())->each(function (string $label, string $slug): void {
            Permission::firstOrCreate(['slug' => $slug], ['name' => $label]);
        });

        Team::query()->get()->each(function (Team $team): void {
            app(CreateDefaultRolesAction::class)->execute($team);
        });
    }
}
