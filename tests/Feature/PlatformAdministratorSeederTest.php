<?php

namespace Tests\Feature;

use App\Models\PlatformAdministrator;
use App\Models\User;
use Database\Seeders\PlatformAdministratorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class PlatformAdministratorSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_grants_super_admin_to_configured_verified_user(): void
    {
        $user = User::factory()->create([
            'name' => 'System Admin',
            'email' => 'superadmin@gmail.com',
        ]);
        $user->markEmailAsVerified();
        config()->set('system_access.admin_emails', [' SUPERADMIN@GMAIL.COM ']);

        app(PlatformAdministratorSeeder::class)->run();
        app(PlatformAdministratorSeeder::class)->run();

        $this->assertDatabaseHas('platform_administrators', [
            'user_id' => $user->id,
            'role' => PlatformAdministrator::ROLE_SUPER_ADMIN,
        ]);
        $this->assertDatabaseCount('platform_administrators', 1);
        $this->assertTrue($user->fresh()->isPlatformAdministrator());
    }

    public function test_seeder_fails_when_configured_user_does_not_exist(): void
    {
        config()->set('system_access.admin_emails', ['missing@example.test']);

        $this->expectException(LogicException::class);
        app(PlatformAdministratorSeeder::class)->run();
    }

    public function test_seeder_fails_when_configured_user_is_not_verified(): void
    {
        User::factory()->unverified()->create(['email' => 'unverified@example.test']);
        config()->set('system_access.admin_emails', ['unverified@example.test']);

        $this->expectException(LogicException::class);
        app(PlatformAdministratorSeeder::class)->run();
    }

    public function test_seeder_fails_when_no_system_admin_emails_are_configured(): void
    {
        config()->set('system_access.admin_emails', []);

        $this->expectException(LogicException::class);
        app(PlatformAdministratorSeeder::class)->run();
    }
}
