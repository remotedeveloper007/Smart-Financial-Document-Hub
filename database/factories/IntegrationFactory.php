<?php

namespace Database\Factories;

use App\Models\Integration;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class IntegrationFactory extends Factory
{
    protected $model = Integration::class;

    public function definition()
    {
        $team = Team::factory()->create();

        return [
            'team_id' => $team->id,
            'provider' => $this->faker->randomElement(['quickbooks', 'xero', 'zoho']),
            'external_id' => $this->faker->uuid,
            'credentials' => ['token' => $this->faker->sha1],
            'status' => 'enabled',
        ];
    }
}
