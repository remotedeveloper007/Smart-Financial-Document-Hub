<?php

namespace Database\Factories;

use App\Models\AiRequest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AiRequestFactory extends Factory
{
    protected $model = AiRequest::class;

    public function definition()
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);

        return [
            'team_id' => $team->id,
            'user_id' => $user->id,
            'model' => 'gpt-5-mini',
            'input' => $this->faker->sentence(12),
            'response' => ['text' => $this->faker->paragraph()],
            'status' => AiRequest::STATUS_COMPLETED,
        ];
    }
}
