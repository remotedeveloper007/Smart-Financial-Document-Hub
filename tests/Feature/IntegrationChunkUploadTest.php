<?php

namespace Tests\Feature;

use App\Jobs\TallyImportFromFileJob;
use App\Models\Integration;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Contracts\MalwareScanner;
use Mockery;
use Tests\TestCase;

class IntegrationChunkUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_chunked_upload_and_finalize_dispatches_import_job(): void
    {
        Storage::fake('local');
        Bus::fake();
        config(['services.clamav.enabled' => true]);
        $scanner = Mockery::mock(MalwareScanner::class);
        $scanner->shouldReceive('scan')->andReturn('clean');
        $this->app->instance(MalwareScanner::class, $scanner);

        $team = Team::factory()->create();
        $permission = Permission::create(['name' => 'Manage integrations', 'slug' => Permission::PERMISSION_MANAGE_INTEGRATIONS]);
        $role = Role::create(['team_id' => $team->id, 'name' => 'Integrator', 'slug' => 'integrator']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['team_id' => $team->id]);
        $user->roles()->attach($role);

        $integration = Integration::factory()->create(['team_id' => $team->id]);

        $this->actingAs($user);

        $csv = "ref,amount\nT1,100\nT2,200\n";
        $checksum = md5($csv);

        $this->postJson(route('integrations.upload.session.create', ['integration' => $integration->id]), [
            'filename' => '..\\outside.csv',
            'checksum' => $checksum,
            'size' => strlen($csv),
        ])->assertUnprocessable()->assertJsonValidationErrors('filename');

        $res = $this->postJson(route('integrations.upload.session.create', ['integration' => $integration->id]), [
            'filename' => 'transactions.csv',
            'checksum' => $checksum,
            'size' => strlen($csv),
        ]);

        $res->assertOk();
        $sessionId = $res->json('session_id');
        $this->assertNotEmpty($sessionId);

        // split into two chunks
        $chunk1 = substr($csv, 0, (int) ceil(strlen($csv) / 2));
        $chunk2 = substr($csv, (int) ceil(strlen($csv) / 2));

        $this->putJson(route('integrations.upload.chunk', ['integration' => $integration->id]), [
            'session_id' => $sessionId,
            'index' => 1,
            'chunk' => base64_encode($chunk1),
        ])->assertOk();

        $this->putJson(route('integrations.upload.chunk', ['integration' => $integration->id]), [
            'session_id' => $sessionId,
            'index' => 2,
            'chunk' => base64_encode($chunk2),
        ])->assertOk();

        $final = $this->postJson(route('integrations.upload.finalize', ['integration' => $integration->id]), [
            'session_id' => $sessionId,
        ]);

        $final->assertOk();
        $body = $final->json();
        $this->assertArrayHasKey('assembled_csv', $body);
        $this->assertStringContainsString('ref,amount', $body['assembled_csv']);

        $this->postJson(route('integrations.import', ['integration' => $integration->id]), [
            'assembled_path' => $body['assembled_path'],
        ])->assertAccepted();

        Bus::assertDispatched(TallyImportFromFileJob::class);
    }

    public function test_file_import_requires_clean_scan_metadata_for_same_upload_session(): void
    {
        Storage::fake('local');
        Bus::fake();

        $team = Team::factory()->create();
        $permission = Permission::create(['name' => 'Manage integrations', 'slug' => Permission::PERMISSION_MANAGE_INTEGRATIONS]);
        $role = Role::create(['team_id' => $team->id, 'name' => 'Integrator', 'slug' => 'integrator']);
        $role->permissions()->attach($permission);
        $user = User::factory()->create(['team_id' => $team->id]);
        $user->roles()->attach($role);
        $integration = Integration::factory()->create(['team_id' => $team->id]);

        $sessionId = (string) Str::uuid();
        $path = "tenant-{$team->id}/uploads/assembled/{$sessionId}_transactions.csv";
        $contents = "ref,amount\nT1,100\n";
        Storage::disk('local')->put($path, $contents);
        Storage::disk('local')->put("tenant-{$team->id}/uploads/sessions/{$sessionId}.json", json_encode([
            'filename' => 'transactions.csv',
            'checksum' => hash('sha256', $contents),
            'size' => strlen($contents),
            'integration_id' => $integration->id,
            'team_id' => $team->id,
        ]));
        Storage::disk('local')->put($path.'.scan.json', json_encode([
            'path' => $path,
            'status' => 'scan_failed',
            'scanner' => 'clamav',
        ]));

        $this->actingAs($user)
            ->postJson(route('integrations.import', ['integration' => $integration->id]), [
                'assembled_path' => $path,
            ])
            ->assertStatus(409)
            ->assertJson(['error' => 'scan_not_clean']);

        Bus::assertNotDispatched(TallyImportFromFileJob::class);
    }
}
