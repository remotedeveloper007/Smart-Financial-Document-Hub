<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AdminCachingTest extends TestCase
{
    public function test_fault_logs_index_uses_cache()
    {
        $this->withoutMiddleware();

        $user = User::factory()->create();
        $user->markEmailAsVerified();

        Gate::shouldReceive('allows')->andReturn(true);
        Gate::shouldReceive('authorize')->andReturn(true);

        // Expect cache to be used
        Cache::shouldReceive('remember')->once()->andReturn([
            'data' => [
                ['id' => 1, 'message' => 'Cached fault']
            ]
        ]);

        $this->actingAs($user)
            ->get(route('admin.fault_logs.index'))
            ->assertStatus(200)
            ->assertSee('Cached fault');
    }

    public function test_cache_is_flushed_on_fault_delete()
    {
        $this->withoutMiddleware();

        $user = User::factory()->create();
        $user->markEmailAsVerified();

        Gate::shouldReceive('allows')->andReturn(true);
        Gate::shouldReceive('authorize')->andReturn(true);

        // Insert a fault log to delete
        $id = \DB::table('fault_logs')->insertGetId([
            'team_id' => 1,
            'user_id' => $user->id,
            'error_code' => 'E_TEST',
            'severity' => 'high',
            'message' => 'To delete',
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

        // Expect tags flush to be called (if available) or fallback forgets
        Cache::shouldReceive('tags')->once()->andReturnSelf();
        Cache::shouldReceive('flush')->once();

        $this->actingAs($user)
            ->delete(route('admin.fault_logs.destroy', ['id' => $id]))
            ->assertStatus(302);
    }
}
