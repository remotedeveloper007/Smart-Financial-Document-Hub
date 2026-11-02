<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class XeroOAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_start_xero_oauth_flow(): void
    {
        $team = Team::factory()->create();
        $permission = Permission::create(['name' => 'Manage integrations', 'slug' => Permission::PERMISSION_MANAGE_INTEGRATIONS]);
        $role = Role::create(['team_id' => $team->id, 'name' => 'Integrator', 'slug' => 'integrator']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['team_id' => $team->id]);
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->get(route('integrations.xero.connect'));

        $response->assertRedirect();

        $location = $response->headers->get('Location');
        $this->assertNotNull($location);
        $this->assertStringContainsString('login.xero.com', $location);
        $this->assertStringContainsString('client_id=', $location);
        $this->assertStringContainsString('redirect_uri=', $location);
        $this->assertMatchesRegularExpression('/state=[^&]+/', $location);
    }

    public function test_xero_callback_persists_tokens_for_the_active_team(): void
    {
        $team = Team::factory()->create();
        $permission = Permission::create(['name' => 'Manage integrations', 'slug' => Permission::PERMISSION_MANAGE_INTEGRATIONS]);
        $role = Role::create(['team_id' => $team->id, 'name' => 'Integrator', 'slug' => 'integrator']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['team_id' => $team->id]);
        $user->roles()->attach($role);

        $state = 'xero-state-123';
        session(['xero_oauth_state' => $state]);

        Http::fake([
            'https://identity.xero.com/connect/token' => Http::response([
                'access_token' => 'xero-access-token',
                'refresh_token' => 'xero-refresh-token',
                'token_type' => 'Bearer',
                'expires_in' => 1800,
            ], 200),
        ]);

        $this->actingAs($user)
            ->get(route('integrations.xero.callback', ['code' => 'xero-auth-code-123', 'state' => $state]))
            ->assertRedirect(route('integrations.page'));

        $integration = \App\Models\Integration::query()->where('team_id', $team->id)->where('provider', 'xero')->first();

        $this->assertNotNull($integration);
        $this->assertSame('xero-access-token', $integration->credentials['access_token']);
        $this->assertSame('xero-refresh-token', $integration->credentials['refresh_token']);
    }
}
