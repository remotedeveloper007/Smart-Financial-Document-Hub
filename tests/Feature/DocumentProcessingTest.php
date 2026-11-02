<?php

namespace Tests\Feature;

use App\Jobs\ScanUploadedFileJob;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentProcessingTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_dispatches_document_processing_job(): void
    {
        Bus::fake();
        Storage::fake('local');

        $team = Team::factory()->create();
        $user = $this->createUserWithPermission($team, Permission::PERMISSION_CREATE_DOCUMENTS);

        $this->actingAs($user)->postJson(route('documents.store'), [
            'name' => 'Bank Statement',
            'file' => UploadedFile::fake()->create('statement.pdf', 200, 'application/pdf'),
        ])->assertCreated();

        Bus::assertDispatched(ScanUploadedFileJob::class, function (ScanUploadedFileJob $job): bool {
            return $job->teamId !== null && $job->documentId !== null;
        });
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
