<?php

namespace Tests\Feature;

use App\Jobs\ProcessDocumentJob;
use App\Jobs\ScanUploadedFileJob;
use App\Contracts\MalwareScanner;
use App\Models\Document;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class DocumentScanPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_scanner_fails_closed_and_does_not_dispatch_extraction(): void
    {
        Storage::fake('local');
        Bus::fake();
        config(['services.clamav.enabled' => false]);

        [$document, $path] = $this->createStoredDocument();

        (new ScanUploadedFileJob($path, $document->id, $document->team_id))
            ->handle(app(MalwareScanner::class));

        $this->assertSame(Document::STATUS_SCAN_FAILED, $document->fresh()->status);
        $this->assertSame('unscanned', json_decode(Storage::disk('local')->get($path.'.scan.json'), true)['status']);
        Bus::assertNotDispatched(ProcessDocumentJob::class);
    }

    public function test_clean_scan_queues_extraction_with_the_same_team_and_document_ids(): void
    {
        Storage::fake('local');
        Bus::fake();
        config(['services.clamav.enabled' => true]);
        [$document, $path] = $this->createStoredDocument();

        $scanner = Mockery::mock(MalwareScanner::class);
        $scanner->shouldReceive('scan')
            ->once()
            ->with(Storage::disk('local')->path($path))
            ->andReturn('clean');

        (new ScanUploadedFileJob($path, $document->id, $document->team_id))->handle($scanner);

        $this->assertSame(Document::STATUS_QUEUED, $document->fresh()->status);
        Bus::assertDispatched(ProcessDocumentJob::class, function (ProcessDocumentJob $job) use ($document): bool {
            return $job->documentId === $document->id && $job->teamId === $document->team_id;
        });
    }

    public function test_infected_scan_quarantines_document_without_dispatching_extraction(): void
    {
        Storage::fake('local');
        Bus::fake();
        config(['services.clamav.enabled' => true]);
        [$document, $path] = $this->createStoredDocument();

        $scanner = Mockery::mock(MalwareScanner::class);
        $scanner->shouldReceive('scan')->once()->andReturn('infected');

        (new ScanUploadedFileJob($path, $document->id, $document->team_id))->handle($scanner);

        $this->assertSame(Document::STATUS_QUARANTINED, $document->fresh()->status);
        Bus::assertNotDispatched(ProcessDocumentJob::class);
    }

    public function test_scanner_errors_are_reported_and_block_extraction(): void
    {
        Storage::fake('local');
        Bus::fake();
        config(['services.clamav.enabled' => true]);
        [$document, $path] = $this->createStoredDocument();

        $scanner = Mockery::mock(MalwareScanner::class);
        $scanner->shouldReceive('scan')
            ->once()
            ->andThrow(new RuntimeException('scanner unavailable'));

        try {
            (new ScanUploadedFileJob($path, $document->id, $document->team_id))->handle($scanner);
            $this->fail('A scanner failure must be surfaced for queue retry.');
        } catch (RuntimeException $exception) {
            $this->assertSame('scanner unavailable', $exception->getMessage());
        }

        $this->assertSame(Document::STATUS_SCAN_FAILED, $document->fresh()->status);
        Bus::assertNotDispatched(ProcessDocumentJob::class);
    }

    public function test_unrecognized_scanner_result_fails_closed(): void
    {
        Storage::fake('local');
        Bus::fake();
        config(['services.clamav.enabled' => true]);
        [$document, $path] = $this->createStoredDocument();

        $scanner = Mockery::mock(MalwareScanner::class);
        $scanner->shouldReceive('scan')->once()->andReturn('unknown');

        try {
            (new ScanUploadedFileJob($path, $document->id, $document->team_id))->handle($scanner);
            $this->fail('An unrecognized scanner result must not be accepted as clean.');
        } catch (RuntimeException $exception) {
            $this->assertSame('The malware scanner returned an unsupported result.', $exception->getMessage());
        }

        $this->assertSame(Document::STATUS_SCAN_FAILED, $document->fresh()->status);
        $this->assertSame('scan_failed', json_decode(Storage::disk('local')->get($path.'.scan.json'), true)['status']);
        Bus::assertNotDispatched(ProcessDocumentJob::class);
    }

    public function test_simulated_extraction_is_blocked_outside_local_and_testing(): void
    {
        $this->app->detectEnvironment(static fn (): string => 'production');
        config(['services.document_extraction.driver' => 'simulated']);
        $document = Document::factory()->create(['status' => Document::STATUS_QUEUED]);

        app()->call([new ProcessDocumentJob($document->id, $document->team_id), 'handle']);

        $this->assertSame(Document::STATUS_FAILED, $document->fresh()->status);
        $this->assertDatabaseMissing('transactions', ['document_id' => $document->id]);
    }

    /**
     * @return array{Document, string}
     */
    private function createStoredDocument(): array
    {
        $team = Team::factory()->create();
        $path = "tenant-{$team->id}/documents/synthetic.pdf";
        Storage::disk('local')->put($path, 'synthetic file bytes');
        $document = Document::factory()->create([
            'team_id' => $team->id,
            'file_path' => $path,
            'status' => Document::STATUS_SCANNING,
        ]);

        return [$document, $path];
    }
}
