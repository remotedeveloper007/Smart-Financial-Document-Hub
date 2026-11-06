<?php

namespace App\Actions\Transactions;

use App\Models\Transaction;
use App\Actions\Audit\RecordAuditLogAction;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Str;

final class ExportTransactionsAction
{
    public function __construct(private RecordAuditLogAction $recordAuditLogAction)
    {
    }

    public function execute(int $teamId): HttpResponse
    {
        $filename = sprintf('transactions-%s.csv', Str::slug(now()->toDateTimeString()));

        $transactions = Transaction::query()
            ->where('team_id', $teamId)
            ->orderByDesc('transaction_date')
            ->get(['transaction_date', 'description', 'amount', 'type', 'reference']);

        $handle = fopen('php://temp', 'w+');

        fputcsv($handle, ['Date', 'Description', 'Amount', 'Type', 'Reference']);

        foreach ($transactions as $transaction) {
            fputcsv($handle, [
                $transaction->transaction_date,
                $this->escapeSpreadsheetFormula($transaction->description),
                number_format($transaction->amount, 2, '.', ''),
                $this->escapeSpreadsheetFormula($transaction->type),
                $this->escapeSpreadsheetFormula($transaction->reference),
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        // Record audit for export
        try {
            $this->recordAuditLogAction->execute(
                $teamId,
                auth()->id(),
                'transactions.export',
                null,
                null,
                ['count' => $transactions->count()],
                'info',
                'Transactions exported',
                null,
                request()?->ip(),
            );
        } catch (\Throwable $e) {
            // swallow errors to avoid breaking download
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    private function escapeSpreadsheetFormula(?string $value): ?string
    {
        if ($value !== null && preg_match('/^[\x00-\x20\xEF\xBB\xBF]*[=+\-@]/', $value) === 1) {
            return "'".$value;
        }

        return $value;
    }
}
