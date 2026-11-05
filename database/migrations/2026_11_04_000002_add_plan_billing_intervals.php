<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table): void {
            $table->decimal('yearly_price', 10, 2)->nullable();
        });

        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->string('billing_interval', 20)->default('monthly');
        });

        Schema::table('payment_attempts', function (Blueprint $table): void {
            $table->string('billing_interval', 20)->default('monthly');
        });
    }

    public function down(): void
    {
        Schema::table('payment_attempts', function (Blueprint $table): void {
            $table->dropColumn('billing_interval');
        });

        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropColumn('billing_interval');
        });

        Schema::table('plans', function (Blueprint $table): void {
            $table->dropColumn('yearly_price');
        });
    }
};
