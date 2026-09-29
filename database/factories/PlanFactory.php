<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Plan '.fake()->unique()->word(),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->sentence(),
            'monthly_price' => 19000,
            'annual_price' => 190000,
            'currency' => 'XOF',
            'max_users' => 10,
            'max_storage_bytes' => 20 * 1024 * 1024 * 1024,
            'max_directions' => 5,
            'max_document_types' => 20,
            'max_ocr_pages_month' => 200,
            'has_api' => false,
            'has_workflows' => false,
            'has_advanced_audit' => false,
            'has_priority_support' => false,
            'has_dedicated_support' => false,
            'has_sla' => false,
            'has_custom_migration' => false,
            'has_custom_integrations' => false,
            'is_custom' => false,
            'is_active' => true,
            'sort_order' => 1,
        ];
    }

    public function essential(): static
    {
        return $this->state(fn () => [
            'name' => 'Essentiel',
            'slug' => 'essential',
            'monthly_price' => 19000,
            'annual_price' => 190000,
            'max_users' => 5,
            'max_storage_bytes' => 20 * 1024 * 1024 * 1024,
            'max_directions' => 3,
            'max_document_types' => 15,
            'max_ocr_pages_month' => 100,
            'has_api' => false,
            'has_workflows' => false,
            'is_custom' => false,
        ]);
    }

    public function professional(): static
    {
        return $this->state(fn () => [
            'name' => 'Professionnel',
            'slug' => 'professional',
            'monthly_price' => 39000,
            'annual_price' => 390000,
            'max_users' => 20,
            'max_storage_bytes' => 100 * 1024 * 1024 * 1024,
            'max_directions' => 10,
            'max_document_types' => 50,
            'max_ocr_pages_month' => 1000,
            'has_api' => true,
            'has_workflows' => true,
            'has_advanced_audit' => true,
            'has_priority_support' => true,
            'is_custom' => false,
        ]);
    }

    public function enterprise(): static
    {
        return $this->state(fn () => [
            'name' => 'Entreprise',
            'slug' => 'enterprise',
            'monthly_price' => null,
            'annual_price' => null,
            'max_users' => null,
            'max_storage_bytes' => null,
            'max_directions' => null,
            'max_document_types' => null,
            'max_ocr_pages_month' => null,
            'has_api' => true,
            'has_workflows' => true,
            'has_advanced_audit' => true,
            'has_priority_support' => true,
            'has_dedicated_support' => true,
            'has_sla' => true,
            'is_custom' => true,
        ]);
    }
}
