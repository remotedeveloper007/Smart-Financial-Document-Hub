<?php

namespace Tests\Feature;

use App\Actions\Documents\StoreReceiptsAction;
use App\Models\Document;
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

    public function test_receipt_storage_persists_and_updates_records_with_external_ids(): void
    {
        $team = Team::factory()->create();
        $document = Document::factory()->create(['team_id' => $team->id]);
        $action = app(StoreReceiptsAction::class);

        $action->execute($team->id, $document->id, [[
            'external_id' => 'receipt-1',
            'vendor_name' => 'First Vendor',
            'receipt_number' => 'R-100',
            'issue_date' => '2026-11-05',
            'total_amount' => 100,
            'currency' => 'INR',
            'status' => 'paid',
            'reference' => 'first import',
        ]]);
        $action->execute($team->id, $document->id, [[
            'external_id' => 'receipt-1',
            'vendor_name' => 'Updated Vendor',
            'receipt_number' => 'R-100',
            'issue_date' => '2026-11-05',
            'total_amount' => 125,
            'currency' => 'INR',
            'status' => 'paid',
            'reference' => 'retry',
        ]]);

        $this->assertDatabaseCount('receipts', 1);
        $this->assertDatabaseHas('receipts', [
            'team_id' => $team->id,
            'document_id' => $document->id,
            'external_id' => 'receipt-1',
            'vendor_name' => 'Updated Vendor',
            'total_amount' => 125,
            'reference' => 'retry',
        ]);
    }

    public function test_receipt_storage_accepts_missing_external_ids(): void
    {
        $team = Team::factory()->create();
        $document = Document::factory()->create(['team_id' => $team->id]);

        app(StoreReceiptsAction::class)->execute($team->id, $document->id, [
            ['vendor_name' => 'Vendor One', 'receipt_number' => 'R-101'],
            ['vendor_name' => 'Vendor Two', 'receipt_number' => 'R-102'],
        ]);

        $this->assertDatabaseCount('receipts', 2);
        $this->assertDatabaseHas('receipts', [
            'team_id' => $team->id,
            'document_id' => $document->id,
            'external_id' => null,
            'vendor_name' => 'Vendor One',
        ]);
    }
}
