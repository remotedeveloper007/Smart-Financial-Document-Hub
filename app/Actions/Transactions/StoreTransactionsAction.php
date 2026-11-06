<?php

namespace App\Actions\Transactions;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

final class StoreTransactionsAction
{
    public function execute(int $teamId, int $documentId, array $transactions): void
    {
        DB::transaction(function () use ($teamId, $documentId, $transactions) {
            foreach ($transactions as $transaction) {
                $identity = [
                    'team_id' => $teamId,
                    'document_id' => $documentId,
                    'external_id' => $transaction['external_id'],
                ];
                $createdAt = DB::table('transactions')
                    ->where($identity)
                    ->value('created_at') ?? now();

                DB::table('transactions')->updateOrInsert($identity, [
                    'team_id' => $teamId,
                    'description' => $transaction['description'],
                    'amount' => $transaction['amount'],
                    'transaction_date' => $transaction['transaction_date'],
                    'type' => $transaction['type'],
                    'reference' => $transaction['reference'],
                    'updated_at' => now(),
                    'created_at' => $createdAt,
                ]);
            }
        });
    }
}
