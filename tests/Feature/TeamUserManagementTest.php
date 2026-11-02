<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_manage_users_permission_can_view_team_members_page(): void
    {
        $team = Team::factory()->create();
        $owner = $this->createTeamOwnerWithManageUsersPermission($team);

        $response = $this->actingAs($owner)
            ->get('/team/members');

        $response->assertOk();
    }

    public function test_user_without_manage_users_permission_cannot_list_team_members(): void
    {
        $team = Team::factory()->create();
        $member = User::factory()->create(['team_id' => $team->id]);

        $response = $this->actingAs($member)
            ->getJson(route('team.members.index'));

        $response->assertForbidden();
    }

    public function test_owner_can_add_update_and_remove_team_member(): void
    {
        $team = Team::factory()->create();
        $owner = $this->createTeamOwnerWithManageUsersPermission($team);
        $role = Role::create([
            'team_id' => $team->id,
            'name' => 'Manager',
            'slug' => 'manager',
        ]);

        $response = $this->actingAs($owner)
            ->postJson(route('team.members.store'), [
                'name' => 'New Member',
                'email' => 'new.member@example.com',
                'role_slug' => 'manager',
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('users', [
            'email' => 'new.member@example.com',
            'team_id' => $team->id,
        ]);

        $memberId = $response->json('id');
        $this->assertDatabaseHas('role_user', [
            'user_id' => $memberId,
            'role_id' => $role->id,
        ]);

        $updatedResponse = $this->actingAs($owner)
            ->putJson(route('team.members.update', ['user' => $memberId]), [
                'role_slug' => 'manager',
            ]);

        $updatedResponse->assertOk();
        $updatedRoles = $updatedResponse->json('roles');
        $this->assertSame('manager', $updatedRoles[0]['slug']);

        $deleteResponse = $this->actingAs($owner)
            ->deleteJson(route('team.members.destroy', ['user' => $memberId]));

        $deleteResponse->assertNoContent();
        $this->assertDatabaseMissing('team_user', [
            'team_id' => $team->id,
            'user_id' => $memberId,
        ]);
    }

    private function createTeamOwnerWithManageUsersPermission(Team $team): User
    {
        $permission = Permission::create([
            'name' => 'Manage users',
            'slug' => 'manage_users',
        ]);

        $ownerRole = Role::create([
            'team_id' => $team->id,
            'name' => 'Owner',
            'slug' => 'owner',
        ]);

        $ownerRole->permissions()->attach($permission);

        $owner = User::factory()->create(['team_id' => $team->id]);
        $owner->roles()->attach($ownerRole);

        return $owner;
    }
}
