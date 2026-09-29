<?php

namespace Tests\Feature;

use App\Models\Plan;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_seed_plans_and_verify_attributes(): void
    {
        $this->seed(PlanSeeder::class);

        $this->assertDatabaseCount('plans', 3);

        $essential = Plan::where('slug', 'essential')->first();
        $this->assertNotNull($essential);
        $this->assertSame('Essentiel', $essential->name);
        $this->assertSame(19000, $essential->monthly_price);
        $this->assertSame(190000, $essential->annual_price);
        $this->assertSame('XOF', $essential->currency);
        $this->assertSame(5, $essential->max_users);
        $this->assertSame(20 * 1024 * 1024 * 1024, $essential->max_storage_bytes);
        $this->assertSame(3, $essential->max_directions);
        $this->assertSame(15, $essential->max_document_types);
        $this->assertSame(100, $essential->max_ocr_pages_month);
        $this->assertFalse($essential->has_workflows);
        $this->assertFalse($essential->has_api);

        $professional = Plan::where('slug', 'professional')->first();
        $this->assertNotNull($professional);
        $this->assertSame('Professionnel', $professional->name);
        $this->assertSame(39000, $professional->monthly_price);
        $this->assertSame(390000, $professional->annual_price);
        $this->assertSame(20, $professional->max_users);
        $this->assertSame(100 * 1024 * 1024 * 1024, $professional->max_storage_bytes);
        $this->assertSame(10, $professional->max_directions);
        $this->assertSame(50, $professional->max_document_types);
        $this->assertSame(1000, $professional->max_ocr_pages_month);
        $this->assertTrue($professional->has_workflows);
        $this->assertTrue($professional->has_api);
        $this->assertTrue($professional->has_advanced_audit);

        $enterprise = Plan::where('slug', 'enterprise')->first();
        $this->assertNotNull($enterprise);
        $this->assertNull($enterprise->monthly_price);
        $this->assertNull($enterprise->annual_price);
        $this->assertNull($enterprise->max_users);
        $this->assertTrue($enterprise->isUnlimitedUsers());
        $this->assertTrue($enterprise->isUnlimitedStorage());
        $this->assertTrue($enterprise->isUnlimitedDirections());
        $this->assertTrue($enterprise->isUnlimitedDocumentTypes());
        $this->assertTrue($enterprise->isUnlimitedOcr());
        $this->assertTrue($enterprise->is_custom);
    }

    public function test_plan_seeder_is_idempotent(): void
    {
        $this->seed(PlanSeeder::class);
        $this->seed(PlanSeeder::class);

        $this->assertDatabaseCount('plans', 3);
    }

    public function test_calculates_annual_savings_correctly(): void
    {
        $this->seed(PlanSeeder::class);

        $essential = Plan::where('slug', 'essential')->first();
        // 19 000 * 12 = 228 000. 228 000 - 190 000 = 38 000
        $this->assertSame(38000, $essential->getAnnualSavings());

        $professional = Plan::where('slug', 'professional')->first();
        // 39 000 * 12 = 468 000. 468 000 - 390 000 = 78 000
        $this->assertSame(78000, $professional->getAnnualSavings());

        $enterprise = Plan::where('slug', 'enterprise')->first();
        $this->assertSame(0, $enterprise->getAnnualSavings());
    }
}
