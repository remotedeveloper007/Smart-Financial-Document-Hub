<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_invoice_permission_can_view_invoices_page(): void
    {
        $team = Team::factory()->create();
        $permission = Permission::create(['name' => 'View invoices', 'slug' => Permission::PERMISSION_VIEW_INVOICES]);
        $role = Role::create(['team_id' => $team->id, 'name' => 'Invoice Viewer', 'slug' => 'invoice-viewer']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['team_id' => $team->id]);
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->get(route('invoices.page'));

        $response->assertOk();
        $response->assertSee('Invoices');
    }

    public function test_invoices_list_returns_current_team_invoices(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();

        Invoice::factory()->create(['team_id' => $teamA->id]);
        Invoice::factory()->create(['team_id' => $teamB->id]);

        $permission = Permission::create(['name' => 'View invoices', 'slug' => Permission::PERMISSION_VIEW_INVOICES]);
        $role = Role::create(['team_id' => $teamA->id, 'name' => 'Invoice Viewer', 'slug' => 'invoice-viewer']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['team_id' => $teamA->id]);
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->getJson(route('invoices.list'));

        $response->assertOk();
        $response->assertJsonCount(1, 'invoices.data');
    }

    public function test_user_without_permission_cannot_access_invoice_routes(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);

        $response = $this->actingAs($user)->get(route('invoices.page'));

        $response->assertForbidden();
    }
}
