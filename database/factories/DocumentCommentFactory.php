<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentComment;
use App\Models\DocumentVersion;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentComment>
 */
class DocumentCommentFactory extends Factory
{
    protected $model = DocumentComment::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'document_id' => Document::factory(),
            'document_version_id' => null,
            'user_id' => User::factory(),
            'parent_id' => null,
            'content' => fake()->paragraph(),
        ];
    }

    public function root(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => null,
        ]);
    }

    public function reply(DocumentComment $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent->id,
            'document_id' => $parent->document_id,
            'organization_id' => $parent->organization_id,
            'document_version_id' => $parent->document_version_id,
        ]);
    }

    public function forDocument(Document $document): static
    {
        return $this->state(fn (array $attributes) => [
            'document_id' => $document->id,
            'organization_id' => $document->organization_id,
        ]);
    }

    public function forVersion(DocumentVersion $version): static
    {
        return $this->state(fn (array $attributes) => [
            'document_version_id' => $version->id,
            'document_id' => $version->document_id,
        ]);
    }

    public function trashed(): static
    {
        return $this->state(fn (array $attributes) => [
            'deleted_at' => now(),
        ]);
    }
}
