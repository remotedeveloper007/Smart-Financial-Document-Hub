<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receipts', function (Blueprint $table): void {
            $table->string('external_id')->nullable()->after('document_id');
            $table->unique(
                ['team_id', 'document_id', 'external_id'],
                'receipts_team_document_external_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table): void {
            $table->dropUnique('receipts_team_document_external_unique');
            $table->dropColumn('external_id');
        });
    }
};
