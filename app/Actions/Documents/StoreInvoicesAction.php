<?php

namespace App\Actions\Documents;

use Illuminate\Support\Facades\DB;

final class StoreInvoicesAction
{
    public function execute(int $teamId, int $documentId, array $invoices): void
    {
        DB::transaction(function () use ($teamId, $documentId, $invoices) {
            foreach ($invoices as $invoice) {
                $recordExists = DB::table('invoices')
                    ->where('document_id', $documentId)
                    ->where('external_id', $invoice['external_id'])
                    ->exists();

                if ($recordExists) {
                    DB::table('invoices')
                        ->where('document_id', $documentId)
                        ->where('external_id', $invoice['external_id'])
                        ->update([
                            'team_id' => $teamId,
                            'vendor_name' => $invoice['vendor_name'],
                            'invoice_number' => $invoice['invoice_number'],
                            'issue_date' => $invoice['issue_date'],
                            'due_date' => $invoice['due_date'],
                            'total_amount' => $invoice['total_amount'],
                            'currency' => $invoice['currency'],
                            'status' => $invoice['status'],
                            'reference' => $invoice['reference'],
                            'updated_at' => now(),
                        ]);

                    continue;
                }

                DB::table('invoices')->insert([
                    'team_id' => $teamId,
                    'document_id' => $documentId,
                    'external_id' => $invoice['external_id'],
                    'vendor_name' => $invoice['vendor_name'],
                    'invoice_number' => $invoice['invoice_number'],
                    'issue_date' => $invoice['issue_date'],
                    'due_date' => $invoice['due_date'],
                    'total_amount' => $invoice['total_amount'],
                    'currency' => $invoice['currency'],
                    'status' => $invoice['status'],
                    'reference' => $invoice['reference'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }
}
