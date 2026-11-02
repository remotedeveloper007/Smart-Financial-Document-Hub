<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Support\Facades\Log;

class Handler extends ExceptionHandler
{
    public function report(Throwable $e): void
    {
        try {
            $payload = [
                'timestamp' => now()->toDateTimeString(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];

            @file_put_contents(storage_path('logs/fault_capture_persistent.log'), json_encode($payload) . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (Throwable $inner) {
            Log::error('Failed to write persistent fault file', ['error' => (string) $inner]);
        }

        parent::report($e);
    }
}
