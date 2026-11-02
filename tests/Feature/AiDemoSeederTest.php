<?php

namespace Tests\Feature;

use App\Models\AiRequest;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\AiDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_demo_seeder_is_repeatable_and_creates_only_synthetic_rows(): void
    {
        $seeder = app(AiDemoSeeder::class);
        $seeder->run();
        $seeder->run();

        $team = Team::query()->where('slug', 'ai-demo-workspace')->firstOrFail();
        $user = User::query()->where('email', 'ai.tester@local.test')->firstOrFail();

        $this->assertSame($team->id, $user->team_id);
        $this->assertDatabaseCount('ai_requests', 3);
        $this->assertDatabaseHas('ai_requests', [
            'team_id' => $team->id,
            'user_id' => $user->id,
            'model' => 'demo-simulator',
            'status' => AiRequest::STATUS_COMPLETED,
        ]);
        $this->assertDatabaseHas('ai_requests', [
            'team_id' => $team->id,
            'user_id' => $user->id,
            'status' => AiRequest::STATUS_QUEUED,
        ]);
        $this->assertDatabaseHas('ai_requests', [
            'team_id' => $team->id,
            'user_id' => $user->id,
            'status' => AiRequest::STATUS_FAILED,
            'error_message' => 'Synthetic demo failure. No provider request was made.',
        ]);
    }
}