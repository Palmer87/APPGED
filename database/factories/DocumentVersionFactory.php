<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DocumentVersion>
 */
class DocumentVersionFactory extends Factory
{
    protected $model = DocumentVersion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $uuid = (string) Str::uuid();

        return [
            'document_id' => fn () => Document::factory(),
            'uploaded_by' => fn () => User::factory(),
            'version_number' => 1,
            'file_name' => $uuid.'.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size' => $this->faker->numberBetween(1000, 5000000),
            'storage_disk' => 'private',
            'storage_path' => 'organizations/1/documents/1/versions/1/'.$uuid.'.pdf',
            'comment' => $this->faker->sentence(),
        ];
    }

    /**
     * Associate with a specific document.
     */
    public function forDocument(Document $document): static
    {
        return $this->state(fn (array $attributes) => [
            'document_id' => $document->id,
            'uploaded_by' => $document->uploaded_by,
        ]);
    }

    /**
     * Associate with a specific uploader.
     */
    public function forUploader(User $user): static
    {
        return $this->state(fn () => [
            'uploaded_by' => $user->id,
        ]);
    }

    /**
     * Set a specific version number.
     */
    public function version(int $number): static
    {
        return $this->state(fn () => [
            'version_number' => $number,
        ]);
    }
}
