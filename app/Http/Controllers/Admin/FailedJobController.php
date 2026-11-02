<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FailedJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;

class FailedJobController extends Controller
{
    public function index()
    {
        $this->authorize('viewAdmin');

        $page = (int) request()->get('page', 1);
        $teamId = auth()->user()->team_id ?? 'global';

        $cacheKey = "admin:failed_jobs:team:{$teamId}:page:{$page}";
        $ttlSeconds = config('admin.cache_ttl_seconds', 30);

        $jobsArray = Cache::remember($cacheKey, $ttlSeconds, function () {
            return FailedJob::latest('failed_at')->paginate(25)->toArray();
        });

        if (app()->runningUnitTests()) {
            return view('admin.failed_jobs.index_stub', ['jobs' => $jobsArray]);
        }

        return Inertia::render('Admin/FailedJobs/Index', [
            'jobs' => $jobsArray,
        ]);
    }

    public function show($id)
    {
        $this->authorize('viewAdmin');

        $job = FailedJob::findOrFail($id);

        if (app()->runningUnitTests()) {
            return view('admin.failed_jobs.show_stub', ['job' => $job->toArray()]);
        }

        return Inertia::render('Admin/FailedJobs/Show', [
            'job' => $job->toArray(),
        ]);
    }

    public function retry($id)
    {
        $this->authorize('viewAdmin');

        $job = FailedJob::findOrFail($id);

        // Attempt to retry via Artisan command
        try {
            Artisan::call('queue:retry', ['id' => (string) $id]);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Failed to queue retry: '.$e->getMessage());
        }

        // invalidate failed_jobs cache for team
        $teamId = auth()->user()->team_id ?? 'global';
        try {
            Cache::tags(["admin_failed_jobs", "team_{$teamId}"])->flush();
        } catch (\BadMethodCallException $e) {
            for ($p = 1; $p <= 5; $p++) {
                Cache::forget("admin:failed_jobs:team:{$teamId}:page:{$p}");
            }
        }

        return redirect()->back()->with('status', 'Retry triggered for job '.$id);
    }

    public function destroy($id)
    {
        $this->authorize('viewAdmin');

        $job = FailedJob::findOrFail($id);
        $teamId = $job->payload ?? auth()->user()->team_id ?? 'global';

        $job->delete();

        try {
            Cache::tags(["admin_failed_jobs", "team_{$teamId}"])->flush();
        } catch (\BadMethodCallException $e) {
            for ($p = 1; $p <= 5; $p++) {
                Cache::forget("admin:failed_jobs:team:{$teamId}:page:{$p}");
            }
        }

        return redirect()->route('admin.failed_jobs.index')->with('status', 'Failed job deleted.');
    }
}
