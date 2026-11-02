<?php

namespace App\Http\Middleware;

use App\Models\FaultLog;
use Illuminate\Support\Facades\DB;
use Closure;
use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CaptureFaults
{
    public function handle(Request $request, Closure $next)
    {
        Log::debug('CaptureFaults middleware entered', ['path' => $request->path()]);

        // quick entry probe for test debugging
        @file_put_contents(storage_path('logs/fault_capture_entry.log'), now()->toDateTimeString() . "\n", FILE_APPEND | LOCK_EX);

        try {
            return $next($request);
        } catch (Throwable $e) {
            // Write an immediate persistent record to disk (not transactional)
            try {
                $payload = [
                    'timestamp' => now()->toDateTimeString(),
                    'path' => $request->path(),
                    'message' => $e->getMessage(),
                    'user_id' => optional($request->user())->id,
                    'team_id' => optional($request->user())->team_id,
                ];

                $logFile = storage_path('logs/fault_capture_persistent.log');
                @file_put_contents($logFile, json_encode($payload) . PHP_EOL, FILE_APPEND | LOCK_EX);
            } catch (Throwable $_fileErr) {
                // ignore file write errors
            }

            try {
                $trace = $e->getTraceAsString();

                Log::debug('Attempting to persist FaultLog to logging connection');
                @file_put_contents(storage_path('logs/fault_capture_debug.txt'), "Attempting to persist FaultLog to logging connection: " . now() . "\n", FILE_APPEND);

                try {
                    $insert = [
                        'team_id' => optional($request->user())->team_id,
                        'user_id' => optional($request->user())->id,
                        'error_code' => Str::limit($e->getCode() ?: null, 50),
                        'severity' => 'high',
                        'message' => $e->getMessage(),
                        'stack_trace' => $trace,
                        'file_name' => basename($e->getFile()),
                        'file_path' => $e->getFile(),
                        'line_number' => $e->getLine(),
                        'class_name' => null,
                        'method_name' => null,
                        'root_cause' => null,
                        'recommended_fix' => null,
                        'status' => 'open',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $loggingId = DB::connection('logging')->table('fault_logs')->insertGetId($insert);
                    Log::debug('FaultLog persisted to logging connection', ['id' => $loggingId]);
                } catch (Throwable $inner2) {
                    Log::error('Failed to persist FaultLog to logging connection', ['error' => (string) $inner2]);
                }

                $fault = FaultLog::create([
                    'team_id' => optional($request->user())->team_id,
                    'user_id' => optional($request->user())->id,
                    'error_code' => Str::limit($e->getCode() ?: null, 50),
                    'severity' => 'high',
                    'message' => $e->getMessage(),
                    'stack_trace' => $trace,
                    'file_name' => basename($e->getFile()),
                    'file_path' => $e->getFile(),
                    'line_number' => $e->getLine(),
                    'class_name' => null,
                    'method_name' => null,
                    'root_cause' => null,
                    'recommended_fix' => null,
                    'status' => 'open',
                ]);

                Log::debug('FaultLog created', ['id' => $fault->id ?? null]);

                try {
                    $insert = [
                        'team_id' => optional($request->user())->team_id,
                        'user_id' => optional($request->user())->id,
                        'error_code' => Str::limit($e->getCode() ?: null, 50),
                        'severity' => 'high',
                        'message' => $e->getMessage(),
                        'stack_trace' => $trace,
                        'file_name' => basename($e->getFile()),
                        'file_path' => $e->getFile(),
                        'line_number' => $e->getLine(),
                        'class_name' => null,
                        'method_name' => null,
                        'root_cause' => null,
                        'recommended_fix' => null,
                        'status' => 'open',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $loggingId = DB::connection('logging')->table('fault_logs')->insertGetId($insert);
                    Log::debug('FaultLog persisted to logging connection', ['id' => $loggingId]);
                } catch (Throwable $inner2) {
                    Log::error('Failed to persist FaultLog to logging connection', ['error' => (string) $inner2]);
                }
            } catch (Throwable $inner) {
                Log::error('Failed to persist FaultLog', ['error' => (string) $inner]);
            }

            throw $e;
        }
    }
}
