<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class() extends Migration
{
    public function up(): void
    {
        DB::table('ai_requests')
            ->select(['id', 'input'])
            ->orderBy('id')
            ->chunkById(100, function ($requests): void {
                foreach ($requests as $request) {
                    DB::table('ai_requests')
                        ->where('id', $request->id)
                        ->update(['input' => Crypt::encryptString($request->input)]);
                }
            });
    }

    public function down(): void
    {
        DB::table('ai_requests')
            ->select(['id', 'input'])
            ->orderBy('id')
            ->chunkById(100, function ($requests): void {
                foreach ($requests as $request) {
                    DB::table('ai_requests')
                        ->where('id', $request->id)
                        ->update(['input' => Crypt::decryptString($request->input)]);
                }
            });
    }
};
