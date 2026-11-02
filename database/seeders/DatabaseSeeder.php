<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use LogicException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Demo accounts can only be seeded in local or testing environments.');
        }

        $team = Team::factory()->create([
            'name' => 'Primary Team',
        ]);

        $usersWithRoles = collect([
            [
                'name' => 'Admin User',
                'email' => 'admin@local.test',
                'role' => 'admin',
            ],
            [
                'name' => 'Manager User',
                'email' => 'manager@local.test',
                'role' => 'manager',
            ],
            [
                'name' => 'Accountant User',
                'email' => 'accountant@local.test',
                'role' => 'accountant',
            ],
            [
                'name' => 'Viewer User',
                'email' => 'viewer@local.test',
                'role' => 'viewer',
            ],
        ])->map(function (array $userData) use ($team): array {
            $user = User::factory()->create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make('password'),
                'team_id' => $team->id,
            ]);

            $team->members()->syncWithoutDetaching([$user->id]);

            return ['user' => $user, 'role' => $userData['role']];
        });

        $this->call(AuthorizationSeeder::class);

        $usersWithRoles->each(function (array $userData) use ($team): void {
            $role = Role::query()
                ->where('team_id', $team->id)
                ->where('slug', $userData['role'])
                ->firstOrFail();

            $userData['user']->roles()->syncWithoutDetaching([$role->id]);
        });
    }
}
