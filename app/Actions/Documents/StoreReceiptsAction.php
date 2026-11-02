<?php

namespace App\Actions\Documents;

use Illuminate\Support\Facades\DB;

final class StoreReceiptsAction
{
    public function execute(int $teamId, int $documentId, array $receipts): void
    {
        DB::transaction(function () use ($teamId, $documentId, $receipts) {
            foreach ($receipts as $receipt) {
                $recordExists = DB::table('receipts')
                    ->where('document_id', $documentId)
                    ->where('external_id', $receipt['external_id'])
                    ->exists();

                if ($recordExists) {
                    DB::table('receipts')
                        ->where('document_id', $documentId)
                        ->where('external_id', $receipt['external_id'])
                        ->update([
                            'team_id' => $teamId,
                            'vendor_name' => $receipt['vendor_name'],
                            'receipt_number' => $receipt['receipt_number'],
                            'issue_date' => $receipt['issue_date'],
                            'total_amount' => $receipt['total_amount'],
                            'currency' => $receipt['currency'],
                            'status' => $receipt['status'],
                            'reference' => $receipt['reference'],
                            'updated_at' => now(),
                        ]);

                    continue;
                }

                DB::table('receipts')->insert([
                    'team_id' => $teamId,
                    'document_id' => $documentId,
                    'external_id' => $receipt['external_id'],
                    'vendor_name' => $receipt['vendor_name'],
                    'receipt_number' => $receipt['receipt_number'],
                    'issue_date' => $receipt['issue_date'],
                    'total_amount' => $receipt['total_amount'],
                    'currency' => $receipt['currency'],
                    'status' => $receipt['status'],
                    'reference' => $receipt['reference'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }
}
