<?php

namespace Tests\Feature;

use App\Models\AiRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Models\TeamAiProviderSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AiFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_shell_includes_a_csrf_token_for_browser_requests(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<meta name="csrf-token" content="'.csrf_token().'">', false);
    }

    public function test_user_with_use_ai_permission_can_submit_request_and_view_page(): void
    {
        $team = Team::factory()->create();
        $permission = Permission::create(['name' => 'Use AI', 'slug' => Permission::PERMISSION_USE_AI]);
        $role = Role::create(['team_id' => $team->id, 'name' => 'AI User', 'slug' => 'ai-user']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['team_id' => $team->id]);
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->get(route('ai.page'));
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Ai/Index')
            ->where('mode', 'simulated')
            ->where('available', true));

        Bus::fake();
        $post = $this->actingAs($user)->postJson(route('ai.create'), ['input' => 'Hello AI']);
        $post->assertStatus(202);

        $requestId = $post->json('id');
        $this->assertDatabaseHas('ai_requests', [
            'id' => $requestId,
            'team_id' => $team->id,
            'user_id' => $user->id,
            'model' => config('ai.model'),
            'status' => AiRequest::STATUS_QUEUED,
        ]);
        $this->assertSame('Hello AI', AiRequest::findOrFail($requestId)->input);
        $storedInput = DB::table('ai_requests')->where('id', $requestId)->value('input');
        $this->assertIsString($storedInput);
        $this->assertStringNotContainsString('Hello AI', $storedInput);

        Bus::assertDispatched(\App\Jobs\ProcessAiRequestJob::class, function ($job) use ($requestId, $team) {
            return $job->aiRequestId === $requestId && $job->teamId === $team->id;
        });
    }

    public function test_ai_requests_list_only_shows_the_current_users_requests(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $teamA->id]);
        $otherUser = User::factory()->create(['team_id' => $teamA->id]);
        $otherTeamUser = User::factory()->create(['team_id' => $teamB->id]);

        AiRequest::factory()->create([
            'team_id' => $teamA->id,
            'user_id' => $user->id,
            'input' => 'My private request',
        ]);
        AiRequest::factory()->create([
            'team_id' => $teamA->id,
            'user_id' => $otherUser->id,
            'input' => 'Another team member private request',
        ]);
        AiRequest::factory()->create([
            'team_id' => $teamB->id,
            'user_id' => $otherTeamUser->id,
            'input' => 'Other team private request',
        ]);

        $permission = Permission::create(['name' => 'Use AI', 'slug' => Permission::PERMISSION_USE_AI]);
        $role = Role::create(['team_id' => $teamA->id, 'name' => 'AI User', 'slug' => 'ai-user']);
        $role->permissions()->attach($permission);

        $user->roles()->attach($role);

        $response = $this->actingAs($user)->getJson(route('ai.list'));
        $response->assertOk();
        $response->assertJsonCount(1, 'requests.data');
        $response->assertJsonPath('requests.data.0.input', 'My private request');
        $response->assertDontSee('Another team member private request');
        $response->assertDontSee('Other team private request');
    }

    public function test_existing_ai_prompts_are_encrypted_by_the_data_migration(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);
        $prompt = 'Synthetic prompt from before encryption';
        $requestId = DB::table('ai_requests')->insertGetId([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'model' => 'demo-simulator',
            'input' => $prompt,
            'status' => AiRequest::STATUS_QUEUED,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $migration = require database_path('migrations/2026_11_05_000003_encrypt_existing_ai_request_inputs.php');

        $migration->up();

        $storedPrompt = DB::table('ai_requests')->where('id', $requestId)->value('input');
        $this->assertNotSame($prompt, $storedPrompt);
        $this->assertSame($prompt, Crypt::decryptString($storedPrompt));
        $this->assertSame($prompt, AiRequest::withoutGlobalScopes()->findOrFail($requestId)->input);

        $migration->down();
        $this->assertSame($prompt, DB::table('ai_requests')->where('id', $requestId)->value('input'));
    }

    public function test_user_without_permission_cannot_access_ai(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);

        $response = $this->actingAs($user)->get(route('ai.page'));
        $response->assertForbidden();
    }

    public function test_prompt_over_configured_limit_is_rejected(): void
    {
        $team = Team::factory()->create();
        $user = $this->createAiUser($team);

        $this->actingAs($user)
            ->post(route('ai.create'), ['input' => str_repeat('x', config('ai.max_prompt_chars') + 1)])
            ->assertRedirect()
            ->assertSessionHasErrors('input');
    }

    public function test_ai_job_cannot_process_request_from_another_team(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();
        $aiRequest = AiRequest::factory()->create(['team_id' => $teamB->id]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        (new \App\Jobs\ProcessAiRequestJob($aiRequest->id, $teamA->id))
            ->handle(app(\App\Actions\Ai\ProcessAiRequestAction::class));
    }

    public function test_ai_job_completes_with_the_explicit_simulated_response(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);
        $aiRequest = AiRequest::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'input' => 'Synthetic prompt for the test suite',
            'status' => AiRequest::STATUS_QUEUED,
        ]);

        (new \App\Jobs\ProcessAiRequestJob($aiRequest->id, $team->id))
            ->handle(app(\App\Actions\Ai\ProcessAiRequestAction::class));

        $aiRequest->refresh();
        $this->assertSame(AiRequest::STATUS_COMPLETED, $aiRequest->status);
        $this->assertSame(
            'Simulated AI response for input: Synthetic prompt for the test suite',
            $aiRequest->response['text'],
        );
    }

    public function test_unimplemented_ai_provider_returns_service_unavailable(): void
    {
        config(['ai.driver' => 'openai']);

        $team = Team::factory()->create();
        $user = $this->createAiUser($team);

        $this->actingAs($user)
            ->get(route('ai.page'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Ai/Index')
                ->where('available', false));

        $this->actingAs($user)
            ->postJson(route('ai.create'), ['input' => 'Synthetic production prompt'])
            ->assertServiceUnavailable()
            ->assertJson([
                'message' => 'The AI provider is not configured for this environment.',
            ]);

        $this->assertSame(
            0,
            AiRequest::withoutGlobalScopes()->where('team_id', $team->id)->count(),
        );
    }

    public function test_simulated_provider_rejects_production_execution(): void
    {
        $this->app->detectEnvironment(static fn (): string => 'production');
        config(['ai.driver' => 'simulated']);
        $aiRequest = AiRequest::factory()->create();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Simulated AI responses are only available in local and testing environments.');

        app(\App\Services\Ai\SimulatedAiProvider::class)->generate($aiRequest, [], $aiRequest->model);
    }

    public function test_team_admin_can_save_encrypted_openai_settings_without_exposing_the_key(): void
    {
        $team = Team::factory()->create();
        $user = $this->createAiUser($team, true);

        $this->actingAs($user)
            ->postJson(route('ai.settings.update'), [
                'provider' => 'openai',
                'model' => 'gpt-4.1-mini',
                'api_key' => 'sk-team-secret-value',
            ])
            ->assertOk()
            ->assertJson([
                'provider' => 'openai',
                'model' => 'gpt-4.1-mini',
                'hasCredential' => true,
                'available' => true,
            ])
            ->assertJsonMissing(['api_key' => 'sk-team-secret-value']);

        $encryptedCredentials = DB::table('team_ai_provider_settings')
            ->where('team_id', $team->id)
            ->value('credentials');
        $this->assertIsString($encryptedCredentials);
        $this->assertStringNotContainsString('sk-team-secret-value', $encryptedCredentials);

        $this->actingAs($user)
            ->get(route('ai.page'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('selectedProvider', 'openai')
                ->where('selectedModel', 'gpt-4.1-mini')
                ->where('hasCredential', true)
                ->where('available', true)
                ->missing('apiKey'));

        $this->actingAs($user)
            ->postJson(route('ai.settings.update'), [
                'provider' => 'simulated',
                'model' => 'demo-simulator',
            ])
            ->assertOk()
            ->assertJson(['hasCredential' => false, 'available' => true]);

        $this->assertSame(
            [],
            TeamAiProviderSetting::withoutGlobalScopes()
                ->where('team_id', $team->id)
                ->firstOrFail()
                ->credentials
                ->getArrayCopy(),
        );
    }

    public function test_user_without_manage_ai_settings_cannot_change_provider_settings(): void
    {
        $team = Team::factory()->create();
        $user = $this->createAiUser($team);

        $this->actingAs($user)
            ->postJson(route('ai.settings.update'), [
                'provider' => 'openai',
                'model' => 'gpt-4.1-mini',
                'api_key' => 'sk-not-authorized',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('team_ai_provider_settings', ['team_id' => $team->id]);
    }

    public function test_openai_request_uses_the_team_key_and_request_model(): void
    {
        Http::fake([
            'api.openai.com/v1/responses' => Http::response([
                'model' => 'gpt-4.1-mini',
                'output' => [[
                    'content' => [['type' => 'output_text', 'text' => 'A safe test response']],
                ]],
                'usage' => ['input_tokens' => 3, 'output_tokens' => 5],
            ]),
        ]);

        $team = Team::factory()->create();
        TeamAiProviderSetting::create([
            'team_id' => $team->id,
            'provider' => 'openai',
            'model' => 'gpt-4.1-mini',
            'credentials' => ['api_key' => 'sk-team-secret-value'],
        ]);
        $otherTeam = Team::factory()->create();
        TeamAiProviderSetting::create([
            'team_id' => $otherTeam->id,
            'provider' => 'openai',
            'model' => 'gpt-4.1-mini',
            'credentials' => ['api_key' => 'sk-other-team-secret'],
        ]);
        $aiRequest = AiRequest::factory()->create([
            'team_id' => $team->id,
            'provider' => 'openai',
            'model' => 'gpt-4.1-mini',
            'input' => 'Summarize synthetic test data',
        ]);

        $response = app(\App\Actions\Ai\ProcessAiRequestAction::class)->execute($aiRequest);

        $this->assertSame('A safe test response', $response['text']);
        $this->assertSame('openai', $response['provider']);
        $this->assertSame(['input_tokens' => 3, 'output_tokens' => 5], $response['usage']);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer sk-team-secret-value')
            && $request['model'] === 'gpt-4.1-mini'
            && $request['input'] === 'Summarize synthetic test data');
    }

    private function createAiUser(Team $team, bool $manageSettings = false): User
    {
        $permission = Permission::firstOrCreate(
            ['slug' => Permission::PERMISSION_USE_AI],
            ['name' => 'Use AI'],
        );
        $role = Role::create(['team_id' => $team->id, 'name' => 'AI User', 'slug' => 'ai-user']);
        $role->permissions()->attach($permission);
        if ($manageSettings) {
            $managePermission = Permission::firstOrCreate(
                ['slug' => Permission::PERMISSION_MANAGE_AI_SETTINGS],
                ['name' => 'Manage AI provider settings'],
            );
            $role->permissions()->attach($managePermission);
        }
        $user = User::factory()->create(['team_id' => $team->id]);
        $user->roles()->attach($role);

        return $user;
    }
}
