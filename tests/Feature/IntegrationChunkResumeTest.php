<?php

namespace Tests\Feature;

use App\Models\Integration;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class IntegrationChunkResumeTest extends TestCase
{
    use RefreshDatabase;

    public function test_resume_endpoint_lists_received_chunks_with_checksums(): void
    {
        Storage::fake('local');

        $team = Team::factory()->create();
        $permission = Permission::create(['name' => 'Manage integrations', 'slug' => Permission::PERMISSION_MANAGE_INTEGRATIONS]);
        $role = Role::create(['team_id' => $team->id, 'name' => 'Integrator', 'slug' => 'integrator']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['team_id' => $team->id]);
        $user->roles()->attach($role);

        $integration = Integration::factory()->create(['team_id' => $team->id]);

        $this->actingAs($user);

        $sessionId = (string) Str::uuid();
        Storage::disk('local')->put("tenant-{$team->id}/uploads/sessions/{$sessionId}.json", json_encode([
            'filename' => 'transactions.csv',
            'checksum' => md5('hello'),
            'size' => 5,
            'integration_id' => $integration->id,
            'team_id' => $team->id,
        ]));
        $dir = "tenant-{$team->id}/uploads/tmp/{$sessionId}";
        Storage::disk('local')->put("{$dir}/chunk_1", 'hello');
        Storage::disk('local')->put("{$dir}/chunk_1.meta.json", json_encode(['index' => 1, 'md5' => md5('hello'), 'sha256' => hash('sha256', 'hello')]));

        $res = $this->getJson(route('integrations.upload.resume', ['integration' => $integration->id, 'session_id' => $sessionId]));
        $res->assertOk();
        $body = $res->json();
        $this->assertArrayHasKey('received', $body);
        $this->assertCount(1, $body['received']);
        $this->assertEquals(1, $body['received'][0]['index']);
    }
}
