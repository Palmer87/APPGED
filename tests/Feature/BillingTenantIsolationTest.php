<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionUsageService;
use Database\Seeders\BillingPermissionSeeder;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BillingTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $orgA;

    protected Organization $orgB;

    protected User $adminA;

    protected User $adminB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanSeeder::class);

        $this->orgA = Organization::factory()->create(['name' => 'Tenant A']);
        $this->orgB = Organization::factory()->create(['name' => 'Tenant B']);

        $this->seed(RolePermissionSeeder::class);
        $this->seed(BillingPermissionSeeder::class);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $this->adminA = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->adminA->assignRole('admin');

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgB->id);
        $this->adminB = User::factory()->create(['organization_id' => $this->orgB->id]);
        $this->adminB->assignRole('admin');
    }

    public function test_tenant_a_cannot_see_tenant_b_subscription_or_invoices(): void
    {
        $subB = $this->orgB->currentSubscription;

        Invoice::create([
            'organization_id' => $this->orgB->id,
            'subscription_id' => $subB->id,
            'invoice_number' => 'INV-TENANT-B-SECRET',
            'amount' => 390000,
            'subtotal' => 390000,
            'total' => 390000,
            'currency' => 'XOF',
            'status' => 'paid',
        ]);

        // When Tenant A visits their subscription page
        $response = $this->actingAs($this->adminA)->get('/settings/subscription');
        $response->assertStatus(200);

        // Assert Tenant B's secret invoice is NOT passed to Tenant A's view
        $response->assertDontSee('INV-TENANT-B-SECRET');
    }

    public function test_tenant_a_modifications_do_not_affect_tenant_b(): void
    {
        $subB = $this->orgB->currentSubscription;
        $this->assertSame('essential', $subB->plan->slug);

        // Tenant A upgrades to Professional
        $this->actingAs($this->adminA)->post('/settings/subscription/change-plan', [
            'plan_slug' => 'professional',
            'billing_cycle' => 'annual',
        ]);

        $this->assertSame('professional', $this->orgA->fresh()->currentSubscription->plan->slug);

        // Tenant B remains untouched on Essential
        $this->assertSame('essential', $this->orgB->fresh()->currentSubscription->plan->slug);
        $this->assertSame('monthly', $this->orgB->fresh()->currentSubscription->billing_cycle);
    }

    public function test_usage_is_strictly_isolated_between_tenants(): void
    {
        $usageService = app(SubscriptionUsageService::class);

        // Create 4 users in Tenant A
        User::factory()->count(4)->create(['organization_id' => $this->orgA->id]);

        // Create 2 users in Tenant B
        User::factory()->count(2)->create(['organization_id' => $this->orgB->id]);

        $usageA = $usageService->getUsage($this->orgA);
        $usageB = $usageService->getUsage($this->orgB);

        // Tenant A: 1 (adminA) + 4 = 5 users
        $this->assertSame(5, $usageA['metrics']['users']['used']);

        // Tenant B: 1 (adminB) + 2 = 3 users
        $this->assertSame(3, $usageB['metrics']['users']['used']);
    }

    public function test_policy_blocks_cross_tenant_subscription_access(): void
    {
        $subB = $this->orgB->currentSubscription;

        // User A trying to view or manage Subscription B
        $this->assertFalse($this->adminA->can('view', $subB));
        $this->assertFalse($this->adminA->can('manage', $subB));
    }
}
