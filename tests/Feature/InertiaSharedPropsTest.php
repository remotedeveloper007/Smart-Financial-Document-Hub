<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Tests\TestCase;

class InertiaSharedPropsTest extends TestCase
{
    public function test_inertia_shares_auth_and_admin_capabilities()
    {
        $this->withoutMiddleware();

        $user = User::factory()->create();
        $this->actingAs($user);

        Gate::shouldReceive('allows')->with('viewPlatformAdmin')->andReturn(false);
        Gate::shouldReceive('allows')->with('viewAdmin')->andReturn(true);
        Gate::shouldReceive('allows')->with('viewHorizon')->andReturn(false);

        $shared = Inertia::getShared();

        $this->assertArrayHasKey('auth', $shared);
        $this->assertArrayHasKey('can', $shared);

        $this->assertIsCallable($shared['auth']);
        $this->assertIsCallable($shared['can']);

        $auth = $shared['auth']();
        $can = $shared['can']();

        $this->assertArrayHasKey('user', $auth);
        $this->assertFalse($can['isPlatformAdmin']);
        $this->assertTrue($can['viewAdmin']);
        $this->assertFalse($can['viewHorizon']);
    }

    public function test_inertia_shares_only_the_active_team_user_permissions(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);
        $permission = Permission::create([
            'name' => 'View invoices',
            'slug' => Permission::PERMISSION_VIEW_INVOICES,
        ]);
        $role = Role::create([
            'team_id' => $team->id,
            'name' => 'Invoice reader',
            'slug' => 'invoice-reader',
        ]);
        $role->permissions()->attach($permission);
        $user->roles()->attach($role);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('auth.user.id', $user->id)
                ->where('can.permissions', [Permission::PERMISSION_VIEW_INVOICES])
                ->where('can.isPlatformAdmin', false)
                ->where('can.viewAdmin', false));
    }

    public function test_unauthenticated_inertia_props_have_no_permissions(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('can.permissions', [])
                ->where('can.isPlatformAdmin', false)
                ->where('can.viewAdmin', false));
    }
}
