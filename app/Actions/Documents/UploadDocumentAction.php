<?php

namespace App\Actions\Documents;

use App\Models\Document;
use App\Actions\Audit\RecordAuditLogAction;
use App\Jobs\ScanUploadedFileJob;
use App\Services\Tenancy\TeamContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class UploadDocumentAction
{
    public function __construct(private TeamContext $teamContext, private RecordAuditLogAction $recordAuditLogAction)
    {
    }

    public function execute(UploadedFile $file, string $name): Document
    {
        // Previous uploads used the public disk and dispatched extraction before malware scanning.
        // Financial documents now stay private until the queued scan explicitly returns clean.
        $path = $file->storeAs(
            sprintf('tenant-%s/documents', $this->teamContext->teamId()),
            Str::uuid().'.pdf',
            'local',
        );

        if (! is_string($path)) {
            throw new RuntimeException('The document could not be saved to private storage.');
        }

        try {
            $document = Document::create([
                'name' => $name,
                'file_path' => $path,
                'status' => Document::STATUS_SCANNING,
            ]);
        } catch (Throwable $exception) {
            if (! Storage::disk('local')->delete($path)) {
                Log::critical('Uploaded file could not be removed after its document record failed', [
                    'path' => $path,
                    'team_id' => $this->teamContext->teamId(),
                ]);
            }

            throw $exception;
        }

        ScanUploadedFileJob::dispatch($path, $document->id, $this->teamContext->teamId())
            ->onQueue('ocr')
            ->afterCommit();

        // Record audit log for upload
        try {
            $this->recordAuditLogAction->execute(
                $this->teamContext->teamId(),
                auth()->id(),
                'document.upload',
                Document::class,
                $document->id,
                null,
                'info',
                'Document uploaded',
                null,
                request()?->ip(),
            );
        } catch (\Throwable $e) {
            Log::error('Document upload audit record could not be written', [
                'team_id' => $this->teamContext->teamId(),
                'user_id' => auth()->id(),
                'document_id' => $document->id,
                'exception' => $e::class,
            ]);
        }

        return $document;
    }
}
