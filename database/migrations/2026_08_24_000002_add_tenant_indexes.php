<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create indexes in a safe, idempotent way (ignore duplicates)
        /*
        $statements = [
            "ALTER TABLE `documents` ADD INDEX `documents_team_id_created_at_index` (`team_id`, `created_at`)",
            "ALTER TABLE `transactions` ADD INDEX `transactions_team_id_transaction_date_index` (`team_id`, `transaction_date`)",
            "ALTER TABLE `invoices` ADD INDEX `invoices_team_id_issue_date_index` (`team_id`, `issue_date`)",
            "ALTER TABLE `audit_logs` ADD INDEX `audit_logs_team_id_created_at_index` (`team_id`, `created_at`)",
            "ALTER TABLE `subscriptions` ADD INDEX `subscriptions_team_id_starts_at_index` (`team_id`, `starts_at`)",
        ];

        foreach ($statements as $sql) {
            try {
                \DB::statement($sql);
            } catch (\Exception $e) {
                // Ignore duplicate index errors and continue
            }
        }*/
        
        Schema::table('documents', function (Blueprint $table): void {
            $table->index(['team_id', 'created_at']);
        });

        Schema::table('transactions', function (Blueprint $table): void {
            $table->index(['team_id', 'transaction_date']);
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->index(['team_id', 'issue_date']);
        });

        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->index(['team_id', 'starts_at']);
        });        
    }

    public function down(): void
    {
        /*
        $drop = [
            'documents_team_id_created_at_index',
            'transactions_team_id_transaction_date_index',
            'invoices_team_id_issue_date_index',
            'audit_logs_team_id_created_at_index',
            'subscriptions_team_id_starts_at_index',
        ];

        foreach ($drop as $index) {
            try {
                \DB::statement("ALTER TABLE `" . str_replace('_' . $index, '', $index) . "` DROP INDEX `{$index}`");
            } catch (\Exception) {
                // ignore
            }
        }
        */
        Schema::table('subscriptions', fn (Blueprint $table) => $table->dropIndex(['team_id', 'starts_at']));
        Schema::table('invoices', fn (Blueprint $table) => $table->dropIndex(['team_id', 'issue_date']));
        Schema::table('transactions', fn (Blueprint $table) => $table->dropIndex(['team_id', 'transaction_date']));
        Schema::table('documents', fn (Blueprint $table) => $table->dropIndex(['team_id', 'created_at']));        
    }
};
