<?php

namespace Tests\Feature;

use Database\Seeders\BillingPermissionSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicLandingTest extends TestCase
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

    public function test_landing_page_is_accessible(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_tarifs_page_is_accessible_and_renders_active_plans(): void
    {
        $response = $this->get('/tarifs');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Pricing/Index')
            ->has('plans', 3)
        );
    }

    public function test_pricing_alias_redirects_or_renders_tarifs(): void
    {
        $response = $this->get('/pricing');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Pricing/Index'));
    }

    public function test_fonctionnalites_page_is_accessible(): void
    {
        $response = $this->get('/fonctionnalites');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Public/Features'));
    }

    public function test_contact_page_is_accessible(): void
    {
        $response = $this->get('/contact');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Public/Contact'));
    }

    public function test_contact_form_submission_success(): void
    {
        $response = $this->post('/contact', [
            'name' => 'Jean Martin',
            'email' => 'jean.martin@example.com',
            'subject' => 'Question générale',
            'message' => 'Bonjour, pouvez-vous me donner plus de détails sur vos offres ?',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('message');
    }

    public function test_contact_form_validation_errors(): void
    {
        $response = $this->post('/contact', [
            'name' => '',
            'email' => 'not-an-email',
            'message' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'message']);
    }
}
