<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Folder;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => fn () => Organization::factory(),
            'folder_id' => null,
            'uploaded_by' => fn () => User::factory(),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph,
            'file_name' => Str::uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size' => $this->faker->numberBetween(1000, 5000000),
            'storage_disk' => 'private',
            'storage_path' => 'organizations/'.(string) Organization::factory()->create()->id.'/documents/'.Str::uuid().'/test.pdf',
            'status' => 'active',
        ];
    }

    public function withFolder()
    {
        return $this->state(fn (array $attributes) => [
            'folder_id' => Folder::factory()->create(['organization_id' => $attributes['organization_id'] ?? Organization::factory()])->id,
        ]);
    }

    public function withUploader()
    {
        return $this->state(fn () => [
            'uploaded_by' => User::factory()->create(['organization_id' => $this->faker->randomElement([Organization::factory()->create()->id])])->id,
        ]);
    }

    public function archived()
    {
        return $this->state([
            'status' => 'archived',
            'deleted_at' => now(),
        ]);
    }
}
