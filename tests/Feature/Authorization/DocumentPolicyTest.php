<?php

namespace Tests\Feature\Authorization;

use App\Models\Document;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\AuthorizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DocumentPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => AuthorizationSeeder::class]);
    }

    public function test_owner_can_view_document(): void
    {
        $team = Team::factory()->create();
        $permission = \App\Models\Permission::firstOrCreate([
            'slug' => 'view_documents',
        ], [
            'name' => 'View documents',
        ]);

        $ownerRole = Role::create([
            'team_id' => $team->id,
            'name' => 'Owner',
            'slug' => 'owner',
        ]);
        $ownerRole->permissions()->attach($permission);

        $owner = User::factory()->create(['team_id' => $team->id]);
        $owner->roles()->attach($ownerRole);

        $document = Document::factory()->create(['team_id' => $team->id]);

        $this->assertTrue($owner->can('view', $document));
    }

    public function test_viewer_cannot_delete_document(): void
    {
        $team = Team::factory()->create();
        $viewerRole = Role::create([
            'team_id' => $team->id,
            'name' => 'Viewer',
            'slug' => 'viewer',
        ]);

        $permission = \App\Models\Permission::firstOrCreate([
            'slug' => 'view_documents',
        ], [
            'name' => 'View documents',
        ]);
        $viewerRole->permissions()->attach($permission);

        $viewer = User::factory()->create(['team_id' => $team->id]);
        $viewer->roles()->attach($viewerRole);

        $document = Document::factory()->create(['team_id' => $team->id]);

        $this->assertFalse($viewer->can('delete', $document));
    }

    public function test_user_cannot_view_document_in_other_team(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();

        $role = Role::create([
            'team_id' => $teamA->id,
            'name' => 'Viewer',
            'slug' => 'viewer',
        ]);

        $permission = \App\Models\Permission::firstOrCreate([
            'slug' => 'view_documents',
        ], [
            'name' => 'View documents',
        ]);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['team_id' => $teamA->id]);
        $user->roles()->attach($role);

        $document = Document::factory()->create(['team_id' => $teamB->id]);

        $this->assertFalse($user->can('view', $document));
    }
}
