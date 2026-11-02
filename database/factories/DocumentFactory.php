<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => fake()->sentence(3),
            'file_path' => 'tenant-documents/'.fake()->uuid().'.pdf',
            'status' => Document::STATUS_UPLOADED,
        ];
    }
}
