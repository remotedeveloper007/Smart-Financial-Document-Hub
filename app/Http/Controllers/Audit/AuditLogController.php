<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\Tenancy\TeamContext;
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

    public function list(TeamContext $teamContext): JsonResponse
    {
        $this->authorize('viewAny', AuditLog::class);

        $logs = AuditLog::query()
            ->where('team_id', $teamContext->teamId())
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json(['logs' => $logs]);
    }
}
