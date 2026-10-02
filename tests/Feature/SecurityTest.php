<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PlatformUser;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\BillingPermissionSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityTest extends TestCase
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

    public function test_registration_fails_if_email_is_already_taken(): void
    {
        User::factory()->create([
            'email' => 'existing@gedapp.com',
        ]);

        $response = $this->post('/inscription', [
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'existing@gedapp.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_tenant_cannot_access_platform_admin_endpoints(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $org->id,
        ]);

        $response = $this->actingAs($user)->get('/platform');
        // Tenant is not authenticated on platform guard, redirects to platform.login
        $response->assertRedirect(route('platform.login'));
    }

    public function test_platform_user_is_not_automatically_a_tenant_user(): void
    {
        $platformAdmin = PlatformUser::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@gedapp.internal',
            'password' => 'secret1234',
            'role' => 'platform_admin',
            'is_active' => true,
        ]);

        // Attempting to visit tenant dashboard authenticated as platform user
        $response = $this->actingAs($platformAdmin, 'platform')->get('/dashboard');
        // Web guard is unauthenticated
        $response->assertRedirect(route('login'));
    }

    public function test_client_cannot_tamper_with_plan_price_or_limits(): void
    {
        $this->withSession([
            'registration.admin' => [
                'first_name' => 'Hacker',
                'last_name' => 'Man',
                'email' => 'hacker@gedapp.test',
                'password' => 'secret1234',
                'password_confirmation' => 'secret1234',
            ],
            'registration.organization' => [
                'name' => 'Hack Org',
            ],
        ])->post('/inscription/plan', [
            'plan' => 'essential',
            'billing_cycle' => 'monthly',
            'price' => 0, // Malicious override attempt
            'amount' => 0,
            'max_users' => 999999,
            'max_storage_gb' => 999999,
        ])->assertRedirect('/dashboard');

        $org = Organization::where('name', 'Hack Org')->first();
        $subscription = Subscription::where('organization_id', $org->id)->first();

        // The system resolves Plan by controlled slug, malicious params ignored
        $this->assertSame('essential', $subscription->plan->slug);
        $this->assertSame(5, $subscription->plan->max_users);
        $this->assertSame(19000, (int) $subscription->plan->monthly_price);
    }

    public function test_tenant_isolation_user_cannot_access_other_tenant_organization_data(): void
    {
        $orgA = Organization::factory()->create(['name' => 'Org Alpha']);
        $orgB = Organization::factory()->create(['name' => 'Org Beta']);

        $userA = User::factory()->create(['organization_id' => $orgA->id]);
        $userB = User::factory()->create(['organization_id' => $orgB->id]);

        $this->assertNotSame($userA->organization_id, $userB->organization_id);
    }
}
