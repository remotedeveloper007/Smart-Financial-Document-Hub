<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SystemHealthDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_system_health_dashboard_renders_summary(): void
    {
        $this->withoutMiddleware();

        $user = User::factory()->create();
        $user->markEmailAsVerified();

        Gate::shouldReceive('allows')->andReturn(true);
        Gate::shouldReceive('authorize')->andReturn(true);

        \DB::table('fault_logs')->insert([
            'team_id' => 1,
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
    }
}
