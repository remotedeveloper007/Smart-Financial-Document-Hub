<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

final class AuditLogController extends Controller
{
    public function page(): Response
    {
        $this->authorize('viewAny', AuditLog::class);

        return Inertia::render('Audit/Index');
    }

    public function list(): JsonResponse
    {
        $this->authorize('viewAny', AuditLog::class);

        $logs = AuditLog::withoutGlobalScopes()
            ->with('team:id,name')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json(['logs' => $logs]);
    }
}
