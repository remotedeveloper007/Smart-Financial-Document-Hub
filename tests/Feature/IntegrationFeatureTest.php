<?php

namespace Tests\Feature;

use App\Models\Integration;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegrationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_manage_permission_can_view_integrations_page(): void
    {
        $team = Team::factory()->create();
        $permission = Permission::create(['name' => 'Manage integrations', 'slug' => Permission::PERMISSION_MANAGE_INTEGRATIONS]);
        $role = Role::create(['team_id' => $team->id, 'name' => 'Integrator', 'slug' => 'integrator']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['team_id' => $team->id]);
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->get(route('integrations.page'));

        $response->assertOk();
        $response->assertSee('Integrations');
    }

    public function test_integrations_list_scoped_to_team(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();

        Integration::factory()->create(['team_id' => $teamA->id]);
        Integration::factory()->create(['team_id' => $teamB->id]);

        $permission = Permission::create(['name' => 'Manage integrations', 'slug' => Permission::PERMISSION_MANAGE_INTEGRATIONS]);
        $role = Role::create(['team_id' => $teamA->id, 'name' => 'Integrator', 'slug' => 'integrator']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['team_id' => $teamA->id]);
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->getJson(route('integrations.list'));

        $response->assertOk();
        $response->assertJsonCount(1, 'integrations.data');
    }

    public function test_user_without_permission_cannot_access_integrations(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);

        $response = $this->actingAs($user)->get(route('integrations.page'));

        $response->assertForbidden();
    }

    public function test_user_can_create_update_and_delete_team_integration_settings(): void
    {
        $team = Team::factory()->create();
        $permission = Permission::create(['name' => 'Manage integrations', 'slug' => Permission::PERMISSION_MANAGE_INTEGRATIONS]);
        $role = Role::create(['team_id' => $team->id, 'name' => 'Integrator', 'slug' => 'integrator']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['team_id' => $team->id]);
        $user->roles()->attach($role);

        $this->actingAs($user);

        $createResponse = $this->postJson(route('integrations.store'), [
            'provider' => 'quickbooks',
            'external_id' => 'acct-001',
            'status' => 'enabled',
            'credentials' => ['access_token' => 'secret-token', 'url' => 'https://api.qb.example', 'security_code' => 'sec-123', 'webhook' => 'https://hooks.example.com/qb'],
        ]);

        $createResponse->assertCreated();
        $integrationId = $createResponse->json('integration.id');
        $createResponse->assertJsonMissingPath('integration.credentials');
        $this->assertDatabaseHas('integrations', ['team_id' => $team->id, 'provider' => 'quickbooks', 'external_id' => 'acct-001']);
        $integration = Integration::findOrFail($integrationId);
        $this->assertSame('secret-token', $integration->credentials['access_token']);
        $this->assertStringNotContainsString('secret-token', (string) $integration->getRawOriginal('credentials'));

        $updateResponse = $this->putJson(route('integrations.update', ['integration' => $integrationId]), [
            'provider' => 'quickbooks',
            'external_id' => 'acct-002',
            'status' => 'disabled',
            'credentials' => ['access_token' => 'new-token'],
        ]);

        $updateResponse->assertOk();
        $updateResponse->assertJsonMissingPath('integration.credentials');
        $this->assertDatabaseHas('integrations', ['id' => $integrationId, 'external_id' => 'acct-002', 'status' => 'disabled']);
        $updatedCredentials = Integration::findOrFail($integrationId)->credentials;
        $this->assertSame('new-token', $updatedCredentials['access_token']);
        $this->assertSame('https://api.qb.example', $updatedCredentials['url']);

        $deleteResponse = $this->deleteJson(route('integrations.destroy', ['integration' => $integrationId]));

        $deleteResponse->assertNoContent();
        $this->assertDatabaseMissing('integrations', ['id' => $integrationId]);
    }
}
