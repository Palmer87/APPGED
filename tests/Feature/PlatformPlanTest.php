<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\PlatformUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformPlanTest extends TestCase
{
    use RefreshDatabase;

    protected PlatformUser $owner;

    protected Plan $plan;

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

        $this->plan = Plan::create([
            'name' => 'Starter Test',
            'slug' => 'starter-test',
            'description' => 'Plan de test',
            'monthly_price' => 15000,
            'annual_price' => 150000,
            'currency' => 'XOF',
            'max_users' => 5,
            'max_storage_bytes' => 10 * 1024 * 1024 * 1024,
            'max_directions' => 2,
            'max_document_types' => 10,
            'max_ocr_pages_month' => 50,
            'is_active' => true,
        ]);
    }

    public function test_can_list_plans(): void
    {
        $response = $this->actingAs($this->owner, 'platform')
            ->get('/platform/plans');

        $response->assertOk();
    }

    public function test_can_create_a_new_plan(): void
    {
        $response = $this->actingAs($this->owner, 'platform')
            ->post('/platform/plans', [
                'name' => 'Ultimate Pro',
                'slug' => 'ultimate-pro',
                'description' => 'Le plan sans limites',
                'monthly_price' => 50000,
                'annual_price' => 500000,
                'currency' => 'XOF',
                'max_users' => 50,
                'max_storage_gb' => 200,
                'max_directions' => 20,
                'max_document_types' => 100,
                'max_ocr_pages_month' => 5000,
                'is_active' => true,
            ]);

        $response->assertRedirect('/platform/plans');
        $this->assertDatabaseHas('plans', [
            'slug' => 'ultimate-pro',
            'max_users' => 50,
            'monthly_price' => 50000,
        ]);
    }

    public function test_cannot_create_plan_with_duplicate_slug(): void
    {
        $response = $this->actingAs($this->owner, 'platform')
            ->post('/platform/plans', [
                'name' => 'Duplicate Plan',
                'slug' => 'starter-test', // duplicate
                'monthly_price' => 10000,
                'annual_price' => 100000,
                'currency' => 'XOF',
                'max_users' => 5,
                'max_storage_gb' => 10,
                'max_directions' => 2,
                'max_document_types' => 10,
                'max_ocr_pages_month' => 50,
            ]);

        $response->assertSessionHasErrors('slug');
    }

    public function test_can_update_an_existing_plan(): void
    {
        $response = $this->actingAs($this->owner, 'platform')
            ->put("/platform/plans/{$this->plan->id}", [
                'name' => 'Starter Updated',
                'slug' => 'starter-test',
                'description' => 'Updated description',
                'monthly_price' => 18000,
                'annual_price' => 180000,
                'currency' => 'XOF',
                'max_users' => 8,
                'max_storage_gb' => 15,
                'max_directions' => 3,
                'max_document_types' => 15,
                'max_ocr_pages_month' => 75,
                'is_active' => true,
            ]);

        $response->assertRedirect('/platform/plans');
        $this->plan->refresh();
        $this->assertEquals('Starter Updated', $this->plan->name);
        $this->assertEquals(18000, $this->plan->monthly_price);
        $this->assertEquals(8, $this->plan->max_users);
    }

    public function test_can_toggle_plan_active_status(): void
    {
        $this->assertTrue($this->plan->is_active);

        $response = $this->actingAs($this->owner, 'platform')
            ->post("/platform/plans/{$this->plan->id}/toggle-active");

        $response->assertRedirect();
        $this->plan->refresh();
        $this->assertFalse($this->plan->is_active);
    }
}
