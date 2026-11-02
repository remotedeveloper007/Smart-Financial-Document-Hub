<?php

namespace App\Jobs;

use App\Actions\Documents\ExtractInvoicesAction;
use App\Actions\Documents\ExtractReceiptsAction;
use App\Actions\Documents\ExtractTransactionsAction;
use App\Actions\Documents\ExtractContractsAction;
use App\Actions\Documents\StoreInvoicesAction;
use App\Actions\Documents\StoreReceiptsAction;
use App\Actions\Transactions\StoreTransactionsAction;
use App\Actions\Documents\StoreContractsAction;
use App\Models\Document;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProcessDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function middleware(): array
    {
        return [new \App\Jobs\Middleware\SetTeamContext($this->teamId)];
    }

    public function __construct(public int $documentId, public int $teamId)
    {
    }

    public function handle(
        ExtractTransactionsAction $extractTransactionsAction,
        StoreTransactionsAction $storeTransactionsAction,
        ExtractInvoicesAction $extractInvoicesAction,
        StoreInvoicesAction $storeInvoicesAction,
        ExtractReceiptsAction $extractReceiptsAction,
        StoreReceiptsAction $storeReceiptsAction,
    ): void {
        $document = Document::withoutGlobalScopes()
            ->where('id', $this->documentId)
            ->where('team_id', $this->teamId)
            ->firstOrFail();

        // Historical extraction actions return fixture rows. Never persist those rows in a production tenant.
        if (
            config('services.document_extraction.driver') === 'simulated'
            && ! app()->environment(['local', 'testing'])
        ) {
            $document->update(['status' => Document::STATUS_FAILED]);
            Log::critical('Document extraction is blocked because only the simulated driver is configured', [
                'team_id' => $this->teamId,
                'document_id' => $this->documentId,
            ]);

            return;
        }

        if (config('services.document_extraction.driver') !== 'simulated') {
            throw new \LogicException('The configured document extraction provider is not implemented.');
        }

        $document->update(['status' => Document::STATUS_PROCESSING]);

        $transactions = $extractTransactionsAction->execute($document);
        $storeTransactionsAction->execute(
            $this->teamId,
            $document->id,
            $transactions,
        );

        $invoices = $extractInvoicesAction->execute($document);
        $storeInvoicesAction->execute(
            $this->teamId,
            $document->id,
            $invoices,
        );

        // Receipts extraction and storage
        $receipts = $extractReceiptsAction->execute($document);
        $storeReceiptsAction->execute(
            $this->teamId,
            $document->id,
            $receipts,
        );

        // Contracts extraction and storage
        $contracts = $extractContractsAction->execute($document);
        $storeContractsAction->execute(
            $this->teamId,
            $document->id,
            $contracts,
        );

        $document->update(['status' => Document::STATUS_PROCESSED]);
    }

    public function failed(?\Throwable $exception): void
    {
        Document::withoutGlobalScopes()
            ->where('id', $this->documentId)
            ->where('team_id', $this->teamId)
            ->update(['status' => Document::STATUS_FAILED, 'updated_at' => now()]);

        Log::error('Document processing job failed', [
            'team_id' => $this->teamId,
            'document_id' => $this->documentId,
            'exception' => $exception === null ? null : $exception::class,
        ]);
    }
}
