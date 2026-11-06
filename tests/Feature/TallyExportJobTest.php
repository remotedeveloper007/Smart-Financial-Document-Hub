<?php

namespace Tests\Feature;

use App\Jobs\TallyExportJob;
use App\Models\Integration;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TallyExportJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_is_saved_under_team_prefix_and_listed_for_owning_integration(): void
    {
        Storage::fake('local');
        Cache::flush();
        Http::fake([
            'https://tally.example.test/api/transactions/export*' => Http::response([
                ['date' => '2026-10-01', 'description' => 'Office supplies', 'amount' => '25.75'],
            ], 200),
        ]);

        [$team, $user, $integration] = $this->createTallyIntegration();
        $job = new TallyExportJob($integration->id, $team->id, ['start_date' => '2026-10-01']);
        $job->handle(app(\App\Services\Integrations\TallyService::class));

        $this->assertSame(
            ['status' => 'completed', 'path' => $this->getExportPath($team->id, $integration->id), 'completed_at' => Cache::get("integration_export_status:team:{$team->id}:integration:{$integration->id}")['completed_at']],
            Cache::get("integration_export_status:team:{$team->id}:integration:{$integration->id}"),
        );

        $path = $this->getExportPath($team->id, $integration->id);
        Storage::disk('local')->assertExists($path);
        $this->assertJsonStringEqualsJsonString(
            json_encode([['date' => '2026-10-01', 'description' => 'Office supplies', 'amount' => '25.75']]),
            Storage::disk('local')->get($path),
        );

        $this->actingAs($user)
            ->getJson(route('integrations.exports.list', ['integration' => $integration->id]))
            ->assertOk()
            ->assertJsonCount(1, 'files')
            ->assertJsonPath('files.0.path', $path);
    }

    public function test_failed_remote_export_is_reported_as_failed_and_throws_for_retry(): void
    {
        Storage::fake('local');
        Cache::flush();
        Http::fake(['https://tally.example.test/api/transactions/export*' => Http::response(['message' => 'busy'], 503)]);

        [$team, , $integration] = $this->createTallyIntegration();
        $job = new TallyExportJob($integration->id, $team->id);

        try {
            $job->handle(app(\App\Services\Integrations\TallyService::class));
            $this->fail('Expected remote export failure to be thrown for the queue retry policy.');
        } catch (\Illuminate\Http\Client\RequestException) {
            $this->assertSame(
                'failed',
                Cache::get("integration_export_status:team:{$team->id}:integration:{$integration->id}")['status'],
            );
        }

        $this->assertSame([], Storage::disk('local')->allFiles("tenant-{$team->id}/exports/tally"));
    }

    public function test_failed_storage_write_is_not_reported_as_completed(): void
    {
        Cache::flush();
        Http::fake([
            'https://tally.example.test/api/transactions/export*' => Http::response([
                ['date' => '2026-10-01', 'description' => 'Office supplies', 'amount' => '25.75'],
            ], 200),
        ]);
        [$team, , $integration] = $this->createTallyIntegration();

        $disk = \Mockery::mock();
        $disk->shouldReceive('put')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('local')->once()->andReturn($disk);

        try {
            (new TallyExportJob($integration->id, $team->id))
                ->handle(app(\App\Services\Integrations\TallyService::class));
            $this->fail('A failed storage write must fail the queued export job.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('The Tally export file could not be saved.', $exception->getMessage());
        }

        $this->assertSame(
            'failed',
            Cache::get("integration_export_status:team:{$team->id}:integration:{$integration->id}")['status'],
        );
    }

    private function createTallyIntegration(): array
    {
        $team = Team::factory()->create();
        $permission = Permission::firstOrCreate(
            ['slug' => Permission::PERMISSION_MANAGE_INTEGRATIONS],
            ['name' => 'Manage integrations'],
        );
        $role = Role::create(['team_id' => $team->id, 'name' => 'Integrator', 'slug' => 'tally-integrator']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['team_id' => $team->id]);
        $user->roles()->attach($role);

        $integration = Integration::factory()->create([
            'team_id' => $team->id,
            'provider' => 'tally',
            'credentials' => [
                'url' => 'https://tally.example.test/api',
                'access_token' => 'team-token',
            ],
        ]);

        return [$team, $user, $integration];
    }

    private function getExportPath(int $teamId, int $integrationId): string
    {
        $paths = Storage::disk('local')->allFiles("tenant-{$teamId}/exports/tally");

        $this->assertCount(1, $paths);
        $this->assertStringContainsString("_integration_{$integrationId}_", basename($paths[0]));

        return $paths[0];
    }
}
