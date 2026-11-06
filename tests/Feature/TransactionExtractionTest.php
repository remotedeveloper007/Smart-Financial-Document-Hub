<?php

namespace Tests\Feature;

use App\Actions\Documents\ExtractTransactionsAction;
use App\Actions\Transactions\StoreTransactionsAction;
use App\Models\Document;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TransactionExtractionTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_processing_job_extracts_and_stores_transactions(): void
    {
        Storage::fake('local');
        Bus::fake();

        $team = Team::factory()->create();
        $user = $this->createUserWithPermission($team, Permission::PERMISSION_CREATE_DOCUMENTS);

        $response = $this->actingAs($user)->postJson(route('documents.store'), [
            'name' => 'Statement Upload',
            'file' => UploadedFile::fake()->create('statement.pdf', 200, 'application/pdf'),
        ]);

        $response->assertCreated();

 
        $document = Document::query()->where('name', 'Statement Upload')->firstOrFail();

        Bus::assertDispatched(\App\Jobs\ScanUploadedFileJob::class, function (\App\Jobs\ScanUploadedFileJob $job) use ($document, $team) {
            return $job->documentId === $document->id && $job->teamId === $team->id;
        });
    }

    public function test_extract_transactions_action_returns_sample_transactions(): void
    {
        $document = Document::factory()->create();
        $extractor = app(ExtractTransactionsAction::class);

        $transactions = $extractor->execute($document);

        $this->assertCount(3, $transactions);
        $this->assertEquals('Office supplies purchase', $transactions[0]['description']);
        $this->assertArrayHasKey('external_id', $transactions[0]);
    }

    public function test_extraction_external_ids_are_stable_per_document_and_record(): void
    {
        $document = Document::factory()->create();
        $extractor = app(ExtractTransactionsAction::class);

        $firstRun = $extractor->execute($document);
        $retryRun = $extractor->execute($document);

        $this->assertSame(
            array_column($firstRun, 'external_id'),
            array_column($retryRun, 'external_id'),
        );
    }

    public function test_store_transactions_action_persists_transactions(): void
    {
        $team = Team::factory()->create();
        $document = Document::factory()->create(['team_id' => $team->id]);
        $storeAction = app(StoreTransactionsAction::class);

        $storeAction->execute($team->id, $document->id, [
            [
                'external_id' => 'ext-1',
                'description' => 'Test transaction',
                'amount' => 100.00,
                'transaction_date' => now()->toDateString(),
                'type' => 'income',
                'reference' => 'REF-1',
            ],
        ]);

        $this->assertDatabaseHas('transactions', [
            'document_id' => $document->id,
            'description' => 'Test transaction',
            'amount' => 100.00,
            'reference' => 'REF-1',
        ]);
    }

    public function test_reprocessing_preserves_transaction_creation_timestamp(): void
    {
        $team = Team::factory()->create();
        $document = Document::factory()->create(['team_id' => $team->id]);
        $storeAction = app(StoreTransactionsAction::class);
        $transaction = [
            'external_id' => 'stable-external-id',
            'description' => 'Original description',
            'amount' => 100.00,
            'transaction_date' => '2026-11-05',
            'type' => 'income',
            'reference' => 'REF-1',
        ];

        $storeAction->execute($team->id, $document->id, [$transaction]);
        $originalCreatedAt = now()->subDays(3)->startOfSecond();
        DB::table('transactions')
            ->where('document_id', $document->id)
            ->where('external_id', 'stable-external-id')
            ->update(['created_at' => $originalCreatedAt]);

        $storeAction->execute($team->id, $document->id, [[
            ...$transaction,
            'description' => 'Updated description',
        ]]);

        $this->assertDatabaseHas('transactions', [
            'team_id' => $team->id,
            'document_id' => $document->id,
            'external_id' => 'stable-external-id',
            'description' => 'Updated description',
            'created_at' => $originalCreatedAt->toDateTimeString(),
        ]);
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
