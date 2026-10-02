<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\BillingPermissionSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RegistrationPlanTest extends TestCase
{
    use RefreshDatabase;

    protected array $validOrgSession = [
        'name' => 'Entreprise Test CI',
        'activity' => 'Technologies',
        'country' => "Côte d'Ivoire",
        'city' => 'Abidjan',
    ];

    protected array $validAdminSession = [
        'first_name' => 'Jean',
        'last_name' => 'Kouassi',
        'email' => 'jean.kouassi@entreprise.test',
        'phone' => '+225 07 00 00 00 00',
        'password' => 'secret1234',
        'password_confirmation' => 'secret1234',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            PlanSeeder::class,
            BillingPermissionSeeder::class,
        ]);
    }

    public function test_plan_step_redirects_if_session_missing_org(): void
    {
        $response = $this->withSession(['registration.admin' => $this->validAdminSession])
            ->get('/register/plan');

        $response->assertRedirect(route('register.organization'));
    }

    public function test_plan_step_redirects_if_session_missing_admin(): void
    {
        $response = $this->withSession(['registration.organization' => $this->validOrgSession])
            ->get('/register/plan');

        $response->assertRedirect(route('register.admin'));
    }

    public function test_plan_step_renders_successfully_with_plans(): void
    {
        $response = $this->withSession([
            'registration.organization' => $this->validOrgSession,
            'registration.admin' => $this->validAdminSession,
        ])->get('/register/plan');

        $response->assertOk();
    }

    public function test_successful_registration_with_professional_plan_creates_org_user_role_and_trial(): void
    {
        $response = $this->withSession([
            'registration.organization' => $this->validOrgSession,
            'registration.admin' => $this->validAdminSession,
        ])->post('/register/plan', [
            'plan' => 'professional',
            'billing_cycle' => 'monthly',
        ]);

        $response->assertRedirect(route('dashboard'));

        // Verify Organization created
        $org = Organization::where('name', 'Entreprise Test CI')->first();
        $this->assertNotNull($org);
        $this->assertSame('Technologies', $org->activity);
        $this->assertSame("Côte d'Ivoire", $org->country);
        $this->assertSame('Abidjan', $org->city);

        // Verify User created and attached to Organization
        $user = User::where('email', 'jean.kouassi@entreprise.test')->first();
        $this->assertNotNull($user);
        $this->assertSame($org->id, $user->organization_id);
        $this->assertSame('Jean', $user->first_name);
        $this->assertSame('Kouassi', $user->last_name);
        $this->assertSame('active', $user->status);

        // Verify Role 'admin' assigned under team
        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasPermissionTo('users.create'));
        $this->assertTrue($user->hasPermissionTo('documents.create'));
        $this->assertTrue($user->hasPermissionTo('billing.view'));
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        // Verify Subscription
        $subscription = Subscription::where('organization_id', $org->id)->first();
        $this->assertNotNull($subscription);
        $this->assertSame('trialing', $subscription->status);
        $this->assertSame('monthly', $subscription->billing_cycle);
        $this->assertSame('professional', $subscription->plan->slug);
        $this->assertNotNull($subscription->trial_starts_at);
        $this->assertNotNull($subscription->trial_ends_at);
        $this->assertSame(14, (int) round($subscription->trial_starts_at->floatDiffInDays($subscription->trial_ends_at)));

        // Verify Authenticated
        $this->assertTrue(Auth::guard('web')->check());
        $this->assertSame($user->id, Auth::guard('web')->id());

        // Verify Session has onboarding flash banner
        $response->assertSessionHas('welcome_onboarding');
        $onboarding = session('welcome_onboarding');
        $this->assertSame('Entreprise Test CI', $onboarding['organization_name']);
        $this->assertSame('Jean Kouassi', $onboarding['admin_name']);
        $this->assertSame('Professionnel', $onboarding['plan_name']);
        $this->assertSame(14, $onboarding['trial_days']);

        // Verify registration session is cleared
        $this->assertFalse(session()->has('registration.organization'));
        $this->assertFalse(session()->has('registration.admin'));
    }

    public function test_registration_with_essential_plan_and_annual_cycle(): void
    {
        $response = $this->withSession([
            'registration.organization' => $this->validOrgSession,
            'registration.admin' => $this->validAdminSession,
        ])->post('/register/plan', [
            'plan' => 'essential',
            'billing_cycle' => 'annual',
        ]);

        $response->assertRedirect(route('dashboard'));

        $org = Organization::where('name', 'Entreprise Test CI')->first();
        $this->assertNotNull($org);

        $subscription = Subscription::where('organization_id', $org->id)->first();
        $this->assertNotNull($subscription);
        $this->assertSame('essential', $subscription->plan->slug);
        $this->assertSame('annual', $subscription->billing_cycle);
        $this->assertSame('trialing', $subscription->status);
        $this->assertTrue($subscription->isTrial());
    }

    public function test_registration_with_enterprise_plan_creates_pending_subscription_and_displays_contact_message(): void
    {
        $response = $this->withSession([
            'registration.organization' => $this->validOrgSession,
            'registration.admin' => $this->validAdminSession,
        ])->post('/register/plan', [
            'plan' => 'enterprise',
            'billing_cycle' => 'annual',
        ]);

        $response->assertRedirect(route('dashboard'));

        $org = Organization::where('name', 'Entreprise Test CI')->first();
        $this->assertNotNull($org);

        $subscription = Subscription::where('organization_id', $org->id)->first();
        $this->assertNotNull($subscription);
        $this->assertSame('enterprise', $subscription->plan->slug);
        $this->assertSame('pending', $subscription->status);
        $this->assertFalse($subscription->auto_renew);
        $this->assertTrue($subscription->metadata['enterprise_quote_requested']);

        // Verify flash message for sales team contact
        $response->assertSessionHas('info', 'Notre équipe va vous contacter pour configurer votre offre Entreprise.');
    }

    public function test_plan_validation_requires_valid_plan_and_cycle(): void
    {
        $response = $this->withSession([
            'registration.organization' => $this->validOrgSession,
            'registration.admin' => $this->validAdminSession,
        ])->from('/register/plan')->post('/register/plan', [
            'plan' => 'invalid-plan',
            'billing_cycle' => 'bi-weekly',
        ]);

        $response->assertRedirect('/register/plan');
        $response->assertSessionHasErrors(['plan', 'billing_cycle']);
    }

    public function test_registration_succeeds_even_when_plans_table_initially_empty(): void
    {
        // Truncate plans to simulate empty production database
        \App\Models\Plan::query()->delete();
        $this->assertSame(0, \App\Models\Plan::count());

        $response = $this->withSession([
            'registration.organization' => $this->validOrgSession,
            'registration.admin' => $this->validAdminSession,
        ])->post('/register/plan', [
            'plan' => 'essential',
            'billing_cycle' => 'monthly',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertGreaterThan(0, \App\Models\Plan::count());
        $this->assertDatabaseHas('organizations', ['name' => 'Entreprise Test CI']);
    }
}
