<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('external_id')->nullable();
            $table->string('vendor_name');
            $table->string('invoice_number');
            $table->date('issue_date');
            $table->date('due_date');
            $table->decimal('total_amount', 15, 2);
            $table->string('currency', 10)->default('USD');
            $table->string('status')->default('unpaid');
            $table->string('reference')->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
