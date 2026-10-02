<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\BillingPermissionSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingTest extends TestCase
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

    public function test_dashboard_renders_onboarding_for_fresh_organization(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $org->id,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard/Index')
            ->where('onboarding_dismissed', false)
        );
    }

    public function test_user_can_dismiss_onboarding_checklist(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $org->id,
        ]);

        $response = $this->actingAs($user)->post('/dashboard/onboarding/dismiss');

        $response->assertRedirect();
        $response->assertSessionHas('onboarding_dismissed', true);

        // Subsequent dashboard visit has onboarding_dismissed true
        $dashResponse = $this->actingAs($user)->get('/dashboard');
        $dashResponse->assertInertia(fn ($page) => $page
            ->component('Dashboard/Index')
            ->where('onboarding_dismissed', true)
        );
    }

    public function test_user_can_dismiss_onboarding_via_json(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $org->id,
        ]);

        $response = $this->actingAs($user)->postJson('/dashboard/onboarding/dismiss');

        $response->assertStatus(200);
        $response->assertJson(['dismissed' => true]);
    }
}
