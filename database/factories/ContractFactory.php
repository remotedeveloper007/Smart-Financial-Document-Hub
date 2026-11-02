<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\Team;
use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractFactory extends Factory
{
    protected $model = Contract::class;

    public function definition()
    {
        $team = Team::factory()->create();
        $document = Document::factory()->create(['team_id' => $team->id]);

        return [
            'team_id' => $team->id,
            'document_id' => $document->id,
            'external_id' => $this->faker->uuid,
            'vendor_name' => $this->faker->company,
            'contract_number' => strtoupper('CTR-' . $this->faker->bothify('2026-###')),
            'start_date' => $this->faker->date(),
            'end_date' => $this->faker->date(),
            'value' => $this->faker->randomFloat(2, 1000, 100000),
            'currency' => 'USD',
            'status' => Contract::STATUS_ACTIVE,
            'reference' => $this->faker->uuid,
        ];
    }
}
