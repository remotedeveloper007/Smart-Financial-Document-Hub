<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up()
    {
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('external_id')->nullable();
            $table->longText('credentials')->nullable();
            $table->string('status')->default('disabled');
            $table->timestamps();

            $table->unique(['team_id', 'provider']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('integrations');
    }
};
