<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use App\Services\RegistrationService;
use Database\Seeders\BillingPermissionSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RegistrationSecurityTest extends TestCase
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

    public function test_client_cannot_tamper_with_organization_id_during_registration(): void
    {
        $victimOrg = Organization::factory()->create(['name' => 'Victim Organization']);

        $response = $this->withSession([
            'registration.organization' => [
                'name' => 'Attacker Org',
                'activity' => 'Hacking',
            ],
            'registration.admin' => [
                'first_name' => 'Attacker',
                'last_name' => 'User',
                'email' => 'attacker@test.com',
                'password' => 'secret1234',
                'password_confirmation' => 'secret1234',
            ],
        ])->post('/register/plan', [
            'plan' => 'professional',
            'billing_cycle' => 'monthly',
            'organization_id' => $victimOrg->id, // Malicious IDOR attempt
        ]);

        $response->assertRedirect(route('dashboard'));

        $attackerUser = User::where('email', 'attacker@test.com')->first();
        $this->assertNotNull($attackerUser);

        // Security assertion: user MUST NOT be attached to the victim organization
        $this->assertNotSame($victimOrg->id, $attackerUser->organization_id);

        $attackerOrg = Organization::where('name', 'Attacker Org')->first();
        $this->assertNotNull($attackerOrg);
        $this->assertSame($attackerOrg->id, $attackerUser->organization_id);
    }

    public function test_double_submission_with_same_email_is_blocked_atomically(): void
    {
        $orgData = [
            'name' => 'First Submission Corp',
        ];
        $adminData = [
            'first_name' => 'Jean',
            'last_name' => 'Kouassi',
            'email' => 'jean.duplicate@entreprise.test',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ];

        // First successful registration logs in the user
        $this->withSession([
            'registration.organization' => $orgData,
            'registration.admin' => $adminData,
        ])->post('/register/plan', [
            'plan' => 'essential',
            'billing_cycle' => 'monthly',
        ])->assertRedirect(route('dashboard'));

        // Logout so second visitor can attempt registration as guest
        Auth::logout();

        // Second registration attempt using the same email
        $this->withSession([
            'registration.organization' => [
                'name' => 'Second Submission Corp',
            ],
            'registration.admin' => $adminData,
        ])->post('/register/plan', [
            'plan' => 'essential',
            'billing_cycle' => 'monthly',
        ])->assertSessionHasErrors(['email']);

        // Verify that the second organization was not created
        $this->assertDatabaseMissing('organizations', ['name' => 'Second Submission Corp']);
    }

    public function test_transaction_rolls_back_completely_if_any_step_fails(): void
    {
        $initialOrgCount = Organization::count();
        $initialUserCount = User::count();
        $initialSubCount = Subscription::count();

        // Simulate an unexpected error by passing an invalid email directly to service with existing user
        User::factory()->create([
            'email' => 'conflict@test.com',
            'organization_id' => Organization::factory()->create()->id,
        ]);

        $service = app(RegistrationService::class);

        try {
            $service->register(
                orgData: ['name' => 'Failed Org Corp'],
                adminData: [
                    'first_name' => 'Failed',
                    'last_name' => 'Admin',
                    'email' => 'conflict@test.com', // Duplicate
                    'password' => 'secret1234',
                ],
                planSlug: 'essential',
                billingCycle: 'monthly'
            );
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            // Verify atomic rollback: Failed Org Corp must not exist
            $this->assertDatabaseMissing('organizations', ['name' => 'Failed Org Corp']);
        }
    }

    public function test_guest_cannot_directly_post_to_plan_without_session(): void
    {
        $response = $this->post('/register/plan', [
            'plan' => 'essential',
            'billing_cycle' => 'monthly',
        ]);

        $response->assertRedirect(route('register.organization'));
        $this->assertDatabaseMissing('organizations', ['name' => 'essential']);
    }
}
