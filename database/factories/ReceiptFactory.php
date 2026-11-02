<?php

namespace Database\Factories;

use App\Models\Receipt;
use App\Models\Team;
use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReceiptFactory extends Factory
{
    protected $model = Receipt::class;

    public function definition()
    {
        $team = Team::factory()->create();
        $document = Document::factory()->create(['team_id' => $team->id]);

        return [
            'team_id' => $team->id,
            'document_id' => $document->id,
            'vendor_name' => $this->faker->company,
            'receipt_number' => strtoupper('RCPT-' . $this->faker->bothify('2026-###')),
            'issue_date' => $this->faker->date(),
            'total_amount' => $this->faker->randomFloat(2, 1, 5000),
            'currency' => 'USD',
            'status' => Receipt::STATUS_PAID,
            'reference' => $this->faker->uuid,
        ];
    }
}
