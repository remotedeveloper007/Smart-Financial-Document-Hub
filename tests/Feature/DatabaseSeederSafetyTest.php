<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class DatabaseSeederSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_accounts_are_not_seeded_in_production(): void
    {
        $this->app->detectEnvironment(static fn (): string => 'production');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Demo accounts can only be seeded in local or testing environments.');

        app(DatabaseSeeder::class)->run();
    }

    public function test_demo_accounts_receive_their_matching_team_roles(): void
    {
        $this->seed(DatabaseSeeder::class);

        $expectedRoles = [
            'admin@local.test' => 'admin',
            'manager@local.test' => 'manager',
            'accountant@local.test' => 'accountant',
            'viewer@local.test' => 'viewer',
        ];

        foreach ($expectedRoles as $email => $roleSlug) {
            $user = User::query()->where('email', $email)->firstOrFail();

            $this->assertTrue($user->hasRole($roleSlug), "{$email} should have the {$roleSlug} role.");
            $this->assertDatabaseHas('role_user', [
                'user_id' => $user->id,
                'role_id' => $user->roles()->where('slug', $roleSlug)->value('roles.id'),
            ]);
        }
    }
}
