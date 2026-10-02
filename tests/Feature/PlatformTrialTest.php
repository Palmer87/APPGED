<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\PlatformUser;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformTrialTest extends TestCase
{
    use RefreshDatabase;

    protected PlatformUser $owner;

    protected Organization $org;

    protected Plan $plan;

    protected Subscription $subscription;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = PlatformUser::create([
            'name' => 'Owner',
            'email' => 'owner@platform.test',
            'password' => Hash::make('password'),
            'role' => 'platform_owner',
            'is_active' => true,
        ]);

        $this->org = Organization::create([
            'name' => 'Trial Org',
            'slug' => 'trial-org',
            'status' => 'active',
        ]);

        $this->plan = Plan::firstOrCreate(
            ['slug' => 'trial-plan'],
            [
                'name' => 'Trial Plan',
                'monthly_price' => 20000,
                'annual_price' => 200000,
                'currency' => 'XOF',
                'is_active' => true,
            ]
        );

        $this->subscription = Subscription::create([
            'organization_id' => $this->org->id,
            'plan_id' => $this->plan->id,
            'billing_cycle' => 'monthly',
            'status' => 'trialing',
            'starts_at' => now(),
            'trial_ends_at' => now()->addDays(14),
            'current_period_starts_at' => now(),
            'current_period_ends_at' => now()->addDays(14),
        ]);
    }

    public function test_can_list_trials(): void
    {
        $response = $this->actingAs($this->owner, 'platform')
            ->get('/platform/trials');

        $response->assertOk();
    }

    public function test_can_extend_trial(): void
    {
        $oldTrialEnd = $this->subscription->trial_ends_at;

        $response = $this->actingAs($this->owner, 'platform')
            ->post("/platform/trials/{$this->org->id}/extend", [
                'days' => 7,
            ]);

        $response->assertRedirect();
        $this->subscription->refresh();
        $this->assertTrue($this->subscription->trial_ends_at->greaterThan($oldTrialEnd));
    }

    public function test_can_end_trial_immediately(): void
    {
        $response = $this->actingAs($this->owner, 'platform')
            ->post("/platform/trials/{$this->org->id}/end");

        $response->assertRedirect();
        $this->subscription->refresh();
        $this->assertEquals('expired', $this->subscription->status);
    }

    public function test_can_convert_trial_into_active_subscription(): void
    {
        $response = $this->actingAs($this->owner, 'platform')
            ->post("/platform/trials/{$this->org->id}/convert", [
                'plan_id' => $this->plan->id,
                'billing_cycle' => 'annual',
            ]);

        $response->assertRedirect();
        $this->subscription->refresh();
        $this->assertEquals('active', $this->subscription->status);
        $this->assertEquals('annual', $this->subscription->billing_cycle);
    }
}
