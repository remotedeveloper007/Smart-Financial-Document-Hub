<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Contract;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_contract_permission_can_view_contracts_page(): void
    {
        $team = Team::factory()->create();
        $permission = Permission::create(['name' => 'View contracts', 'slug' => Permission::PERMISSION_VIEW_CONTRACTS]);
        $role = Role::create(['team_id' => $team->id, 'name' => 'Contract Viewer', 'slug' => 'contract-viewer']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['team_id' => $team->id]);
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->get(route('contracts.page'));

        $response->assertOk();
        $response->assertSee('Contracts');
    }

    public function test_contracts_list_returns_current_team_contracts(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();

        Contract::factory()->create(['team_id' => $teamA->id]);
        Contract::factory()->create(['team_id' => $teamB->id]);

        $permission = Permission::create(['name' => 'View contracts', 'slug' => Permission::PERMISSION_VIEW_CONTRACTS]);
        $role = Role::create(['team_id' => $teamA->id, 'name' => 'Contract Viewer', 'slug' => 'contract-viewer']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['team_id' => $teamA->id]);
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->getJson(route('contracts.list'));

        $response->assertOk();
        $response->assertJsonCount(1, 'contracts.data');
    }

    public function test_user_without_permission_cannot_access_contract_routes(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);

        $response = $this->actingAs($user)->get(route('contracts.page'));

        $response->assertForbidden();
    }
}
