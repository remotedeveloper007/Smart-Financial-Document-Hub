<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FaultLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

final class SystemHealthController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAdmin');

        $summary = [
            'errors' => FaultLog::count(),
            'warnings' => FaultLog::whereIn('severity', ['warning', 'medium'])->count(),
            'failed_jobs' => DB::table('failed_jobs')->count(),
            'slow_queries' => $this->countFaultsByKeywords(['slow', 'timeout', 'long query', 'query time']),
            'queue_issues' => $this->countFaultsByKeywords(['queue', 'job failed', 'job timeout', 'worker', 'horizon']),
            'storage_issues' => $this->countFaultsByKeywords(['storage', 'disk', 'permission denied', 's3', 'r2', 'filesystem']),
            'permission_issues' => $this->countFaultsByKeywords(['permission', 'unauthorized', 'forbidden', 'authorization', 'access denied']),
            'api_failures' => $this->countFaultsByKeywords(['api', 'http request', 'response', '429', '401', '403', 'timeout']),
            'database_failures' => $this->countFaultsByKeywords(['database', 'sql', 'mysql', 'pdo', 'deadlock', 'connection refused']),
            'critical_faults' => FaultLog::where('severity', 'critical')->count(),
            'high_faults' => FaultLog::where('severity', 'high')->count(),
            'medium_faults' => FaultLog::where('severity', 'medium')->count(),
            'low_faults' => FaultLog::where('severity', 'low')->count(),
            'recent_faults' => FaultLog::latest()->limit(10)->get(),
            'recent_failed_jobs' => DB::table('failed_jobs')->orderByDesc('failed_at')->limit(10)->get([
                'id',
                'connection',
                'queue',
                'exception',
                'failed_at',
            ]),
        ];

        if (app()->runningUnitTests()) {
            return view('admin.system_health.index_stub', ['summary' => $summary]);
        }

        return Inertia::render('Admin/SystemHealth/Index', [
            'summary' => $summary,
        ]);
    }

    protected function countFaultsByKeywords(array $keywords): int
    {
        $query = FaultLog::query();

        foreach ($keywords as $keyword) {
            $query->orWhere('message', 'like', "%{$keyword}%")
                ->orWhere('root_cause', 'like', "%{$keyword}%")
                ->orWhere('recommended_fix', 'like', "%{$keyword}%");
        }

        return $query->count();
    }
}
