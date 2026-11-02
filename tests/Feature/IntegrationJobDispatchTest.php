<?php

namespace Tests\Feature;

use App\Jobs\TallyExportJob;
use App\Jobs\TallyImportJob;
use App\Models\Integration;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class IntegrationJobDispatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_endpoint_dispatches_tally_import_job(): void
    {
        $team = Team::factory()->create();
        $permission = Permission::create(['name' => 'Manage integrations', 'slug' => Permission::PERMISSION_MANAGE_INTEGRATIONS]);
        $role = Role::create(['team_id' => $team->id, 'name' => 'Integrator', 'slug' => 'integrator']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['team_id' => $team->id]);
        $user->roles()->attach($role);

        $integration = Integration::factory()->create(['team_id' => $team->id]);

        Bus::fake();

        $this->actingAs($user)->postJson(route('integrations.import', ['integration' => $integration->id]), [
            'transactions' => [ ['ref' => 'T1', 'amount' => 100] ]
        ])->assertStatus(202)->assertJson(['accepted' => true]);

        Bus::assertDispatched(TallyImportJob::class, function ($job) use ($integration, $team) {
            return $job->integrationId === $integration->id
                && $job->teamId === $team->id
                && $job->transactions === [['ref' => 'T1', 'amount' => 100]];
        });
    }

    public function test_export_endpoint_dispatches_tally_export_job(): void
    {
        $team = Team::factory()->create();
        $permission = Permission::create(['name' => 'Manage integrations', 'slug' => Permission::PERMISSION_MANAGE_INTEGRATIONS]);
        $role = Role::create(['team_id' => $team->id, 'name' => 'Integrator', 'slug' => 'integrator']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['team_id' => $team->id]);
        $user->roles()->attach($role);

        $integration = Integration::factory()->create(['team_id' => $team->id]);

        Bus::fake();

        $this->actingAs($user)->postJson(route('integrations.export', ['integration' => $integration->id]), [
            'filters' => ['start_date' => '2020-01-01', 'end_date' => '2020-01-31']
        ])->assertStatus(202)->assertJson(['accepted' => true]);

        Bus::assertDispatched(TallyExportJob::class, function ($job) use ($integration, $team) {
            return $job->integrationId === $integration->id
                && $job->teamId === $team->id
                && $job->filters === ['start_date' => '2020-01-01', 'end_date' => '2020-01-31'];
        });
    }
}
