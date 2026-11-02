<?php

namespace Tests\Feature\Authorization;

use App\Models\Invoice;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_view_permission_can_view_invoice(): void
    {
        $team = Team::factory()->create();
        $permission = Permission::create(['name' => 'View invoices', 'slug' => Permission::PERMISSION_VIEW_INVOICES]);

        $role = Role::create(['team_id' => $team->id, 'name' => 'Invoice Viewer', 'slug' => 'invoice-viewer']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['team_id' => $team->id]);
        $user->roles()->attach($role);

        // Ensure TeamContext resolves to the test team for permission checks
        $teamContext = new \App\Services\Tenancy\TeamContext();
        $teamContext->setTeam($team);
        $this->app->instance(\App\Services\Tenancy\TeamContext::class, $teamContext);

        $invoice = Invoice::factory()->create(['team_id' => $team->id]);

        $this->assertTrue($user->can('view', $invoice));
    }

    public function test_user_without_permission_cannot_view_invoice(): void
    {
        $team = Team::factory()->create();
        $role = Role::create(['team_id' => $team->id, 'name' => 'Employee', 'slug' => 'employee']);

        $user = User::factory()->create(['team_id' => $team->id]);
        $user->roles()->attach($role);
        $teamContext = new \App\Services\Tenancy\TeamContext();
        $teamContext->setTeam($team);
        $this->app->instance(\App\Services\Tenancy\TeamContext::class, $teamContext);
        $invoice = Invoice::factory()->create(['team_id' => $team->id]);

        $this->assertFalse($user->can('view', $invoice));
    }

    public function test_user_cannot_view_invoice_from_different_team(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();

        $permission = Permission::create(['name' => 'View invoices', 'slug' => Permission::PERMISSION_VIEW_INVOICES]);
        $role = Role::create(['team_id' => $teamA->id, 'name' => 'Invoice Viewer', 'slug' => 'invoice-viewer']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['team_id' => $teamA->id]);
        $user->roles()->attach($role);

        $invoice = Invoice::factory()->create(['team_id' => $teamB->id]);

        $this->assertFalse($user->can('view', $invoice));
    }
}
