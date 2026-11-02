<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Team;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'document_id' => Document::factory(),
            'external_id' => fake()->uuid(),
            'description' => fake()->sentence(5),
            'amount' => fake()->randomFloat(2, 10, 2000),
            'transaction_date' => fake()->date(),
            'type' => fake()->randomElement(['income', 'expense', 'transfer']),
            'reference' => fake()->bothify('REF-####'),
        ];
    }
}
