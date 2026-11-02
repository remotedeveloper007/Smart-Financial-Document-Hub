<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fault_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('error_code')->nullable()->index();
            $table->string('severity')->default('medium')->index();
            $table->text('message')->nullable();
            $table->longText('stack_trace')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_path')->nullable();
            $table->integer('line_number')->nullable();
            $table->string('class_name')->nullable();
            $table->string('method_name')->nullable();
            $table->text('root_cause')->nullable();
            $table->text('recommended_fix')->nullable();
            $table->string('status')->default('open')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fault_logs');
    }
};
