<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class QueueStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_admin_can_view_queue_status_page(): void
    {
        $team = Team::factory()->create();
        $user = $this->createAdmin($team);

        $response = $this->actingAs($user)->get('/queue/status');

        $response->assertOk();
    }

    public function test_queue_status_endpoint_returns_job_metrics(): void
    {
        $team = Team::factory()->create();
        $user = $this->createAdmin($team);

        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => json_encode(['job' => 'test']),
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);

        DB::table('failed_jobs')->insert([
            'uuid' => Str::uuid()->toString(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['job' => 'failed']),
            'exception' => 'Exception message',
            'failed_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson(route('queue.status.index'));

        $response->assertOk()
            ->assertJson([
                'pending_jobs' => 1,
                'failed_jobs' => 1,
            ]);
    }

    private function createAdmin(Team $team): User
    {
        $role = Role::create(['team_id' => $team->id, 'name' => 'Admin', 'slug' => 'admin']);
        $user = User::factory()->create(['team_id' => $team->id]);
        $user->roles()->attach($role);

        return $user;
    }
}
