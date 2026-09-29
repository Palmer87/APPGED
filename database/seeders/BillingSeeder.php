<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Seeder;

class BillingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            PlanSeeder::class,
            BillingPermissionSeeder::class,
        ]);

        $essentialPlan = Plan::where('slug', 'essential')->first();
        if (! $essentialPlan) {
            return;
        }

        foreach (Organization::query()->cursor() as $organization) {
            if (! $organization->subscriptions()->exists()) {
                $now = now();
                Subscription::create([
                    'organization_id' => $organization->id,
                    'plan_id' => $essentialPlan->id,
                    'billing_cycle' => 'monthly',
                    'status' => 'trialing',
                    'starts_at' => $now,
                    'trial_starts_at' => $now,
                    'trial_ends_at' => (clone $now)->addDays(14),
                    'current_period_starts_at' => $now,
                    'current_period_ends_at' => (clone $now)->addDays(14),
                    'auto_renew' => true,
                    'provider' => 'manual',
                ]);
            }
        }
    }
}
