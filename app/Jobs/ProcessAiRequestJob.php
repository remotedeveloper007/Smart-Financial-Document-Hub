<?php

namespace App\Jobs;

use App\Actions\Ai\ProcessAiRequestAction;
use App\Models\AiRequest;
// use App\Models\Team;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProcessAiRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $aiRequestId, public int $teamId)
    {
    }

    public function middleware(): array
    {
        return [new Middleware\SetTeamContext($this->teamId)];
    }

    public function handle(ProcessAiRequestAction $action): void
    {
        $request = AiRequest::withoutGlobalScopes()
            ->where('id', $this->aiRequestId)
            ->where('team_id', $this->teamId)
            ->firstOrFail();

        $request->update(['status' => AiRequest::STATUS_PROCESSING]);

        try {
            $response = $action->execute($request);

            $request->update([
                'response' => $response,
                'status' => AiRequest::STATUS_COMPLETED,
            ]);
        } catch (\Throwable $e) {
            $request->update([
                'status' => AiRequest::STATUS_FAILED,
                'error_message' => 'The AI request could not be processed. Please try again later.',
            ]);

            \Illuminate\Support\Facades\Log::error('AI request processing failed', [
                'ai_request_id' => $this->aiRequestId,
                'team_id' => $this->teamId,
                'exception' => $e::class,
            ]);

            throw $e;
        }
    }
}
