<?php

namespace App\Actions\Documents;

use App\Models\Document;
use App\Models\Receipt;
// use Illuminate\Support\Str;

final class ExtractReceiptsAction
{
    public function execute(Document $document): array
    {
        // For now we return deterministic fake receipts to enable testing.
        $baseDate = now()->subDays(10);

        $fakeReceipts = [
            [
                'vendor_name' => 'Corner Store LLC',
                'receipt_number' => 'RCPT-2026-001',
                'issue_date' => $baseDate->toDateString(),
                'total_amount' => 45.67,
                'currency' => 'USD',
                'status' => Receipt::STATUS_PAID,
                'reference' => 'TX-1001',
            ],
            [
                'vendor_name' => 'Cafe Good Beans',
                'receipt_number' => 'RCPT-2026-002',
                'issue_date' => $baseDate->addDays(1)->toDateString(),
                'total_amount' => 12.50,
                'currency' => 'USD',
                'status' => Receipt::STATUS_PAID,
                'reference' => 'TX-1002',
            ],
        ];

        return collect($fakeReceipts)
            ->map(fn (array $r): array => [
                // 'external_id' => Str::uuid()->toString(),
                'external_id' => hash('sha256', "{$document->getKey()}:receipt:{$r['receipt_number']}"),
                'vendor_name' => $r['vendor_name'],
                'receipt_number' => $r['receipt_number'],
                'issue_date' => $r['issue_date'],
                'total_amount' => $r['total_amount'],
                'currency' => $r['currency'],
                'status' => $r['status'],
                'reference' => $r['reference'],
            ])
            ->all();
    }
}
