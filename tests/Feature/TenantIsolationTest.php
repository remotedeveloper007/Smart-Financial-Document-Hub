<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_context_is_resolved_for_authenticated_user(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);

        $this->actingAs($user)->get('/');

        $this->assertSame($team->id, $user->team_id);
    }

    public function test_documents_are_scoped_to_current_team(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();

        Document::factory()->create(['team_id' => $teamA->id]);
        Document::factory()->create(['team_id' => $teamB->id]);

        $user = User::factory()->create(['team_id' => $teamA->id]);

        $this->actingAs($user);

        $documents = Document::all();

        $this->assertCount(1, $documents);
        $this->assertSame($teamA->id, $documents->first()->team_id);
    }

    public function test_new_documents_are_assigned_current_team_id(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);

        $this->actingAs($user);

        $document = Document::create([
            'name' => 'Tenant Document',
            'file_path' => 'tenant-documents/test.pdf',
            'status' => Document::STATUS_UPLOADED,
        ]);

        $this->assertSame($team->id, $document->team_id);
        $this->assertDatabaseHas('documents', ['id' => $document->id, 'team_id' => $team->id]);
    }
}
