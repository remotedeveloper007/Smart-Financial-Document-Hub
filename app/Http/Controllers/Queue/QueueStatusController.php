<?php

namespace App\Http\Controllers\Queue;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class QueueStatusController extends Controller
{
    public function page(): Response
    {
        return Inertia::render('Queue/Status');
    }

    public function index(): JsonResponse
    {
        $pendingJobs = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')->count();
        $recentFailedJobs = DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit(5)
            ->get(['id', 'connection', 'queue', 'exception', 'failed_at']);

        return response()->json([
            'pending_jobs' => $pendingJobs,
            'failed_jobs' => $failedJobs,
            'recent_failed_jobs' => $recentFailedJobs,
        ]);
    }
}
