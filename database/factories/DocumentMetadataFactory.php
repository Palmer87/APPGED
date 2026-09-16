<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentMetadata;
use App\Models\MetadataDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentMetadata>
 */
class DocumentMetadataFactory extends Factory
{
    protected $model = DocumentMetadata::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'metadata_definition_id' => MetadataDefinition::factory(),
            'value_string' => fake()->word(),
            'value_text' => null,
            'value_integer' => null,
            'value_decimal' => null,
            'value_boolean' => null,
            'value_date' => null,
            'value_datetime' => null,
        ];
    }
}
