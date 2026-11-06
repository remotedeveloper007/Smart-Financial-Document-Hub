<?php

namespace Tests\Feature;

use App\Models\AiRequest;
use App\Models\AuditLog;
use App\Models\Contract;
use App\Models\Document;
use App\Models\Integration;
use App\Models\Invoice;
use App\Models\PaymentAttempt;
use App\Models\Receipt;
use App\Models\Subscription;
use App\Models\Team;
use App\Models\TeamAiProviderSetting;
use App\Models\Transaction;
use App\Models\User;
use App\Jobs\Middleware\SetTeamContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use App\Services\Tenancy\TeamContext;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_team_switch_is_limited_to_team_memberships(): void
    {
        $primaryTeam = Team::factory()->create();
        $memberTeam = Team::factory()->create();
        $otherTeam = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $primaryTeam->id]);
        $user->teams()->attach($memberTeam);

        Route::middleware(['web', 'auth'])->get('/__test/active-team', function () {
            return response()->json([
                'team_id' => app(TeamContext::class)->teamId(),
            ]);
        });

        $this->actingAs($user)
            ->post(route('team.active.update'), ['team_id' => $memberTeam->id])
            ->assertRedirect();

        $this->getJson('/__test/active-team')
            ->assertOk()
            ->assertJsonPath('team_id', $memberTeam->id);

        $this->post(route('team.active.update'), ['team_id' => $otherTeam->id])
            ->assertNotFound();

        $this->getJson('/__test/active-team')
            ->assertOk()
            ->assertJsonPath('team_id', $memberTeam->id);
    }

    public function test_forged_active_team_session_value_falls_back_to_a_member_team(): void
    {
        $primaryTeam = Team::factory()->create();
        $otherTeam = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $primaryTeam->id]);

        Route::middleware(['web', 'auth'])->get('/__test/active-team', function () {
            return response()->json([
                'team_id' => app(TeamContext::class)->teamId(),
            ]);
        });

        $this->actingAs($user)
            ->withSession(['active_team_id' => $otherTeam->id])
            ->getJson('/__test/active-team')
            ->assertOk()
            ->assertJsonPath('team_id', $primaryTeam->id);
    }

    public function test_team_context_is_resolved_for_authenticated_user(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);

        $this->actingAs($user)->get('/');

        $this->assertSame($team->id, $user->team_id);
    }

    public function test_team_context_is_shared_during_each_web_request_and_cleared_afterward(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();
        $userA = User::factory()->create(['team_id' => $teamA->id]);
        $userB = User::factory()->create(['team_id' => $teamB->id]);

        Route::middleware('web')->get('/__test/team-context', function () {
            return response()->json([
                'team_id' => app(TeamContext::class)->teamId(),
            ]);
        });

        $this->actingAs($userA)
            ->getJson('/__test/team-context')
            ->assertOk()
            ->assertJsonPath('team_id', $teamA->id);

        auth()->logout();
        $this->assertFalse(app(TeamContext::class)->hasTeam());

        $this->actingAs($userB)
            ->getJson('/__test/team-context')
            ->assertOk()
            ->assertJsonPath('team_id', $teamB->id);
    }

    public function test_team_context_is_resolved_before_route_authorization_and_model_binding(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();
        $documentA = Document::factory()->create(['team_id' => $teamA->id]);
        $documentB = Document::factory()->create(['team_id' => $teamB->id]);
        $user = User::factory()->create(['team_id' => $teamA->id]);

        Gate::define('tenant-context-probe', fn (User $user): bool => app(TeamContext::class)->teamId() === $teamA->id);

        Route::middleware(['web', 'auth', 'can:tenant-context-probe'])
            ->get('/__test/tenant-documents/{document}', function (Document $document) {
                return response()->json(['document_id' => $document->id]);
            });

        $this->actingAs($user)
            ->getJson('/__test/tenant-documents/'.$documentA->id)
            ->assertOk()
            ->assertJsonPath('document_id', $documentA->id);

        $this->getJson('/__test/tenant-documents/'.$documentB->id)
            ->assertNotFound();
    }

    public function test_documents_are_scoped_to_current_team(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();

        Document::factory()->create(['team_id' => $teamA->id]);
        Document::factory()->create(['team_id' => $teamB->id]);

        $user = User::factory()->create(['team_id' => $teamA->id]);

        $this->actingAs($user);

        $documents = Document::all();

        $this->assertCount(1, $documents);
        $this->assertSame($teamA->id, $documents->first()->team_id);
    }

    public function test_all_models_using_belongs_to_team_apply_the_team_scope(): void
    {
        $team = Team::factory()->create();
        $teamContext = app(TeamContext::class);
        $teamContext->setTeam($team);

        foreach ([
            AiRequest::class,
            AuditLog::class,
            Contract::class,
            Document::class,
            Integration::class,
            Invoice::class,
            PaymentAttempt::class,
            Receipt::class,
            Subscription::class,
            TeamAiProviderSetting::class,
            Transaction::class,
        ] as $modelClass) {
            $model = new $modelClass();
            $query = $modelClass::query();

            $this->assertStringContainsString(
                'team_id',
                $query->toSql(),
                "{$modelClass} must constrain reads to the active team.",
            );
            $this->assertSame(
                [$team->id],
                $query->getBindings(),
                "{$modelClass} must bind the active team ID.",
            );
        }
    }

    public function test_tenant_model_queries_fail_when_no_team_context_is_available(): void
    {
        auth()->logout();
        app(TeamContext::class)->clearTeam();

        $this->expectException(\App\Exceptions\TenantNotResolvedException::class);

        Document::query()->get();
    }

    public function test_new_tenant_records_ignore_a_forged_team_id_when_context_exists(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $teamA->id]);
        $this->actingAs($user);

        $document = Document::create([
            'name' => 'Team A document',
            'file_path' => 'tenant-documents/forged.pdf',
            'status' => Document::STATUS_UPLOADED,
            'team_id' => $teamB->id,
        ]);

        $this->assertSame($teamA->id, $document->team_id);
    }

    public function test_new_documents_are_assigned_current_team_id(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);

        $this->actingAs($user);

        $document = Document::create([
            'name' => 'Tenant Document',
            'file_path' => 'tenant-documents/test.pdf',
            'status' => Document::STATUS_UPLOADED,
        ]);

        $this->assertSame($team->id, $document->team_id);
        $this->assertDatabaseHas('documents', ['id' => $document->id, 'team_id' => $team->id]);
    }

    public function test_job_team_context_is_available_during_job_and_cleared_afterward(): void
    {
        $team = Team::factory()->create();
        $middleware = new SetTeamContext($team->id);
        $job = new \stdClass();

        $middleware->handle($job, function () use ($team): void {
            $this->assertSame(
                $team->id,
                app(TeamContext::class)->teamId(),
            );
        });

        $this->assertFalse(app(TeamContext::class)->hasTeam());
    }
}
