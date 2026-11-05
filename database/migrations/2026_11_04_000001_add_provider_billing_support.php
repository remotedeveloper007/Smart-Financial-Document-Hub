<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table): void {
            $table->json('provider_plan_ids')->nullable();
        });

        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->string('provider')->nullable();
            $table->string('provider_subscription_id')->nullable()->index();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('canceled_at')->nullable();
        });

        Schema::create('payment_attempts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id')->index();
            $table->unsignedBigInteger('plan_id')->index();
            $table->unsignedBigInteger('subscription_id')->nullable()->index();
            $table->string('provider', 20);
            $table->string('status', 20)->default('creating');
            $table->string('idempotency_key', 100);
            $table->string('provider_checkout_id')->nullable();
            $table->string('provider_subscription_id')->nullable()->index();
            $table->string('payment_reference')->nullable();
            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 3);
            $table->text('checkout_url')->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'idempotency_key']);
            $table->index(['team_id', 'status']);
        });

        Schema::create('payment_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 20);
            $table->string('event_id', 191);
            $table->string('event_type', 191);
            $table->unsignedBigInteger('payment_attempt_id')->nullable()->index();
            $table->string('status', 20)->default('processed');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_events');
        Schema::dropIfExists('payment_attempts');

        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropIndex(['provider_subscription_id']);
            $table->dropColumn([
                'provider',
                'provider_subscription_id',
                'current_period_start',
                'current_period_end',
                'canceled_at',
            ]);
        });

        Schema::table('plans', function (Blueprint $table): void {
            $table->dropColumn('provider_plan_ids');
        });
    }
};
