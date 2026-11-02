<?php

namespace App\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\Tenancy\TeamContext;
use Inertia\Inertia;
use Illuminate\Http\JsonResponse;
use Inertia\Response;

final class InvoiceController extends Controller
{
    public function page(): Response
    {
        $this->authorize('viewAny', Invoice::class);

        return Inertia::render('Invoices/Index');
    }

    public function list(TeamContext $teamContext): JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);

        $invoices = Invoice::query()
            ->where('team_id', $teamContext->teamId())
            ->orderByDesc('issue_date')
            ->paginate(20);

        return response()->json(['invoices' => $invoices]);
    }
}
