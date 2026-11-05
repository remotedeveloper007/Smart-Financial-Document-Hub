<?php

namespace Tests\Feature;

use App\Actions\Audit\RecordAuditLogAction;
use App\Jobs\RecordAuditLogJob;
use App\Models\Permission;
use App\Models\PlatformAdministrator;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_audit_log_and_list(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();
        $userA = User::factory()->create(['team_id' => $teamA->id]);
        $userB = User::factory()->create(['team_id' => $teamB->id]);

        $action = new RecordAuditLogAction();
        $log = $action->execute($teamA->id, $userA->id, 'document.uploaded', 'App\\Models\\Document', 1, ['name' => 'file.pdf'], 'info', 'Uploaded document');
        $action->execute($teamB->id, $userB->id, 'ai.provider.settings.updated', 'App\\Models\\TeamAiProviderSetting', 2, null, 'info', 'Provider settings updated');

        $this->assertNotNull($log->id);
        $this->assertEquals('document.uploaded', $log->action);

        $systemOwner = User::factory()->create([
            'team_id' => $teamA->id,
            'email' => 'system-owner@example.test',
        ]);
        $systemOwner->markEmailAsVerified();
        config()->set('system_access.admin_emails', ['system-owner@example.test']);
        PlatformAdministrator::query()->create([
            'user_id' => $systemOwner->id,
            'role' => PlatformAdministrator::ROLE_SUPER_ADMIN,
        ]);

        $this->actingAs($systemOwner);

        $this->get(route('audit.logs.page'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Audit/Index'));
        $this->getJson(route('audit.logs.list'))
            ->assertOk()
            ->assertJsonCount(2, 'logs.data')
            ->assertJsonFragment(['action' => 'document.uploaded'])
            ->assertJsonFragment(['action' => 'ai.provider.settings.updated'])
            ->assertJsonFragment(['name' => $teamB->name]);
    }

    public function test_audit_log_action_dispatches_job_when_queue_enabled(): void
    {
        Queue::fake();

        config()->set('audit.queue.enabled', true);
        config()->set('audit.queue.connection', 'sync');
        config()->set('audit.queue.queue', 'audit-logs');

        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);

        app(RecordAuditLogAction::class)->execute(
            $team->id,
            $user->id,
            'document.queued_audit',
            null,
            null,
            ['payload' => true],
            'info',
            'Queued audit log',
            null,
            '127.0.0.1',
        );

        Queue::assertPushed(RecordAuditLogJob::class, function (RecordAuditLogJob $job) use ($team, $user) {
            return $job->teamId === $team->id
                && $job->userId === $user->id
                && $job->action === 'document.queued_audit';
        });
    }

    public function test_tenant_role_with_legacy_audit_permission_cannot_view_system_logs(): void
    {
        $team = Team::factory()->create();
        $user = $this->createAuditLogViewer($team);

        $this->actingAs($user)
            ->get(route('audit.logs.page'))
            ->assertForbidden();

        $this->getJson(route('audit.logs.list'))
            ->assertForbidden();
    }

    private function createAuditLogViewer(Team $team): User
    {
        $permission = Permission::firstOrCreate(
            ['slug' => 'view_audit_logs'],
            ['name' => 'View audit logs'],
        );
        $role = Role::create(['team_id' => $team->id, 'name' => 'Auditor', 'slug' => 'auditor']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['team_id' => $team->id]);
        $user->roles()->attach($role);

        return $user;
    }
}
