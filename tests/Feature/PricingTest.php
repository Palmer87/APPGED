<?php

namespace Tests\Feature;

use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanSeeder::class);
    }

    public function test_pricing_page_is_publicly_accessible(): void
    {
        $response = $this->get('/pricing');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Pricing/Index')
            ->has('plans', 3)
            ->where('plans.0.slug', 'essential')
            ->where('plans.0.monthly_price', 19000)
            ->where('plans.0.annual_price', 190000)
            ->where('plans.0.annual_savings', 38000)
            ->where('plans.1.slug', 'professional')
            ->where('plans.1.monthly_price', 39000)
            ->where('plans.1.annual_price', 390000)
            ->where('plans.1.annual_savings', 78000)
            ->where('plans.2.slug', 'enterprise')
            ->where('plans.2.monthly_price', null)
        );
    }
}
