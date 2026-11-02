<?php

namespace App\Jobs;

use App\Services\Integrations\TallyService;
use App\Models\Integration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TallyExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $integrationId, public int $teamId, public ?array $filters = null)
    {
    }

    public function middleware(): array
    {
        return [new Middleware\SetTeamContext($this->teamId)];
    }

    public function handle(TallyService $tallyService): void
    {
        $statusKey = "integration_export_status:team:{$this->teamId}:integration:{$this->integrationId}";
        Cache::put($statusKey, ['status' => 'running', 'started_at' => now()->toIso8601String()], 3600);

        $integration = Integration::withoutGlobalScopes()
            ->where('id', $this->integrationId)
            ->where('team_id', $this->teamId)
            ->firstOrFail();

        try {
            $data = $tallyService->exportTransactions((array) $integration->credentials, $this->filters);
        } catch (\Throwable $exception) {
            Cache::put($statusKey, [
                'status' => 'failed',
                'error' => 'The Tally-compatible export request failed.',
                'failed_at' => now()->toIso8601String(),
            ], 3600);

            Log::error('TallyExportJob failed', [
                'integration_id' => $this->integrationId,
                'team_id' => $this->teamId,
                'exception' => $exception::class,
            ]);

            throw $exception;
        }

        $path = "tenant-{$this->teamId}/exports/tally/".now()->format('Ymd_His_u')."_integration_{$this->integrationId}_".\Illuminate\Support\Str::uuid().'.json';
        Storage::disk('local')->put($path, json_encode($data, JSON_THROW_ON_ERROR));

        Cache::put($statusKey, ['status' => 'completed', 'path' => $path, 'completed_at' => now()->toIso8601String()], 86400);
        Log::info('TallyExportJob completed', ['integration_id' => $this->integrationId, 'team_id' => $this->teamId, 'path' => $path]);
    }
}
