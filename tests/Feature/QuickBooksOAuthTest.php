<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QuickBooksOAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_start_quickbooks_oauth_flow(): void
    {
        $team = Team::factory()->create();
        $permission = Permission::create(['name' => 'Manage integrations', 'slug' => Permission::PERMISSION_MANAGE_INTEGRATIONS]);
        $role = Role::create(['team_id' => $team->id, 'name' => 'Integrator', 'slug' => 'integrator']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['team_id' => $team->id]);
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->get(route('integrations.quickbooks.connect'));

        $response->assertRedirect();

        $location = $response->headers->get('Location');
        $this->assertNotNull($location);
        $this->assertStringContainsString('appcenter.intuit.com/connect/oauth2', $location);
        $this->assertStringContainsString('client_id=', $location);
        $this->assertStringContainsString('redirect_uri=', $location);
        $this->assertMatchesRegularExpression('/state=[^&]+/', $location);
    }

    public function test_quickbooks_callback_persists_tokens_for_the_active_team(): void
    {
        $team = Team::factory()->create();
        $permission = Permission::create(['name' => 'Manage integrations', 'slug' => Permission::PERMISSION_MANAGE_INTEGRATIONS]);
        $role = Role::create(['team_id' => $team->id, 'name' => 'Integrator', 'slug' => 'integrator']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['team_id' => $team->id]);
        $user->roles()->attach($role);

        $state = 'quickbooks-state-123';
        session(['quickbooks_oauth_state' => $state]);

        Http::fake([
            'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer' => Http::response([
                'access_token' => 'test-access-token',
                'refresh_token' => 'test-refresh-token',
                'token_type' => 'bearer',
                'expires_in' => 3600,
            ], 200),
        ]);

        $this->actingAs($user)
            ->get(route('integrations.quickbooks.callback', ['code' => 'auth-code-123', 'state' => $state]))
            ->assertRedirect(route('integrations.page'));

        $integration = \App\Models\Integration::query()->where('team_id', $team->id)->where('provider', 'quickbooks')->first();

        $this->assertNotNull($integration);
        $this->assertSame('test-access-token', $integration->credentials['access_token']);
        $this->assertSame('test-refresh-token', $integration->credentials['refresh_token']);
    }
}
