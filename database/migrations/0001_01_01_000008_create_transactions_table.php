<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('external_id')->nullable();
            $table->string('description');
            $table->decimal('amount', 15, 2);
            $table->date('transaction_date');
            $table->string('type')->default('unknown');
            $table->string('reference')->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
