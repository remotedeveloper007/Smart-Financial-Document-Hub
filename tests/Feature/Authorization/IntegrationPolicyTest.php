<?php

namespace Tests\Feature\Authorization;

use App\Models\Integration;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegrationPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_manage_permission_can_view_integration(): void
    {
        $team = Team::factory()->create();
        $permission = Permission::create(['name' => 'Manage integrations', 'slug' => Permission::PERMISSION_MANAGE_INTEGRATIONS]);

        $role = Role::create(['team_id' => $team->id, 'name' => 'Integrator', 'slug' => 'integrator']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['team_id' => $team->id]);
        $user->roles()->attach($role);

        $teamContext = new \App\Services\Tenancy\TeamContext();
        $teamContext->setTeam($team);
        $this->app->instance(\App\Services\Tenancy\TeamContext::class, $teamContext);

        $integration = Integration::factory()->create(['team_id' => $team->id]);

        $this->assertTrue($user->can('view', $integration));
    }

    public function test_user_without_permission_cannot_view_integration(): void
    {
        $team = Team::factory()->create();
        $role = Role::create(['team_id' => $team->id, 'name' => 'Employee', 'slug' => 'employee']);

        $user = User::factory()->create(['team_id' => $team->id]);
        $user->roles()->attach($role);
        $teamContext = new \App\Services\Tenancy\TeamContext();
        $teamContext->setTeam($team);
        $this->app->instance(\App\Services\Tenancy\TeamContext::class, $teamContext);
        $integration = Integration::factory()->create(['team_id' => $team->id]);

        $this->assertFalse($user->can('view', $integration));
    }

    public function test_user_cannot_view_integration_from_different_team(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();

        $permission = Permission::create(['name' => 'Manage integrations', 'slug' => Permission::PERMISSION_MANAGE_INTEGRATIONS]);
        $role = Role::create(['team_id' => $teamA->id, 'name' => 'Integrator', 'slug' => 'integrator']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['team_id' => $teamA->id]);
        $user->roles()->attach($role);

        $integration = Integration::factory()->create(['team_id' => $teamB->id]);

        $this->assertFalse($user->can('view', $integration));
    }
}
