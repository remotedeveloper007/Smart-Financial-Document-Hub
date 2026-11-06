<?php

namespace App\Services\Diagnostics;

use App\Models\FaultLog;
use App\Exceptions\TenantNotResolvedException;
use App\Services\Tenancy\TeamContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class FaultCaptureService
{
    public function capture(Throwable $exception, Request $request): void
    {
        $user = $request->user();
        $teamId = $user?->team_id;

        if ($teamId === null) {
            try {
                $teamId = app(TeamContext::class)->teamId();
            } catch (TenantNotResolvedException) {
                $teamId = null;
            }
        }

        $trace = $this->redactSensitiveValues($exception->getTraceAsString());
        $firstFrame = $exception->getTrace()[0] ?? [];

        try {
            FaultLog::create([
                'team_id' => $teamId,
                'user_id' => $user?->id,
                'error_code' => $exception->getCode() === 0
                    ? null
                    : Str::limit((string) $exception->getCode(), 50),
                'severity' => 'high',
                'message' => Str::limit(
                    $this->redactSensitiveValues($exception->getMessage()),
                    2000,
                ),
                'stack_trace' => Str::limit($trace, 20000),
                'file_name' => basename($exception->getFile()),
                'file_path' => $exception->getFile(),
                'line_number' => $exception->getLine(),
                'class_name' => $firstFrame['class'] ?? $exception::class,
                'method_name' => $firstFrame['function'] ?? null,
                'root_cause' => null,
                'recommended_fix' => null,
                'status' => 'open',
            ]);
        } catch (Throwable $captureException) {
            Log::error('Failed to persist captured application fault', [
                'exception' => $captureException::class,
            ]);
        }
    }

    private function redactSensitiveValues(string $value): string
    {
        return preg_replace(
            [
                '/(authorization\s*[:=]\s*bearer\s+)[^\s,"\']+/i',
                '/((?:password|token|api[_-]?key|secret)\s*[:=]\s*)[^\s&,;"\']+/i',
            ],
            '$1[REDACTED]',
            $value,
        ) ?? '[Fault details redacted]';
    }
}
