<?php

namespace Database\Factories;

use App\Models\Direction;
use App\Models\Organization;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'organization_id' => function (array $attributes) {
                if (isset($attributes['direction_id'])) {
                    return Direction::find($attributes['direction_id'])->organization_id;
                }

                return Organization::factory();
            },
            'direction_id' => Direction::factory(),
            'name' => $this->faker->unique()->randomElement([
                'Comptabilité',
                'Finance',
                'Trésorerie',
                'Recrutement',
                'Paie',
                'Formation',
                'Ventes',
                'Relation Client',
                'Développement',
                'Infrastructure',
                'Support',
            ]),
            'code' => $this->faker->unique()->lexify('SRV-???'),
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
