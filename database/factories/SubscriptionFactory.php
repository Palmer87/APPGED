<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'plan_id' => Plan::factory(),
            'billing_cycle' => 'monthly',
            'status' => 'trialing',
            'starts_at' => now(),
            'trial_starts_at' => now(),
            'trial_ends_at' => now()->addDays(14),
            'current_period_starts_at' => now(),
            'current_period_ends_at' => now()->addDays(14),
            'cancelled_at' => null,
            'ended_at' => null,
            'auto_renew' => true,
            'provider' => 'manual',
            'provider_subscription_id' => null,
            'metadata' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => 'active',
            'starts_at' => now()->subDays(30),
            'trial_starts_at' => now()->subDays(44),
            'trial_ends_at' => now()->subDays(30),
            'current_period_starts_at' => now(),
            'current_period_ends_at' => now()->addMonth(),
        ]);
    }

    public function trialing(int $daysRemaining = 14): static
    {
        return $this->state(fn () => [
            'status' => 'trialing',
            'starts_at' => now(),
            'trial_starts_at' => now(),
            'trial_ends_at' => now()->addDays($daysRemaining),
            'current_period_starts_at' => now(),
            'current_period_ends_at' => now()->addDays($daysRemaining),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => 'expired',
            'starts_at' => now()->subDays(30),
            'trial_starts_at' => now()->subDays(30),
            'trial_ends_at' => now()->subDays(16),
            'current_period_starts_at' => now()->subDays(30),
            'current_period_ends_at' => now()->subDays(16),
            'ended_at' => now()->subDays(16),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'auto_renew' => false,
        ]);
    }
}
