<?php

namespace App\Actions\Documents;

use Illuminate\Support\Facades\DB;

final class StoreReceiptsAction
{
    public function execute(int $teamId, int $documentId, array $receipts): void
    {
        DB::transaction(function () use ($teamId, $documentId, $receipts) {
            foreach ($receipts as $receipt) {
                $externalId = $receipt['external_id'] ?? null;
                $values = [
                    'team_id' => $teamId,
                    'document_id' => $documentId,
                    'external_id' => $externalId,
                    'vendor_name' => $receipt['vendor_name'] ?? null,
                    'receipt_number' => $receipt['receipt_number'] ?? null,
                    'issue_date' => $receipt['issue_date'] ?? null,
                    'total_amount' => $receipt['total_amount'] ?? 0,
                    'currency' => $receipt['currency'] ?? null,
                    'status' => $receipt['status'] ?? 'unpaid',
                    'reference' => $receipt['reference'] ?? null,
                    'updated_at' => now(),
                ];

                if ($externalId !== null && $externalId !== '') {
                    $existingReceipt = DB::table('receipts')
                        ->where('team_id', $teamId)
                        ->where('document_id', $documentId)
                        ->where('external_id', $externalId)
                        ->exists();

                    if ($existingReceipt) {
                        DB::table('receipts')
                            ->where('team_id', $teamId)
                            ->where('document_id', $documentId)
                            ->where('external_id', $externalId)
                            ->update($values);

                        continue;
                    }

                    DB::table('receipts')
                        ->insert($values + ['created_at' => now()]);

                    continue;
                }

                DB::table('receipts')->insert(
                    $values + ['created_at' => now()],
                );
            }
        });
    }
}
