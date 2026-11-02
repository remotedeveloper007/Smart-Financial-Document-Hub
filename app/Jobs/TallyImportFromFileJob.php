<?php

namespace App\Jobs;

use App\Services\Integrations\TallyService;
use App\Services\Integrations\TallyCsvImportParser;
use App\Models\Integration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TallyImportFromFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $integrationId, public int $teamId, public string $filePath)
    {
    }

    public function middleware(): array
    {
        return [new Middleware\SetTeamContext($this->teamId)];
    }

    public function handle(TallyService $tallyService, TallyCsvImportParser $parser): void
    {
        $tenantPrefix = "tenant-{$this->teamId}/uploads/assembled/";
        if (! str_starts_with($this->filePath, $tenantPrefix)) {
            throw new \RuntimeException('Tally import file is outside the integration tenant storage path.');
        }

        $integration = Integration::withoutGlobalScopes()
            ->where('id', $this->integrationId)
            ->where('team_id', $this->teamId)
            ->firstOrFail();

        $disk = Storage::disk('local');
        if (! $disk->exists($this->filePath)) {
            throw new \RuntimeException('Tally import file no longer exists.');
        }

        $scanMetadataPath = $this->filePath.'.scan.json';
        if (! $disk->exists($scanMetadataPath)) {
            throw new \RuntimeException('Tally import file has no malware scan result.');
        }

        $scanMetadata = json_decode($disk->get($scanMetadataPath), true);
        if (
            ! is_array($scanMetadata)
            || ($scanMetadata['status'] ?? null) !== 'clean'
            || ($scanMetadata['scanner'] ?? null) !== 'clamav'
            || ($scanMetadata['path'] ?? null) !== $this->filePath
        ) {
            throw new \RuntimeException('Tally import file did not pass malware scanning.');
        }

        $contents = $disk->get($this->filePath);
        $transactions = $parser->parse($contents);
        $idempotencyKey = hash('sha256', $this->integrationId.':'.hash('sha256', $contents));

        $tallyService->importTransactions((array) $integration->credentials, $transactions, $idempotencyKey);

        Log::info('TallyImportFromFileJob completed', [
            'integration_id' => $this->integrationId,
            'team_id' => $this->teamId,
            'row_count' => count($transactions),
            'idempotency_key' => $idempotencyKey,
        ]);
    }
}
