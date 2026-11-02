<?php

namespace Tests\Feature;

use App\Models\FaultLog;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLogsTest extends TestCase
{
    public function test_fault_logs_index_accessible_to_admin()
    {
        $this->withoutMiddleware();

        $user = User::factory()->create();
        $user->markEmailAsVerified();

        // allow admin gate in tests
        Gate::shouldReceive('allows')->andReturn(true);
        Gate::shouldReceive('authorize')->andReturn(true);

        // insert a fault log record directly
        \DB::table('fault_logs')->insert([
            'team_id' => 1,
            'user_id' => $user->id,
            'error_code' => 'E_TEST',
            'severity' => 'high',
            'message' => 'Test fault',
            'stack_trace' => 'trace',
            'file_name' => 'Test.php',
            'file_path' => '/app/Test.php',
            'line_number' => 10,
            'class_name' => 'Test',
            'method_name' => 'run',
            'root_cause' => 'testing',
            'recommended_fix' => 'none',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('admin.fault_logs.index'))
            ->assertStatus(200)
            ->assertSee('Fault Logs')
            ->assertSee('Test fault');
    }

    public function test_failed_jobs_index_accessible_to_admin()
    {
        $this->withoutMiddleware();

        $user = User::factory()->create();
        $user->markEmailAsVerified();

        // allow admin gate in tests
        Gate::shouldReceive('allows')->andReturn(true);
        Gate::shouldReceive('authorize')->andReturn(true);

        // seed a failed job record
        \DB::table('failed_jobs')->insert([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['test' => 'payload']),
            'exception' => 'TestException',
            'failed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('admin.failed_jobs.index'))
            ->assertStatus(200)
            ->assertSee('Failed Jobs')
            ->assertSee('TestException');
    }
}
