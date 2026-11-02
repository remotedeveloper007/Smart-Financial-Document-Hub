<?php

namespace App\Actions\Audit;

use App\Jobs\RecordAuditLogJob;
use App\Models\AuditLog;

final class RecordAuditLogAction
{
    public function execute(int $teamId, ?int $userId, string $action, ?string $modelType = null, ?int $modelId = null, ?array $changes = null, string $severity = 'info', ?string $message = null, ?string $errorCode = null, ?string $ip = null): AuditLog
    {
        $payload = [
            'team_id' => $teamId,
            'user_id' => $userId,
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'changes' => $changes,
            'ip' => $ip,
            'severity' => $severity,
            'error_code' => $errorCode,
            'message' => $message,
        ];

        if (config('audit.queue.enabled', false)) {
            try {
                $job = RecordAuditLogJob::dispatch(
                    $teamId,
                    $userId,
                    $action,
                    $modelType,
                    $modelId,
                    $changes,
                    $severity,
                    $message,
                    $errorCode,
                    $ip,
                );

                if ($connection = config('audit.queue.connection')) {
                    $job->onConnection($connection);
                }

                $job->onQueue(config('audit.queue.queue', 'audit-logs'));
            } catch (\Throwable) {
                // swallow queue dispatch failures, do not block the main execution path
            }

            return AuditLog::make($payload);
        }

        return AuditLog::create($payload);
    }
}
