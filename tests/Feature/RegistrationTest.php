<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\BillingPermissionSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class RegistrationTest extends TestCase
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

    public function test_inscription_step1_account_page_is_accessible(): void
    {
        $response = $this->get('/inscription');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Auth/RegisterAccount'));
    }

    public function test_inscription_step1_account_validation_errors(): void
    {
        $response = $this->post('/inscription', [
            'first_name' => '',
            'last_name' => '',
            'email' => 'invalid-email',
            'password' => 'short',
            'password_confirmation' => 'mismatch',
        ]);

        $response->assertSessionHasErrors(['first_name', 'last_name', 'email', 'password']);
    }

    public function test_inscription_step1_stores_account_in_session_and_redirects_to_organisation(): void
    {
        $response = $this->post('/inscription', [
            'first_name' => 'Marc',
            'last_name' => 'Amon',
            'email' => 'marc.amon@example.com',
            'phone' => '+225 07 12 34 56 78',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/inscription/organisation');
        $this->assertTrue(session()->has('registration.admin'));
        $this->assertSame('marc.amon@example.com', session('registration.admin.email'));
    }

    public function test_inscription_step2_organisation_requires_account_in_session(): void
    {
        $response = $this->get('/inscription/organisation');
        $response->assertRedirect('/inscription');
    }

    public function test_inscription_step2_stores_organisation_and_redirects_to_plan(): void
    {
        $response = $this->withSession([
            'registration.admin' => [
                'first_name' => 'Marc',
                'last_name' => 'Amon',
                'email' => 'marc.amon@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ],
        ])->post('/inscription/organisation', [
            'name' => 'Amon Consulting SARL',
            'activity' => 'Conseil & Audit',
            'country' => "Côte d'Ivoire",
            'city' => 'Abidjan',
        ]);

        $response->assertRedirect('/inscription/plan');
        $this->assertTrue(session()->has('registration.organization'));
        $this->assertSame('Amon Consulting SARL', session('registration.organization.name'));
    }

    public function test_inscription_step3_plan_page_renders_active_plans(): void
    {
        $response = $this->withSession([
            'registration.admin' => [
                'first_name' => 'Marc',
                'last_name' => 'Amon',
                'email' => 'marc.amon@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ],
            'registration.organization' => [
                'name' => 'Amon Consulting SARL',
            ],
        ])->get('/inscription/plan');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/RegisterPlan')
            ->has('plans', 3)
        );
    }

    public function test_full_inscription_flow_completes_atomically(): void
    {
        // 1. Account
        $this->post('/inscription', [
            'first_name' => 'Kouamé',
            'last_name' => 'N\'Goran',
            'email' => 'kouame.ngoran@ivoiredoc.ci',
            'phone' => '+225 01 02 03 04 05',
            'password' => 'SecurePass1234!',
            'password_confirmation' => 'SecurePass1234!',
        ])->assertRedirect('/inscription/organisation');

        // 2. Organisation
        $this->post('/inscription/organisation', [
            'name' => 'IvoireDoc Distribution',
            'activity' => 'Distribution B2B',
            'country' => "Côte d'Ivoire",
            'city' => 'Yamoussoukro',
        ])->assertRedirect('/inscription/plan');

        // 3. Plan (Essential)
        $planResponse = $this->post('/inscription/plan', [
            'plan' => 'essential',
            'billing_cycle' => 'monthly',
        ]);

        $planResponse->assertRedirect('/dashboard');

        // User authenticated
        $this->assertTrue(Auth::check());
        $user = Auth::user();
        $this->assertSame('kouame.ngoran@ivoiredoc.ci', $user->email);

        // Organization created
        $org = Organization::where('name', 'IvoireDoc Distribution')->first();
        $this->assertNotNull($org);
        $this->assertSame($org->id, $user->organization_id);

        // Admin role assigned
        $this->assertTrue($user->hasRole('admin'));

        // Subscription created with trial
        $subscription = Subscription::where('organization_id', $org->id)->first();
        $this->assertNotNull($subscription);
        $this->assertSame('trialing', $subscription->status);
        $this->assertNotNull($subscription->trial_ends_at);
        $this->assertTrue($subscription->trial_ends_at->isFuture());
    }

    public function test_choosing_enterprise_plan_redirects_to_enterprise_page(): void
    {
        $response = $this->withSession([
            'registration.admin' => [
                'first_name' => 'Marc',
                'last_name' => 'Amon',
                'email' => 'marc.amon@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ],
            'registration.organization' => [
                'name' => 'Amon Enterprise',
            ],
        ])->post('/inscription/plan', [
            'plan' => 'enterprise',
        ]);

        $response->assertRedirect('/enterprise');
        $this->assertDatabaseMissing('organizations', ['name' => 'Amon Enterprise']);
    }
}
