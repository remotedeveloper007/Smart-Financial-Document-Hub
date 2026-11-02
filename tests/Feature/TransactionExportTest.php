<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_route_requires_permission(): void
    {
        $team = Team::factory()->create();
        $user = $this->createUserWithPermission($team, Permission::PERMISSION_VIEW_TRANSACTIONS);

        $response = $this->actingAs($user)->get(route('transactions.export'));

        $response->assertForbidden();
    }

    public function test_export_downloads_csv_for_authorized_user(): void
    {
        $team = Team::factory()->create();
        $user = $this->createUserWithPermission($team, Permission::PERMISSION_EXPORT_TRANSACTIONS);

        Transaction::factory()->create([
            'team_id' => $team->id,
            'description' => 'Exported transaction',
            'amount' => 4500.00,
            'transaction_date' => '2026-07-21',
            'type' => 'income',
            'reference' => 'EXP-001',
        ]);

        $response = $this->actingAs($user)->get(route('transactions.export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=utf-8');
        $response->assertSee('Date,Description,Amount,Type,Reference');
        $response->assertSee('Exported transaction');
    }

    private function createUserWithPermission(Team $team, string $permissionSlug): User
    {
        $permission = Permission::create(['name' => ucfirst(str_replace('_', ' ', $permissionSlug)), 'slug' => $permissionSlug]);

        $role = Role::create(['team_id' => $team->id, 'name' => 'Exporter', 'slug' => 'exporter']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['team_id' => $team->id]);
        $user->roles()->attach($role);

        return $user;
    }
}
