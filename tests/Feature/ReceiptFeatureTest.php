<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Receipt;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_receipt_permission_can_view_receipts_page(): void
    {
        $team = Team::factory()->create();
        $permission = Permission::create(['name' => 'View receipts', 'slug' => Permission::PERMISSION_VIEW_RECEIPTS]);
        $role = Role::create(['team_id' => $team->id, 'name' => 'Receipt Viewer', 'slug' => 'receipt-viewer']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['team_id' => $team->id]);
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->get(route('receipts.page'));

        $response->assertOk();
        $response->assertSee('Receipts');
    }

    public function test_receipts_list_returns_current_team_receipts(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();

        Receipt::factory()->create(['team_id' => $teamA->id]);
        Receipt::factory()->create(['team_id' => $teamB->id]);

        $permission = Permission::create(['name' => 'View receipts', 'slug' => Permission::PERMISSION_VIEW_RECEIPTS]);
        $role = Role::create(['team_id' => $teamA->id, 'name' => 'Receipt Viewer', 'slug' => 'receipt-viewer']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['team_id' => $teamA->id]);
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->getJson(route('receipts.list'));

        $response->assertOk();
        $response->assertJsonCount(1, 'receipts.data');
    }

    public function test_user_without_permission_cannot_access_receipt_routes(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);

        $response = $this->actingAs($user)->get(route('receipts.page'));

        $response->assertForbidden();
    }
}
