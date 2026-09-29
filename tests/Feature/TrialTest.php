<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Services\BillingService;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanSeeder::class);
    }

    public function test_trial_duration_is_14_days_without_credit_card(): void
    {
        $org = Organization::create([
            'name' => 'Trial Corp',
            'slug' => 'trial-corp',
        ]);

        $sub = $org->currentSubscription;
        $this->assertNotNull($sub);
        $this->assertSame('trialing', $sub->status);
        $this->assertSame(14, $sub->trialDaysRemaining());
        $this->assertSame('manual', $sub->provider);
    }

    public function test_trial_expiration_state(): void
    {
        $org = Organization::factory()->create();
        $sub = $org->currentSubscription;

        // Move trial end to past
        $sub->update([
            'trial_ends_at' => now()->subDay(),
        ]);

        $this->assertTrue($sub->fresh()->isTrialExpired());
        $this->assertTrue($sub->fresh()->isExpired());
        $this->assertSame(0, $sub->fresh()->trialDaysRemaining());

        // Check handle expiration
        app(BillingService::class)->checkAndHandleExpiration($org);
        $this->assertSame('expired', $sub->fresh()->status);
    }

    public function test_admin_can_extend_trial(): void
    {
        $org = Organization::factory()->create();
        $sub = $org->currentSubscription;

        // Expire
        $sub->update(['trial_ends_at' => now()->subDay()]);
        $this->assertSame(0, $sub->fresh()->trialDaysRemaining());

        // Extend 10 days
        app(BillingService::class)->extendTrial($org, 10);

        $this->assertSame('trialing', $sub->fresh()->status);
        $this->assertSame(10, $sub->fresh()->trialDaysRemaining());
    }
}
