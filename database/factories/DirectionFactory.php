<?php

namespace Database\Factories;

use App\Models\Direction;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Direction>
 */
class DirectionFactory extends Factory
{
    protected $model = Direction::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => $this->faker->unique()->randomElement([
                'Direction Générale',
                'Direction Administrative et Financière',
                'Direction des Ressources Humaines',
                'Direction Commerciale',
                'Direction Marketing & Communication',
                'Direction Informatique',
                'Direction Juridique',
                'Direction des Opérations',
            ]),
            'code' => $this->faker->unique()->lexify('DIR-???'),
            'description' => $this->faker->sentence(),
            'folder_id' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
