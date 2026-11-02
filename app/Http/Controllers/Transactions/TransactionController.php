<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use App\Actions\Transactions\ExportTransactionsAction;
use App\Models\Transaction;
use App\Services\Tenancy\TeamContext;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

final class TransactionController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Transaction::class);

        $transactions = Transaction::query()
            ->orderByDesc('transaction_date')
            ->limit(50)
            ->get(['id', 'description', 'amount', 'transaction_date', 'type', 'reference']);

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
        ]);
    }

    public function list(): JsonResponse
    {
        $this->authorize('viewAny', Transaction::class);

        $transactions = Transaction::query()
            ->orderByDesc('transaction_date')
            ->limit(50)
            ->get(['id', 'description', 'amount', 'transaction_date', 'type', 'reference']);

        return response()->json(['transactions' => $transactions]);
    }

    public function export(TeamContext $teamContext, ExportTransactionsAction $exportTransactionsAction)
    {
        $this->authorize('export', Transaction::class);

        return $exportTransactionsAction->execute($teamContext->teamId());
    }
}
