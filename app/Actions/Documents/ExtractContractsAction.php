<?php

namespace App\Actions\Documents;

use App\Models\Contract;
use App\Models\Document;
//use Illuminate\Support\Str;

final class ExtractContractsAction
{
    public function execute(Document $document): array
    {
        // Deterministic fake contracts for testing purposes
        $baseDate = now()->subDays(30);

        $fake = [
            [
                'vendor_name' => 'Acme Supplies Ltd',
                'contract_number' => 'CTR-2026-001',
                'start_date' => $baseDate->toDateString(),
                'end_date' => $baseDate->addMonths(12)->toDateString(),
                'value' => 15000.00,
                'currency' => 'USD',
                'status' => Contract::STATUS_ACTIVE,
                'reference' => 'REF-CTR-1001',
            ],
        ];

        return collect($fake)
            ->map(fn (array $c): array => [
                //'external_id' => Str::uuid()->toString(),
                'external_id' => hash('sha256', "{$document->getKey()}:contract:{$c['contract_number']}"),
                'vendor_name' => $c['vendor_name'],
                'contract_number' => $c['contract_number'],
                'start_date' => $c['start_date'],
                'end_date' => $c['end_date'],
                'value' => $c['value'],
                'currency' => $c['currency'],
                'status' => $c['status'],
                'reference' => $c['reference'],
            ])
            ->all();
    }
}
