<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_search_page(): void
    {
        $team = Team::factory()->create();
        $user = $this->createUserWithPermission($team, Permission::PERMISSION_VIEW_TRANSACTIONS);

        $response = $this->actingAs($user)->get('/search');

        $response->assertOk();
    }

    public function test_search_transactions_returns_matching_results(): void
    {
        $team = Team::factory()->create();
        $user = $this->createUserWithPermission($team, Permission::PERMISSION_VIEW_TRANSACTIONS);

        Transaction::factory()->create([
            'team_id' => $team->id,
            'description' => 'Office supplies purchase',
            'reference' => 'INV-1234',
            'type' => 'expense',
        ]);

        Transaction::factory()->create([
            'team_id' => $team->id,
            'description' => 'Client payment received',
            'reference' => 'PAY-4321',
            'type' => 'income',
        ]);

        $response = $this->actingAs($user)->getJson(route('search.transactions', ['q' => 'client']));

        $response->assertOk();
        $response->assertJsonCount(1, 'transactions');
        $response->assertJsonFragment(['description' => 'Client payment received']);
    }

    public function test_search_only_returns_team_transactions(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();
        $user = $this->createUserWithPermission($teamA, Permission::PERMISSION_VIEW_TRANSACTIONS);

        Transaction::factory()->create([
            'team_id' => $teamA->id,
            'description' => 'Tenant A transaction',
            'reference' => 'A-1',
        ]);

        Transaction::factory()->create([
            'team_id' => $teamB->id,
            'description' => 'Tenant B transaction',
            'reference' => 'B-1',
        ]);

        $response = $this->actingAs($user)->getJson(route('search.transactions', ['q' => 'tenant']));

        $response->assertOk();
        $response->assertJsonCount(1, 'transactions');
        $response->assertJsonFragment(['description' => 'Tenant A transaction']);
    }

    private function createUserWithPermission(Team $team, string $permissionSlug): User
    {
        $permission = Permission::create(['name' => ucfirst(str_replace('_', ' ', $permissionSlug)), 'slug' => $permissionSlug]);

        $role = Role::create(['team_id' => $team->id, 'name' => 'Searcher', 'slug' => 'searcher']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['team_id' => $team->id]);
        $user->roles()->attach($role);

        return $user;
    }
}
