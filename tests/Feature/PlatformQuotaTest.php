<?php

namespace Tests\Feature;

use App\Models\Direction;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\QuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformQuotaTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;

    protected Plan $plan;

    protected Subscription $subscription;

    protected QuotaService $quotaService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->quotaService = app(QuotaService::class);

        $this->org = Organization::create([
            'name' => 'Quota Corp',
            'slug' => 'quota-corp',
            'status' => 'active',
        ]);

        $this->plan = Plan::firstOrCreate(
            ['slug' => 'quota-plan'],
            [
                'name' => 'Quota Plan',
                'monthly_price' => 10000,
                'annual_price' => 100000,
                'currency' => 'XOF',
                'max_users' => 10,
                'max_storage_bytes' => 100 * 1024 * 1024, // 100 MB
                'max_directions' => 5,
                'max_document_types' => 10,
                'max_ocr_pages_month' => 100,
                'is_active' => true,
            ]
        );

        $this->subscription = Subscription::create([
            'organization_id' => $this->org->id,
            'plan_id' => $this->plan->id,
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'starts_at' => now(),
            'current_period_starts_at' => now(),
            'current_period_ends_at' => now()->addMonth(),
        ]);
    }

    public function test_calculates_organization_quotas_accurately(): void
    {
        // Create 2 users
        User::factory()->count(2)->create(['organization_id' => $this->org->id]);

        // Create 1 direction
        Direction::create([
            'organization_id' => $this->org->id,
            'name' => 'Direction Technique',
            'code' => 'DT',
        ]);

        $quotas = $this->quotaService->getOrganizationQuotas($this->org);

        $this->assertEquals(2, $quotas['metrics']['users']['used']);
        $this->assertEquals(10, $quotas['metrics']['users']['limit']);
        $this->assertEquals(20, $quotas['metrics']['users']['percentage']);

        $this->assertEquals(1, $quotas['metrics']['directions']['used']);
        $this->assertEquals(5, $quotas['metrics']['directions']['limit']);
        $this->assertEquals(20, $quotas['metrics']['directions']['percentage']);
    }

    public function test_detects_warning_and_exceeded_quota_alerts(): void
    {
        // Create 8 users => 80% (info alert in QuotaService)
        User::factory()->count(8)->create(['organization_id' => $this->org->id]);

        $quotas = $this->quotaService->getOrganizationQuotas($this->org);
        $this->assertNotEmpty($quotas['alerts']);
        $this->assertEquals('info', $quotas['alerts'][0]['level']);
        $this->assertEquals(80, $quotas['alerts'][0]['percentage']);

        // Create 1 more user => 9 users / 10 => 90% (warning alert)
        User::factory()->create(['organization_id' => $this->org->id]);

        $quotas = $this->quotaService->getOrganizationQuotas($this->org);
        $this->assertEquals('warning', $quotas['alerts'][0]['level']);
        $this->assertEquals(90, $quotas['alerts'][0]['percentage']);

        // Create 1 more user => 10 users / 10 => 100% (danger alert)
        User::factory()->create(['organization_id' => $this->org->id]);

        $quotas = $this->quotaService->getOrganizationQuotas($this->org);
        $this->assertEquals('danger', $quotas['alerts'][0]['level']);
        $this->assertEquals(100, $quotas['alerts'][0]['percentage']);
    }

    public function test_aggregates_global_metrics(): void
    {
        User::factory()->count(3)->create(['organization_id' => $this->org->id]);

        $metrics = $this->quotaService->getGlobalUsageMetrics();

        $this->assertGreaterThanOrEqual(1, $metrics['organizations']['total']);
        $this->assertGreaterThanOrEqual(3, $metrics['users']['total']);
        $this->assertArrayHasKey('total_formatted', $metrics['storage']);
    }
}
