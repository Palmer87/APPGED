<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\PlatformUser;
use App\Models\Subscription;
use App\Services\PlatformOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected PlatformUser $owner;

    protected Organization $org;

    protected Plan $planA;

    protected Plan $planB;

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
            'name' => 'Gamma Tech',
            'slug' => 'gamma-tech',
            'status' => 'active',
        ]);

        $this->planA = Plan::firstOrCreate(
            ['slug' => 'essential'],
            [
                'name' => 'Essential',
                'monthly_price' => 19000,
                'annual_price' => 190000,
                'currency' => 'XOF',
                'max_users' => 5,
                'is_active' => true,
            ]
        );

        $this->planB = Plan::firstOrCreate(
            ['slug' => 'professional'],
            [
                'name' => 'Professional',
                'monthly_price' => 39000,
                'annual_price' => 390000,
                'currency' => 'XOF',
                'max_users' => 20,
                'is_active' => true,
            ]
        );

        $this->subscription = Subscription::create([
            'organization_id' => $this->org->id,
            'plan_id' => $this->planA->id,
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'starts_at' => now(),
            'current_period_starts_at' => now(),
            'current_period_ends_at' => now()->addMonth(),
        ]);
    }

    public function test_can_list_subscriptions(): void
    {
        $response = $this->actingAs($this->owner, 'platform')
            ->get('/platform/subscriptions');

        $response->assertOk();
    }

    public function test_can_view_subscription_details(): void
    {
        $response = $this->actingAs($this->owner, 'platform')
            ->get("/platform/subscriptions/{$this->subscription->id}");

        $response->assertOk();
    }

    public function test_can_cancel_subscription(): void
    {
        $response = $this->actingAs($this->owner, 'platform')
            ->post("/platform/subscriptions/{$this->subscription->id}/cancel");

        $response->assertRedirect();
        $this->subscription->refresh();
        $this->assertEquals('cancelled', $this->subscription->status);
        $this->assertNotNull($this->subscription->cancelled_at);
    }

    public function test_can_change_plan_and_history_is_preserved(): void
    {
        /** @var PlatformOrganizationService $service */
        $service = app(PlatformOrganizationService::class);

        $newSubscription = $service->changePlan(
            organization: $this->org,
            newPlan: $this->planB,
            cycle: 'annual',
            actor: $this->owner
        );

        $this->assertEquals($this->planB->id, $newSubscription->plan_id);
        $this->assertEquals('annual', $newSubscription->billing_cycle);
        $this->assertEquals('active', $newSubscription->status);
        $this->assertEquals($this->planB->id, $this->subscription->fresh()->plan_id);
    }
}
