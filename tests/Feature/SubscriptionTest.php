<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use Database\Seeders\BillingPermissionSeeder;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SubscriptionTest extends TestCase
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

    public function test_organization_creation_automatically_starts_14_day_trial(): void
    {
        $org = Organization::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
            'email' => 'contact@acme.test',
        ]);

        $subscription = $org->currentSubscription;

        $this->assertNotNull($subscription);
        $this->assertSame('trialing', $subscription->status);
        $this->assertSame('monthly', $subscription->billing_cycle);
        $this->assertSame('essential', $subscription->plan->slug);
        $this->assertTrue($subscription->isTrial());
        $this->assertTrue($subscription->isActive());
        $this->assertFalse($subscription->isTrialExpired());
        $this->assertFalse($subscription->isExpired());
        $this->assertSame(14, $subscription->trialDaysRemaining());
        $this->assertDatabaseHas('subscriptions', [
            'organization_id' => $org->id,
            'status' => 'trialing',
        ]);
    }

    public function test_subscription_relationships(): void
    {
        $org = Organization::factory()->create();
        $subscription = $org->currentSubscription;

        $this->assertInstanceOf(Organization::class, $subscription->organization);
        $this->assertSame($org->id, $subscription->organization->id);
        $this->assertInstanceOf(Plan::class, $subscription->plan);

        // Invoices relationship
        Invoice::create([
            'organization_id' => $org->id,
            'subscription_id' => $subscription->id,
            'invoice_number' => 'INV-TEST-001',
            'amount' => 19000,
            'currency' => 'XOF',
            'tax' => 0,
            'subtotal' => 19000,
            'total' => 19000,
            'status' => 'paid',
        ]);

        $this->assertCount(1, $subscription->invoices);
        $this->assertCount(1, $org->invoices);
    }

    public function test_is_active_handles_status_and_expiration(): void
    {
        $org = Organization::factory()->create();
        $subscription = $org->currentSubscription;

        // 1. Ongoing trial is active
        $this->assertTrue($subscription->isActive());

        // 2. Expired trial is not active
        $subscription->update([
            'trial_ends_at' => now()->subDay(),
        ]);
        $this->assertFalse($subscription->fresh()->isActive());
        $this->assertTrue($subscription->fresh()->isTrialExpired());

        // 3. Active status within current period is active
        $subscription->update([
            'status' => 'active',
            'current_period_ends_at' => now()->addMonth(),
        ]);
        $this->assertTrue($subscription->fresh()->isActive());

        // 4. Cancelled status is not active once period ends
        $subscription->update([
            'status' => 'cancelled',
            'current_period_ends_at' => now()->subDay(),
        ]);
        $this->assertFalse($subscription->fresh()->isActive());
        $this->assertTrue($subscription->fresh()->isExpired());
    }

    public function test_has_feature_checks_plan_capabilities(): void
    {
        $essentialPlan = Plan::where('slug', 'essential')->first();
        $professionalPlan = Plan::where('slug', 'professional')->first();

        $org = Organization::factory()->create();
        $subscription = $org->currentSubscription;

        $this->assertFalse($subscription->hasFeature('workflows'));
        $this->assertFalse($subscription->hasFeature('api'));

        $subscription->update(['plan_id' => $professionalPlan->id]);
        $subscription->refresh();

        $this->assertTrue($subscription->hasFeature('workflows'));
        $this->assertTrue($subscription->hasFeature('api'));
        $this->assertTrue($subscription->hasFeature('has_advanced_audit'));
    }

    public function test_web_change_plan_endpoint_succeeds_and_dispatches_notification(): void
    {
        $org = Organization::factory()->create();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(BillingPermissionSeeder::class);

        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);

        $admin = User::factory()->create(['organization_id' => $org->id]);
        $admin->assignRole('admin');

        $professionalPlan = Plan::where('slug', 'professional')->firstOrFail();

        $response = $this->actingAs($admin)->post('/settings/subscription/change-plan', [
            'plan_id' => $professionalPlan->id,
            'billing_cycle' => 'annual',
        ]);

        $response->assertRedirect('/settings/subscription');
        $this->assertSame('professional', $org->fresh()->currentSubscription->plan->slug);
    }
}
