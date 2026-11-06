<?php

namespace Tests\Feature\Authorization;

use App\Models\AiRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiRequestPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_use_ai_can_view_their_own_request_and_create_requests(): void
    {
        $team = Team::factory()->create();
        $permission = Permission::create(['name' => 'Use AI', 'slug' => Permission::PERMISSION_USE_AI]);

        $role = Role::create(['team_id' => $team->id, 'name' => 'AI User', 'slug' => 'ai-user']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['team_id' => $team->id]);
        $user->roles()->attach($role);

        $teamContext = new \App\Services\Tenancy\TeamContext();
        $teamContext->setTeam($team);
        $this->app->instance(\App\Services\Tenancy\TeamContext::class, $teamContext);

        $ai = AiRequest::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
        ]);

        $this->assertTrue($user->can('view', $ai));
        $this->assertTrue($user->can('create', AiRequest::class));

        $otherUser = User::factory()->create(['team_id' => $team->id]);
        $otherUser->roles()->attach($role);
        $this->assertFalse($otherUser->can('view', $ai));
    }

    public function test_user_without_permission_cannot_view_ai(): void
    {
        $team = Team::factory()->create();
        $role = Role::create(['team_id' => $team->id, 'name' => 'Employee', 'slug' => 'employee']);

        $user = User::factory()->create(['team_id' => $team->id]);
        $user->roles()->attach($role);
        $teamContext = new \App\Services\Tenancy\TeamContext();
        $teamContext->setTeam($team);
        $this->app->instance(\App\Services\Tenancy\TeamContext::class, $teamContext);

        $ai = AiRequest::factory()->create(['team_id' => $team->id]);

        $this->assertFalse($user->can('view', $ai));
        $this->assertFalse($user->can('create', AiRequest::class));
    }
}
