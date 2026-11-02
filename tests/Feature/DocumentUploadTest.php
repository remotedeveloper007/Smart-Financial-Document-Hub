<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_create_documents_permission_can_view_upload_page(): void
    {
        $team = Team::factory()->create();
        $user = $this->createUserWithPermission($team, Permission::PERMISSION_CREATE_DOCUMENTS);

        $response = $this->actingAs($user)->get('/documents/upload');

        $response->assertOk();
    }

    public function test_user_with_create_documents_permission_can_upload_pdf(): void
    {
        Bus::fake();
        Storage::fake('local');

        $team = Team::factory()->create();
        $user = $this->createUserWithPermission($team, Permission::PERMISSION_CREATE_DOCUMENTS);

        $response = $this->actingAs($user)->postJson(route('documents.store'), [
            'name' => 'Bank Statement',
            'file' => UploadedFile::fake()->create('statement.pdf', 200, 'application/pdf'),
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('documents', ['name' => 'Bank Statement', 'status' => Document::STATUS_SCANNING]);
        $document = Document::query()->where('name', 'Bank Statement')->firstOrFail();
        $this->assertStringStartsWith('tenant-'.$team->id.'/documents/', $document->file_path);
        $this->assertTrue(Storage::disk('local')->exists($document->file_path));
        Bus::assertDispatched(\App\Jobs\ScanUploadedFileJob::class);
    }

    private function createUserWithPermission(Team $team, string $permissionSlug): User
    {
        $permission = Permission::create(['name' => ucfirst(str_replace('_', ' ', $permissionSlug)), 'slug' => $permissionSlug]);

        $role = Role::create(['team_id' => $team->id, 'name' => 'Uploader', 'slug' => 'uploader']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['team_id' => $team->id]);
        $user->roles()->attach($role);

        return $user;
    }
}
