<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'original_filename' => fake()->slug().'.pdf',
            'openai_file_id' => null,
            'vector_store_file_id' => null,
            'status' => DocumentStatus::Pending,
            'attributes' => null,
            'error_message' => null,
            'uploaded_by' => fake()->userName(),
            'file_size' => fake()->numberBetween(1024, 10485760),
            'mime_type' => 'application/pdf',
        ];
    }
}
