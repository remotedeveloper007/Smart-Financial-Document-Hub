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

class IntegrationExportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_exports_list_returns_files_for_integration(): void
    {
        Storage::fake('local');

        $team = Team::factory()->create();
        $permission = Permission::create(['name' => 'Manage integrations', 'slug' => Permission::PERMISSION_MANAGE_INTEGRATIONS]);
        $role = Role::create(['team_id' => $team->id, 'name' => 'Integrator', 'slug' => 'integrator']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['team_id' => $team->id]);
        $user->roles()->attach($role);

        $integration = Integration::factory()->create(['team_id' => $team->id]);

        // Historical fixture name omitted the suffix delimiter and job ID, unlike TallyExportJob output.
        // $filename = 'tenant-' . $team->id . '/exports/tally/' . now()->format('Ymd_His') . "_integration_{$integration->id}.json";
        $filename = 'tenant-' . $team->id . '/exports/tally/' . now()->format('Ymd_His_u') . "_integration_{$integration->id}_" . Str::uuid() . '.json';
        $content = json_encode(['sample' => 'data']);
        Storage::disk('local')->put($filename, $content);

        $this->actingAs($user)->getJson(route('integrations.exports.list', ['integration' => $integration->id]))
            ->assertOk()
            ->assertJsonFragment(['name' => basename($filename)]);
    }

    public function test_download_export_returns_file_contents(): void
    {
        Storage::fake('local');

        $team = Team::factory()->create();
        $permission = Permission::create(['name' => 'Manage integrations', 'slug' => Permission::PERMISSION_MANAGE_INTEGRATIONS]);
        $role = Role::create(['team_id' => $team->id, 'name' => 'Integrator', 'slug' => 'integrator']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['team_id' => $team->id]);
        $user->roles()->attach($role);

        $integration = Integration::factory()->create(['team_id' => $team->id]);

        $basename = now()->format('Ymd_His') . "_integration_{$integration->id}.json";
        $path = 'tenant-' . $team->id . '/exports/tally/' . $basename;
        $content = json_encode(['export' => 'ok']);
        Storage::disk('local')->put($path, $content);

        $resp = $this->actingAs($user)->get(route('integrations.exports.download', ['integration' => $integration->id, 'filename' => $basename]));

        $resp->assertOk();
        $disposition = $resp->baseResponse->headers->get('content-disposition');
        $this->assertStringContainsString($basename, (string) $disposition);
    }
}
