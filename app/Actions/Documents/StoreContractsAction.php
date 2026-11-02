<?php

namespace App\Actions\Documents;

use Illuminate\Support\Facades\DB;

final class StoreContractsAction
{
    public function execute(int $teamId, int $documentId, array $contracts): void
    {
        DB::transaction(function () use ($teamId, $documentId, $contracts) {
            foreach ($contracts as $contract) {
                $exists = DB::table('contracts')
                    ->where('document_id', $documentId)
                    ->where('external_id', $contract['external_id'])
                    ->exists();

                if ($exists) {
                    DB::table('contracts')
                        ->where('document_id', $documentId)
                        ->where('external_id', $contract['external_id'])
                        ->update([
                            'team_id' => $teamId,
                            'vendor_name' => $contract['vendor_name'],
                            'contract_number' => $contract['contract_number'],
                            'start_date' => $contract['start_date'],
                            'end_date' => $contract['end_date'],
                            'value' => $contract['value'],
                            'currency' => $contract['currency'],
                            'status' => $contract['status'],
                            'reference' => $contract['reference'],
                            'updated_at' => now(),
                        ]);

                    continue;
                }

                DB::table('contracts')->insert([
                    'team_id' => $teamId,
                    'document_id' => $documentId,
                    'external_id' => $contract['external_id'],
                    'vendor_name' => $contract['vendor_name'],
                    'contract_number' => $contract['contract_number'],
                    'start_date' => $contract['start_date'],
                    'end_date' => $contract['end_date'],
                    'value' => $contract['value'],
                    'currency' => $contract['currency'],
                    'status' => $contract['status'],
                    'reference' => $contract['reference'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }
}
