<?php

namespace Tests\Feature\Authorization;

use App\Models\Permission;
use App\Models\Receipt;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_view_permission_can_view_receipt(): void
    {
        $team = Team::factory()->create();
        $permission = Permission::create(['name' => 'View receipts', 'slug' => Permission::PERMISSION_VIEW_RECEIPTS]);

        $role = Role::create(['team_id' => $team->id, 'name' => 'Receipt Viewer', 'slug' => 'receipt-viewer']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['team_id' => $team->id]);
        $user->roles()->attach($role);

        $teamContext = new \App\Services\Tenancy\TeamContext();
        $teamContext->setTeam($team);
        $this->app->instance(\App\Services\Tenancy\TeamContext::class, $teamContext);

        $receipt = Receipt::factory()->create(['team_id' => $team->id]);

        $this->assertTrue($user->can('view', $receipt));
    }

    public function test_user_without_permission_cannot_view_receipt(): void
    {
        $team = Team::factory()->create();
        $role = Role::create(['team_id' => $team->id, 'name' => 'Employee', 'slug' => 'employee']);

        $user = User::factory()->create(['team_id' => $team->id]);
        $user->roles()->attach($role);
        $teamContext = new \App\Services\Tenancy\TeamContext();
        $teamContext->setTeam($team);
        $this->app->instance(\App\Services\Tenancy\TeamContext::class, $teamContext);
        $receipt = Receipt::factory()->create(['team_id' => $team->id]);

        $this->assertFalse($user->can('view', $receipt));
    }

    public function test_user_cannot_view_receipt_from_different_team(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();

        $permission = Permission::create(['name' => 'View receipts', 'slug' => Permission::PERMISSION_VIEW_RECEIPTS]);
        $role = Role::create(['team_id' => $teamA->id, 'name' => 'Receipt Viewer', 'slug' => 'receipt-viewer']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['team_id' => $teamA->id]);
        $user->roles()->attach($role);

        $receipt = Receipt::factory()->create(['team_id' => $teamB->id]);

        $this->assertFalse($user->can('view', $receipt));
    }
}
