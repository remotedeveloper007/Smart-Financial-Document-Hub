<?php

namespace Tests\Feature;

use App\Actions\Team\CreateDefaultRolesAction;
use App\Models\PlatformAdministrator;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemHealthDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_system_health_dashboard_renders_summary(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create([
            'team_id' => $team->id,
            'email' => 'system-owner@example.test',
        ]);
        $user->markEmailAsVerified();
        PlatformAdministrator::query()->create([
            'user_id' => $user->id,
            'role' => PlatformAdministrator::ROLE_SUPER_ADMIN,
        ]);

        \DB::table('fault_logs')->insert([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'error_code' => 'E_TEST',
            'severity' => 'critical',
            'message' => 'Critical system fault',
            'stack_trace' => 'trace',
            'file_name' => 'HealthTest.php',
            'file_path' => '/app/HealthTest.php',
            'line_number' => 22,
            'class_name' => 'HealthTest',
            'method_name' => 'run',
            'root_cause' => 'testing',
            'recommended_fix' => 'fix it',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('failed_jobs')->insert([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['test' => 'payload']),
            'exception' => 'TestException',
            'failed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('admin.system_health.index'))
            ->assertStatus(200)
            ->assertSee('System Health')
            ->assertSee('Critical')
            ->assertSee('Failed Jobs');

        $this->get(route('queue.status.page'))->assertOk();
        $this->getJson(route('queue.status.index'))->assertOk();
    }

    public function test_tenant_owner_cannot_access_platform_monitoring_or_system_audit_logs(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);
        $roles = app(CreateDefaultRolesAction::class)->execute($team);
        $user->roles()->attach($roles['Owner']);
        $user->markEmailAsVerified();

        $this->actingAs($user)
            ->get(route('admin.system_health.index'))
            ->assertForbidden();

        $this->get(route('queue.status.page'))->assertForbidden();
        $this->getJson(route('queue.status.index'))->assertForbidden();
        $this->get(route('audit.logs.page'))->assertForbidden();
        $this->getJson(route('audit.logs.list'))->assertForbidden();
    }

    public function test_unverified_database_platform_administrator_cannot_access_platform_monitoring(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'unverified-system-owner@example.test']);
        PlatformAdministrator::query()->create([
            'user_id' => $user->id,
            'role' => PlatformAdministrator::ROLE_SYSTEM_OWNER,
        ]);

        $this->actingAs($user)
            ->get(route('admin.system_health.index'))
            ->assertRedirect(route('verification.notice'));
    }
}
