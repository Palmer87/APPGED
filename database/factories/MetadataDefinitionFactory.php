<?php

namespace Database\Factories;

use App\Models\MetadataDefinition;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MetadataDefinition>
 */
class MetadataDefinitionFactory extends Factory
{
    protected $model = MetadataDefinition::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->words(2, true),
            'key' => fake()->unique()->regexify('[a-z]{4}_[a-z]{4}'),
            'type' => fake()->randomElement(['string', 'text', 'integer', 'decimal', 'boolean', 'date', 'datetime']),
            'description' => fake()->sentence(),
            'is_required' => false,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the definition belongs to a specific organization.
     */
    public function forOrganization(Organization $organization): static
    {
        return $this->state(fn (array $attributes) => [
            'organization_id' => $organization->id,
        ]);
    }

    /**
     * Set the definition as required.
     */
    public function required(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_required' => true,
        ]);
    }

    /**
     * Set the definition as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set a specific type.
     */
    public function withType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }
}
