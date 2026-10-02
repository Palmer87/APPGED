<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use Database\Seeders\BillingPermissionSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TrialSignupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            PlanSeeder::class,
            BillingPermissionSeeder::class,
        ]);
    }

    public function test_trial_is_created_for_14_days_on_essential_plan(): void
    {
        Carbon::setTestNow('2026-10-02 12:00:00');

        $this->withSession([
            'registration.admin' => [
                'first_name' => 'Paul',
                'last_name' => 'Trial',
                'email' => 'paul.trial@example.com',
                'password' => 'secret1234',
                'password_confirmation' => 'secret1234',
            ],
            'registration.organization' => [
                'name' => 'Trial Org Essential',
            ],
        ])->post('/inscription/plan', [
            'plan' => 'essential',
            'billing_cycle' => 'monthly',
        ])->assertRedirect('/dashboard');

        $org = Organization::where('name', 'Trial Org Essential')->first();
        $this->assertNotNull($org);

        $subscription = Subscription::where('organization_id', $org->id)->first();
        $this->assertNotNull($subscription);
        $this->assertSame('trialing', $subscription->status);
        $this->assertSame(
            Carbon::parse('2026-10-02 12:00:00')->addDays(14)->toDateTimeString(),
            $subscription->trial_ends_at->toDateTimeString()
        );

        // Check plan limits
        $plan = $subscription->plan;
        $this->assertSame('essential', $plan->slug);
        $this->assertSame(5, $plan->max_users);
        $this->assertSame(3, $plan->max_directions);
        $this->assertSame(15, $plan->max_document_types);
        $this->assertSame(100, $plan->max_ocr_pages_month);

        Carbon::setTestNow();
    }

    public function test_trial_is_created_for_14_days_on_professional_plan(): void
    {
        Carbon::setTestNow('2026-10-02 12:00:00');

        $this->withSession([
            'registration.admin' => [
                'first_name' => 'Sophie',
                'last_name' => 'Pro',
                'email' => 'sophie.pro@example.com',
                'password' => 'secret1234',
                'password_confirmation' => 'secret1234',
            ],
            'registration.organization' => [
                'name' => 'Trial Org Pro',
            ],
        ])->post('/inscription/plan', [
            'plan' => 'professional',
            'billing_cycle' => 'annual',
        ])->assertRedirect('/dashboard');

        $org = Organization::where('name', 'Trial Org Pro')->first();
        $this->assertNotNull($org);

        $subscription = Subscription::where('organization_id', $org->id)->first();
        $this->assertNotNull($subscription);
        $this->assertSame('trialing', $subscription->status);
        $this->assertSame(
            Carbon::parse('2026-10-02 12:00:00')->addDays(14)->toDateTimeString(),
            $subscription->trial_ends_at->toDateTimeString()
        );

        $plan = $subscription->plan;
        $this->assertSame('professional', $plan->slug);
        $this->assertSame(20, $plan->max_users);
        $this->assertSame(10, $plan->max_directions);
        $this->assertSame(50, $plan->max_document_types);
        $this->assertSame(1000, $plan->max_ocr_pages_month);

        Carbon::setTestNow();
    }
}
