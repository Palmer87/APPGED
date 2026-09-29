<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationAdminTest extends TestCase
{
    use RefreshDatabase;

    protected array $validOrgSession = [
        'name' => 'Entreprise Test CI',
        'activity' => 'Technologies',
        'country' => "Côte d'Ivoire",
        'city' => 'Abidjan',
    ];

    public function test_admin_step_redirects_to_organization_if_no_org_in_session(): void
    {
        $response = $this->get('/register/admin');

        $response->assertRedirect(route('register.organization'));
        $response->assertSessionHas('error');
    }

    public function test_admin_step_renders_successfully_when_organization_is_in_session(): void
    {
        $response = $this->withSession(['registration.organization' => $this->validOrgSession])
            ->get('/register/admin');

        $response->assertOk();
    }

    public function test_visitor_can_submit_valid_admin_data_and_is_redirected_to_plan_step(): void
    {
        $response = $this->withSession(['registration.organization' => $this->validOrgSession])
            ->post('/register/admin', [
                'first_name' => 'Jean',
                'last_name' => 'Kouassi',
                'email' => 'jean.kouassi@entreprise.test',
                'phone' => '+225 07 00 00 00 00',
                'password' => 'secret1234',
                'password_confirmation' => 'secret1234',
            ]);

        $response->assertRedirect(route('register.plan'));
        $response->assertSessionHas('registration.admin');

        $sessionAdmin = session('registration.admin');
        $this->assertSame('Jean', $sessionAdmin['first_name']);
        $this->assertSame('Kouassi', $sessionAdmin['last_name']);
        $this->assertSame('jean.kouassi@entreprise.test', $sessionAdmin['email']);
    }

    public function test_first_name_and_last_name_are_required(): void
    {
        $response = $this->withSession(['registration.organization' => $this->validOrgSession])
            ->from('/register/admin')
            ->post('/register/admin', [
                'first_name' => '',
                'last_name' => '',
                'email' => 'jean@test.com',
                'password' => 'secret1234',
                'password_confirmation' => 'secret1234',
            ]);

        $response->assertRedirect('/register/admin');
        $response->assertSessionHasErrors(['first_name', 'last_name']);
    }

    public function test_email_is_required_and_must_be_valid(): void
    {
        $response = $this->withSession(['registration.organization' => $this->validOrgSession])
            ->from('/register/admin')
            ->post('/register/admin', [
                'first_name' => 'Jean',
                'last_name' => 'Kouassi',
                'email' => 'not-an-email',
                'password' => 'secret1234',
                'password_confirmation' => 'secret1234',
            ]);

        $response->assertRedirect('/register/admin');
        $response->assertSessionHasErrors(['email']);
    }

    public function test_email_must_be_unique_against_existing_users(): void
    {
        $org = Organization::factory()->create();
        User::factory()->create([
            'organization_id' => $org->id,
            'email' => 'existing.user@entreprise.test',
        ]);

        $response = $this->withSession(['registration.organization' => $this->validOrgSession])
            ->from('/register/admin')
            ->post('/register/admin', [
                'first_name' => 'Jean',
                'last_name' => 'Kouassi',
                'email' => 'existing.user@entreprise.test',
                'password' => 'secret1234',
                'password_confirmation' => 'secret1234',
            ]);

        $response->assertRedirect('/register/admin');
        $response->assertSessionHasErrors(['email']);
    }

    public function test_password_is_required_and_must_be_at_least_8_characters(): void
    {
        $response = $this->withSession(['registration.organization' => $this->validOrgSession])
            ->from('/register/admin')
            ->post('/register/admin', [
                'first_name' => 'Jean',
                'last_name' => 'Kouassi',
                'email' => 'jean@test.com',
                'password' => 'short',
                'password_confirmation' => 'short',
            ]);

        $response->assertRedirect('/register/admin');
        $response->assertSessionHasErrors(['password']);
    }

    public function test_password_must_be_confirmed(): void
    {
        $response = $this->withSession(['registration.organization' => $this->validOrgSession])
            ->from('/register/admin')
            ->post('/register/admin', [
                'first_name' => 'Jean',
                'last_name' => 'Kouassi',
                'email' => 'jean@test.com',
                'password' => 'secret1234',
                'password_confirmation' => 'different-password',
            ]);

        $response->assertRedirect('/register/admin');
        $response->assertSessionHasErrors(['password']);
    }

    public function test_cannot_post_admin_step_without_organization_in_session(): void
    {
        $response = $this->post('/register/admin', [
            'first_name' => 'Jean',
            'last_name' => 'Kouassi',
            'email' => 'jean@test.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ]);

        $response->assertRedirect(route('register.organization'));
    }
}
