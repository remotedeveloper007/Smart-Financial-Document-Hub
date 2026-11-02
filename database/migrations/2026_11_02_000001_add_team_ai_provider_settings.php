<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_requests', function (Blueprint $table): void {
            $table->string('provider')->default('simulated')->after('user_id');
        });

        Schema::create('team_ai_provider_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('model');
            $table->text('credentials')->nullable();
            $table->timestamps();
        });

        $now = now();
        $permissionId = DB::table('permissions')->where('slug', 'manage_ai_settings')->value('id');
        if ($permissionId === null) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => 'Manage AI provider settings',
                'slug' => 'manage_ai_settings',
                'description' => 'Configure the AI provider used by this team.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $roleIds = DB::table('roles')->whereIn('slug', ['owner', 'admin'])->pluck('id');
        foreach ($roleIds as $roleId) {
            DB::table('permission_role')->updateOrInsert(
                ['role_id' => $roleId, 'permission_id' => $permissionId],
                ['created_at' => $now, 'updated_at' => $now],
            );
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('slug', 'manage_ai_settings')->value('id');
        if ($permissionId !== null) {
            DB::table('permission_role')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        Schema::dropIfExists('team_ai_provider_settings');
        Schema::table('ai_requests', function (Blueprint $table): void {
            $table->dropColumn('provider');
        });
    }
};
