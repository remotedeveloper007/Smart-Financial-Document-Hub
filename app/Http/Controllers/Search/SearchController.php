<?php

namespace App\Http\Controllers\Search;

use App\Actions\Search\TransactionSearchAction;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\Tenancy\TeamContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class SearchController extends Controller
{
    public function page(): Response
    {
        $this->authorize('viewAny', Transaction::class);

        return Inertia::render('Search/Index');
    }

    public function transactions(Request $request, TeamContext $teamContext, TransactionSearchAction $searchAction): JsonResponse
    {
        $this->authorize('viewAny', Transaction::class);

        $transactions = $searchAction->execute(
            $teamContext->teamId(),
            (string) $request->query('q', ''),
        );

        return response()->json(['transactions' => $transactions]);
    }
}
