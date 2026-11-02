<?php

namespace App\Actions\Search;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;

final class TransactionSearchAction
{
    public function execute(int $teamId, string $query): Collection
    {
        $searchTerm = trim($query);

        return Transaction::query()
            ->where('team_id', $teamId)
            ->when($searchTerm !== '', function ($builder) use ($searchTerm) {
                $like = sprintf('%%%s%%', $searchTerm);

                $builder->where(fn ($query) =>
                    $query->where('description', 'like', $like)
                        ->orWhere('reference', 'like', $like)
                        ->orWhere('type', 'like', $like)
                );
            })
            ->orderByDesc('transaction_date')
            ->limit(50)
            ->get(['id', 'description', 'amount', 'transaction_date', 'type', 'reference']);
    }
}
