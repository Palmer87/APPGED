<?php

namespace Database\Factories;

use App\Models\Folder;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Folder>
 */
class FolderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Folder::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'parent_id' => null,
            'name' => $this->faker->unique()->word(),
            'description' => $this->faker->sentence(),
            'path' => null,
            'created_by' => User::factory(),
            'is_archived' => false,
        ];
    }

    /**
     * State for a root folder (no parent).
     */
    public function root(): static
    {
        return $this->state(fn () => ['parent_id' => null]);
    }

    /**
     * State to assign an existing parent folder.
     */
    public function withParent(Folder $parent): static
    {
        return $this->state(fn () => [
            'parent_id' => $parent->id,
            'organization_id' => $parent->organization_id,
        ]);
    }

    /**
     * State to associate with an existing organization.
     */
    public function withOrganization(Organization $organization): static
    {
        return $this->state(fn () => ['organization_id' => $organization->id]);
    }

    /**
     * State to associate with an existing creator user.
     */
    public function withCreator(User $user): static
    {
        return $this->state(fn () => ['created_by' => $user->id]);
    }
}
