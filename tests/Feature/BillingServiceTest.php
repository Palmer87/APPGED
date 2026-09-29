<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use App\Services\BillingService;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BillingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BillingService $billingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanSeeder::class);
        $this->billingService = app(BillingService::class);
    }

    public function test_get_current_subscription_and_plan(): void
    {
        $org = Organization::factory()->create();

        $subscription = $this->billingService->getCurrentSubscription($org);
        $this->assertNotNull($subscription);
        $this->assertSame($org->id, $subscription->organization_id);

        $plan = $this->billingService->getCurrentPlan($org);
        $this->assertNotNull($plan);
        $this->assertSame('essential', $plan->slug);
    }

    public function test_change_plan_upgrades_subscription_and_creates_invoice_and_audits(): void
    {
        Notification::fake();

        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $professional = Plan::where('slug', 'professional')->firstOrFail();

        $updated = $this->billingService->changePlan($org, $professional, 'annual', $user);

        $this->assertSame('professional', $updated->plan->slug);
        $this->assertSame('annual', $updated->billing_cycle);
        $this->assertSame('active', $updated->status);
        $this->assertTrue($updated->auto_renew);

        // Verify invoice was created for 390 000 FCFA
        $this->assertDatabaseHas('invoices', [
            'organization_id' => $org->id,
            'subscription_id' => $updated->id,
            'amount' => 390000,
            'status' => 'paid',
        ]);

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $org->id,
            'action' => 'subscription.plan_changed',
        ]);
    }

    public function test_cancel_and_resume_subscription(): void
    {
        Notification::fake();

        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);

        // Cancel
        $cancelled = $this->billingService->cancelSubscription($org, false, $user);
        $this->assertFalse($cancelled->auto_renew);
        $this->assertNotNull($cancelled->cancelled_at);

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $org->id,
            'action' => 'subscription.cancelled',
        ]);

        // Resume
        $resumed = $this->billingService->resumeSubscription($org, $user);
        $this->assertTrue($resumed->auto_renew);
        $this->assertNull($resumed->cancelled_at);
        $this->assertSame('active', $resumed->status);

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $org->id,
            'action' => 'subscription.resumed',
        ]);
    }

    public function test_extend_trial(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);

        $extended = $this->billingService->extendTrial($org, 7, $user);

        $this->assertSame('trialing', $extended->status);
        $this->assertGreaterThan(14, $extended->trialDaysRemaining());

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $org->id,
            'action' => 'subscription.trial_extended',
        ]);
    }

    public function test_check_and_handle_expiration_marks_status_as_expired(): void
    {
        Notification::fake();

        $org = Organization::factory()->create();
        $subscription = $org->currentSubscription;
        $subscription->update([
            'status' => 'trialing',
            'trial_ends_at' => now()->subDay(),
        ]);

        $handled = $this->billingService->checkAndHandleExpiration($org);

        $this->assertSame('expired', $handled->status);
        $this->assertNotNull($handled->ended_at);

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $org->id,
            'action' => 'subscription.expired',
        ]);
    }
}
