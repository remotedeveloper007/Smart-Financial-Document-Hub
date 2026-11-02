<?php

namespace App\Actions\Documents;

use App\Models\Document;
//use Illuminate\Support\Str;

final class ExtractTransactionsAction
{
    public function execute(Document $document): array
    {
        $baseDate = now()->subDays(3);

        $fakeTransactions = [
            [
                'description' => 'Office supplies purchase',
                'amount' => 123.45,
                'transaction_date' => $baseDate->toDateString(),
                'type' => 'expense',
                'reference' => 'INV-1001',
            ],
            [
                'description' => 'Client payment received',
                'amount' => 750.00,
                'transaction_date' => $baseDate->addDay()->toDateString(),
                'type' => 'income',
                'reference' => 'PAY-1002',
            ],
            [
                'description' => 'Monthly subscription fee',
                'amount' => 49.99,
                'transaction_date' => $baseDate->addDay()->toDateString(),
                'type' => 'expense',
                'reference' => 'SUB-1003',
            ],
        ];

        return collect($fakeTransactions)
            ->map(fn (array $transaction): array => [
                //'external_id' => Str::uuid()->toString(),
                'external_id' => hash('sha256', "{$document->getKey()}:transaction:{$transaction['reference']}"),
                'description' => $transaction['description'],
                'amount' => $transaction['amount'],
                'transaction_date' => $transaction['transaction_date'],
                'type' => $transaction['type'],
                'reference' => $transaction['reference'],
            ])
            ->all();
    }
}
