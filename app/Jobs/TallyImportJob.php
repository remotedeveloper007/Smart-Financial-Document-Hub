<?php

namespace App\Jobs;

use App\Services\Integrations\TallyService;
use App\Models\Integration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TallyImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $integrationId, public int $teamId, public array $transactions)
    {
    }

    public function middleware(): array
    {
        return [new Middleware\SetTeamContext($this->teamId)];
    }

    public function handle(TallyService $tallyService): void
    {
        $integration = Integration::withoutGlobalScopes()
            ->where('id', $this->integrationId)
            ->where('team_id', $this->teamId)
            ->firstOrFail();

        $idempotencyKey = hash('sha256', $this->integrationId.':'.json_encode($this->transactions, JSON_THROW_ON_ERROR));
        $tallyService->importTransactions((array) $integration->credentials, $this->transactions, $idempotencyKey);

        Log::info('TallyImportJob completed', [
            'integration_id' => $this->integrationId,
            'team_id' => $this->teamId,
            'row_count' => count($this->transactions),
            'idempotency_key' => $idempotencyKey,
        ]);
    }
}
