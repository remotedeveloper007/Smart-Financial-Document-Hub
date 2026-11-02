<?php

namespace App\Jobs;

use App\Actions\Audit\RecordAuditLogAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class RecordAuditLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function middleware(): array
    {
        return [new \App\Jobs\Middleware\SetTeamContext($this->teamId)];
    }

    public function __construct(
        public int $teamId,
        public ?int $userId,
        public string $action,
        public ?string $modelType = null,
        public ?int $modelId = null,
        public ?array $changes = null,
        public string $severity = 'info',
        public ?string $message = null,
        public ?string $errorCode = null,
        public ?string $ip = null,
    ) {
    }

    public function handle(RecordAuditLogAction $recordAuditLogAction): void
    {
        $recordAuditLogAction->execute(
            $this->teamId,
            $this->userId,
            $this->action,
            $this->modelType,
            $this->modelId,
            $this->changes,
            $this->severity,
            $this->message,
            $this->errorCode,
            $this->ip,
        );
    }
}
