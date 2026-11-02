<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ZohoBooksOAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_start_zoho_books_oauth_flow(): void
    {
        $team = Team::factory()->create();
        $permission = Permission::create(['name' => 'Manage integrations', 'slug' => Permission::PERMISSION_MANAGE_INTEGRATIONS]);
        $role = Role::create(['team_id' => $team->id, 'name' => 'Integrator', 'slug' => 'integrator']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['team_id' => $team->id]);
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->get(route('integrations.zoho.connect'));

        $response->assertRedirect();

        $location = $response->headers->get('Location');
        $this->assertNotNull($location);
        $this->assertStringContainsString('accounts.zoho.com', $location);
        $this->assertStringContainsString('client_id=', $location);
        $this->assertStringContainsString('redirect_uri=', $location);
        $this->assertMatchesRegularExpression('/state=[^&]+/', $location);
    }

    public function test_zoho_books_callback_persists_tokens_for_the_active_team(): void
    {
        $team = Team::factory()->create();
        $permission = Permission::create(['name' => 'Manage integrations', 'slug' => Permission::PERMISSION_MANAGE_INTEGRATIONS]);
        $role = Role::create(['team_id' => $team->id, 'name' => 'Integrator', 'slug' => 'integrator']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['team_id' => $team->id]);
        $user->roles()->attach($role);

        $state = 'zoho-state-123';
        session(['zoho_oauth_state' => $state]);

        Http::fake([
            'https://accounts.zoho.com/oauth/v2/token' => Http::response([
                'access_token' => 'zoho-access-token',
                'refresh_token' => 'zoho-refresh-token',
                'api_domain' => 'https://www.zohoapis.com',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ], 200),
        ]);

        $this->actingAs($user)
            ->get(route('integrations.zoho.callback', ['code' => 'zoho-auth-code-123', 'state' => $state]))
            ->assertRedirect(route('integrations.page'));

        $integration = \App\Models\Integration::query()->where('team_id', $team->id)->where('provider', 'zoho')->first();

        $this->assertNotNull($integration);
        $this->assertSame('zoho-access-token', $integration->credentials['access_token']);
        $this->assertSame('zoho-refresh-token', $integration->credentials['refresh_token']);
    }
}
