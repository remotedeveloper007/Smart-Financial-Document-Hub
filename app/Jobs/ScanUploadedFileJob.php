<?php

namespace App\Jobs;

use App\Models\Document;
use App\Contracts\MalwareScanner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final class ScanUploadedFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public int $timeout = 120;

    public function __construct(
        public string $path,
        public ?int $documentId = null,
        public ?int $teamId = null,
    ) {
    }

    public function middleware(): array
    {
        return $this->teamId === null
            ? []
            : [new Middleware\SetTeamContext($this->teamId)];
    }

    public function handle(MalwareScanner $scanner): void
    {
        if (! Storage::disk('local')->exists($this->path)) {
            throw new RuntimeException('The uploaded file is missing from private storage.');
        }

        if (! config('services.clamav.enabled')) {
            $this->recordResult('unscanned');
            $this->updateDocumentStatus(Document::STATUS_SCAN_FAILED);
            Log::warning('Document was not processed because malware scanning is not enabled', [
                'team_id' => $this->teamId,
                'document_id' => $this->documentId,
            ]);

            return;
        }

        try {
            $result = $scanner->scan(Storage::disk('local')->path($this->path));
            $status = match ($result) {
                'clean' => 'clean',
                'infected' => 'quarantined',
                default => throw new RuntimeException('The malware scanner returned an unsupported result.'),
            };
            $this->recordResult($status);

            if ($status === 'clean' && $this->documentId !== null && $this->teamId !== null) {
                $this->updateDocumentStatus(Document::STATUS_QUEUED);
                ProcessDocumentJob::dispatch($this->documentId, $this->teamId);

                return;
            }

            if ($status === 'quarantined') {
                $this->updateDocumentStatus(Document::STATUS_QUARANTINED);
                Log::warning('Malware scanner quarantined an uploaded document', [
                    'team_id' => $this->teamId,
                    'document_id' => $this->documentId,
                ]);
            }
        } catch (Throwable $exception) {
            $this->recordResult('scan_failed');
            $this->updateDocumentStatus(Document::STATUS_SCAN_FAILED);

            Log::error('Uploaded file could not be scanned; processing is blocked', [
                'team_id' => $this->teamId,
                'document_id' => $this->documentId,
                'exception' => $exception::class,
                'reason' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->updateDocumentStatus(Document::STATUS_SCAN_FAILED);

        Log::critical('Malware scan job exhausted its retries', [
            'team_id' => $this->teamId,
            'document_id' => $this->documentId,
            'exception' => $exception === null ? null : $exception::class,
        ]);
    }

    private function recordResult(string $status): void
    {
        $metadata = [
            'path' => $this->path,
            'status' => $status,
            'scanner' => config('services.clamav.enabled') ? 'clamav' : null,
            'scanned_at' => now()->toIso8601String(),
        ];

        if (! Storage::disk('local')->put(
            $this->path.'.scan.json',
            json_encode($metadata, JSON_THROW_ON_ERROR),
        )) {
            throw new RuntimeException('The scan result could not be saved.');
        }
    }

    private function updateDocumentStatus(string $status): void
    {
        if ($this->documentId === null || $this->teamId === null) {
            return;
        }

        Document::withoutGlobalScopes()
            ->whereKey($this->documentId)
            ->where('team_id', $this->teamId)
            ->update(['status' => $status, 'updated_at' => now()]);
    }
}
