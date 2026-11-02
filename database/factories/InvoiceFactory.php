<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Invoice;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $team = Team::factory()->create();
        $document = Document::factory()->create(['team_id' => $team->id]);

        return [
            'team_id' => $team->id,
            'document_id' => $document->id,
            'external_id' => fake()->uuid(),
            'vendor_name' => fake()->company(),
            'invoice_number' => fake()->bothify('INV-2026-###'),
            'issue_date' => fake()->date(),
            'due_date' => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'total_amount' => fake()->randomFloat(2, 100, 5000),
            'currency' => 'USD',
            'status' => fake()->randomElement([Invoice::STATUS_PAID, Invoice::STATUS_UNPAID, Invoice::STATUS_OVERDUE]),
            'reference' => fake()->bothify('REF-####'),
        ];
    }
}
