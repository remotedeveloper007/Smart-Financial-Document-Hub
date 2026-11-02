<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integrations', function (Blueprint $table): void {
            $table->longText('credentials')->nullable()->change();
        });

        DB::table('integrations')
            ->whereNotNull('credentials')
            ->orderBy('id')
            ->get(['id', 'credentials'])
            ->each(function (object $integration): void {
                $credentials = json_decode($integration->credentials, true, flags: JSON_THROW_ON_ERROR);

                DB::table('integrations')
                    ->where('id', $integration->id)
                    ->update(['credentials' => Crypt::encryptString(json_encode($credentials, JSON_THROW_ON_ERROR))]);
            });
    }

    public function down(): void
    {
        DB::table('integrations')
            ->whereNotNull('credentials')
            ->orderBy('id')
            ->get(['id', 'credentials'])
            ->each(function (object $integration): void {
                $credentials = json_decode(Crypt::decryptString($integration->credentials), true, flags: JSON_THROW_ON_ERROR);

                DB::table('integrations')
                    ->where('id', $integration->id)
                    ->update(['credentials' => json_encode($credentials, JSON_THROW_ON_ERROR)]);
            });

        Schema::table('integrations', function (Blueprint $table): void {
            $table->json('credentials')->nullable()->change();
        });
    }
};