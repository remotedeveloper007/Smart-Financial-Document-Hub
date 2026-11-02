<?php

namespace App\Http\Controllers\Contracts;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Services\Tenancy\TeamContext;
use Inertia\Inertia;
use Illuminate\Http\JsonResponse;
use Inertia\Response;

final class ContractController extends Controller
{
    public function page(): Response
    {
        $this->authorize('viewAny', Contract::class);

        return Inertia::render('Contracts/Index');
    }

    public function list(TeamContext $teamContext): JsonResponse
    {
        $this->authorize('viewAny', Contract::class);

        $contracts = Contract::query()
            ->where('team_id', $teamContext->teamId())
            ->orderByDesc('start_date')
            ->paginate(20);

        return response()->json(['contracts' => $contracts]);
    }
}
