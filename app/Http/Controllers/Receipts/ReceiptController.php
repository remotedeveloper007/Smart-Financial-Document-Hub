<?php

namespace App\Http\Controllers\Receipts;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use App\Services\Tenancy\TeamContext;
use Inertia\Inertia;
use Illuminate\Http\JsonResponse;
use Inertia\Response;

final class ReceiptController extends Controller
{
    public function page(): Response
    {
        $this->authorize('viewAny', Receipt::class);

        return Inertia::render('Receipts/Index');
    }

    public function list(TeamContext $teamContext): JsonResponse
    {
        $this->authorize('viewAny', Receipt::class);

        $receipts = Receipt::query()
            ->where('team_id', $teamContext->teamId())
            ->orderByDesc('issue_date')
            ->paginate(20);

        return response()->json(['receipts' => $receipts]);
    }
}
