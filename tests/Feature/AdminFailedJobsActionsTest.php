<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AdminFailedJobsActionsTest extends TestCase
{
    public function test_retry_and_delete_actions()
    {
        $this->withoutMiddleware();

        $user = User::factory()->create();
        $user->markEmailAsVerified();

        Gate::shouldReceive('allows')->andReturn(true);
        Gate::shouldReceive('authorize')->andReturn(true);

        $id = \DB::table('failed_jobs')->insertGetId([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['test' => 'payload']),
            'exception' => 'TestException',
            'failed_at' => now(),
        ]);

        // Mock Artisan to avoid running real retry
        Artisan::shouldReceive('call')->once()->with('queue:retry', ['id' => (string) $id])->andReturn(0);

        $this->actingAs($user)
            ->post(route('admin.failed_jobs.retry', ['id' => $id]))
            ->assertStatus(302);

        $this->actingAs($user)
            ->delete(route('admin.failed_jobs.destroy', ['id' => $id]))
            ->assertStatus(302);
    }
}
