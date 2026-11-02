<?php

namespace Tests\Feature;

use App\Actions\Audit\RecordAuditLogAction;
use App\Jobs\RecordAuditLogJob;
use App\Models\Permission;
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
        $team = Team::factory()->create();
        $user = $this->createAuditLogViewer($team);

        $action = new RecordAuditLogAction();
        $log = $action->execute($team->id, $user->id, 'document.uploaded', 'App\\Models\\Document', 1, ['name' => 'file.pdf'], 'info', 'Uploaded document');

        $this->assertNotNull($log->id);
        $this->assertEquals('document.uploaded', $log->action);

        $this->actingAs($user);

        $response = $this->getJson(route('audit.logs.list'));

        $response->assertOk();
        $response->assertJsonFragment(['action' => 'document.uploaded']);
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

    public function test_audit_logs_are_tenant_scoped(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();

        $userA = $this->createAuditLogViewer($teamA);
        $userB = $this->createAuditLogViewer($teamB);

        $action = new RecordAuditLogAction();
        $action->execute($teamA->id, $userA->id, 'a.event', null, null, null, 'info', 'A');
        $action->execute($teamB->id, $userB->id, 'b.event', null, null, null, 'info', 'B');

        $this->actingAs($userA);
        $response = $this->getJson(route('audit.logs.list'));
        $response->assertOk();
        $response->assertJsonCount(1, 'logs.data');
    }

    public function test_user_without_audit_permission_cannot_list_logs(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);

        $this->actingAs($user)
            ->getJson(route('audit.logs.list'))
            ->assertForbidden();
    }

    private function createAuditLogViewer(Team $team): User
    {
        $permission = Permission::firstOrCreate(
            ['slug' => Permission::PERMISSION_VIEW_AUDIT_LOGS],
            ['name' => 'View audit logs'],
        );
        $role = Role::create(['team_id' => $team->id, 'name' => 'Auditor', 'slug' => 'auditor']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['team_id' => $team->id]);
        $user->roles()->attach($role);

        return $user;
    }
}
