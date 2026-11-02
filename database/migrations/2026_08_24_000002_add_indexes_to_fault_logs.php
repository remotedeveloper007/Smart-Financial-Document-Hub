<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('fault_logs')) {
            Schema::table('fault_logs', function (Blueprint $table) {
                // compound index for common queries by team and time
                $table->index(['team_id', 'created_at'], 'fault_logs_team_created_idx');
                // index severity for quick filtering
                $table->index('severity', 'fault_logs_severity_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('fault_logs')) {
            Schema::table('fault_logs', function (Blueprint $table) {
                $table->dropIndex('fault_logs_team_created_idx');
                $table->dropIndex('fault_logs_severity_idx');
            });
        }
    }
};
