<?php

namespace App\Actions\Documents;

use App\Models\Document;
use App\Models\Invoice;
// use Illuminate\Support\Str;

final class ExtractInvoicesAction
{
    public function execute(Document $document): array
    {
        $baseDate = now()->subDays(14);

        $fakeInvoices = [
            [
                'vendor_name' => 'Acme Supplies Ltd.',
                'invoice_number' => 'INV-2026-001',
                'issue_date' => $baseDate->toDateString(),
                'due_date' => $baseDate->addDays(30)->toDateString(),
                'total_amount' => 1234.56,
                'currency' => 'USD',
                'status' => Invoice::STATUS_UNPAID,
                'reference' => 'PO-7890',
            ],
            [
                'vendor_name' => 'Office Works LLC',
                'invoice_number' => 'INV-2026-002',
                'issue_date' => $baseDate->addDays(1)->toDateString(),
                'due_date' => $baseDate->addDays(31)->toDateString(),
                'total_amount' => 578.90,
                'currency' => 'USD',
                'status' => Invoice::STATUS_PAID,
                'reference' => 'PO-7891',
            ],
        ];

        return collect($fakeInvoices)
            ->map(fn (array $invoice): array => [
                //'external_id' => Str::uuid()->toString(),
                'external_id' => hash('sha256', "{$document->getKey()}:invoice:{$invoice['invoice_number']}"),
                'vendor_name' => $invoice['vendor_name'],
                'invoice_number' => $invoice['invoice_number'],
                'issue_date' => $invoice['issue_date'],
                'due_date' => $invoice['due_date'],
                'total_amount' => $invoice['total_amount'],
                'currency' => $invoice['currency'],
                'status' => $invoice['status'],
                'reference' => $invoice['reference'],
            ])
            ->all();
    }
}
