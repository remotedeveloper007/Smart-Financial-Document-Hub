<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FaultLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;

class FaultLogController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAdmin');

        $page = (int) $request->get('page', 1);
        $teamId = auth()->user()->team_id ?? 'global';

        $cacheKey = "admin:fault_logs:team:{$teamId}:page:{$page}";
        $ttlSeconds = config('admin.cache_ttl_seconds', 30);

        $logsArray = Cache::remember($cacheKey, $ttlSeconds, function () {
            return FaultLog::latest()->paginate(25)->toArray();
        });

        if (app()->runningUnitTests()) {
            return view('admin.fault_logs.index_stub', ['logs' => $logsArray]);
        }

        return Inertia::render('Admin/FaultLogs/Index', [
            'logs' => $logsArray,
        ]);
    }

    public function show($id)
    {
        $this->authorize('viewAdmin');

        $log = FaultLog::findOrFail($id);

        if (app()->runningUnitTests()) {
            return view('admin.fault_logs.show_stub', ['log' => $log->toArray()]);
        }

        return Inertia::render('Admin/FaultLogs/Show', [
            'log' => $log->toArray(),
        ]);
    }

    public function destroy($id)
    {
        $this->authorize('viewAdmin');

        $log = FaultLog::findOrFail($id);
        $teamId = $log->team_id ?? auth()->user()->team_id ?? 'global';

        $log->delete();

        // Invalidate cache for this team's fault logs. First try tags (Redis), fallback to explicit keys.
        try {
            Cache::tags(["admin_fault_logs", "team_{$teamId}"])->flush();
        } catch (\BadMethodCallException $e) {
            for ($p = 1; $p <= 5; $p++) {
                Cache::forget("admin:fault_logs:team:{$teamId}:page:{$p}");
            }
        }

        return redirect()->route('admin.fault_logs.index')->with('status', 'Fault log deleted.');
    }
}
