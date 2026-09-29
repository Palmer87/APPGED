<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationOrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_root_redirects_to_register_organization(): void
    {
        $response = $this->get('/register');

        $response->assertRedirect(route('register.organization'));
    }

    public function test_registration_organization_page_can_be_rendered(): void
    {
        $response = $this->get('/register/organization');

        $response->assertOk();
    }

    public function test_visitor_can_submit_valid_organization_and_is_redirected_to_admin_step(): void
    {
        $response = $this->post('/register/organization', [
            'name' => 'Entreprise Test CI',
            'activity' => 'Technologies',
            'country' => "Côte d'Ivoire",
            'city' => 'Abidjan',
        ]);

        $response->assertRedirect(route('register.admin'));
        $response->assertSessionHas('registration.organization');

        $sessionData = session('registration.organization');
        $this->assertSame('Entreprise Test CI', $sessionData['name']);
        $this->assertSame('Technologies', $sessionData['activity']);
        $this->assertSame("Côte d'Ivoire", $sessionData['country']);
        $this->assertSame('Abidjan', $sessionData['city']);
    }

    public function test_organization_name_is_required(): void
    {
        $response = $this->from('/register/organization')->post('/register/organization', [
            'name' => '',
        ]);

        $response->assertRedirect('/register/organization');
        $response->assertSessionHasErrors(['name']);
    }

    public function test_organization_name_must_be_at_least_two_characters(): void
    {
        $response = $this->from('/register/organization')->post('/register/organization', [
            'name' => 'A',
        ]);

        $response->assertRedirect('/register/organization');
        $response->assertSessionHasErrors(['name']);
    }

    public function test_organization_name_cannot_exceed_max_length(): void
    {
        $response = $this->from('/register/organization')->post('/register/organization', [
            'name' => str_repeat('A', 101),
        ]);

        $response->assertRedirect('/register/organization');
        $response->assertSessionHasErrors(['name']);
    }

    public function test_organization_name_must_be_unique_against_existing_organizations(): void
    {
        Organization::factory()->create(['name' => 'Cabinet Alpha']);

        $response = $this->from('/register/organization')->post('/register/organization', [
            'name' => 'Cabinet Alpha',
        ]);

        $response->assertRedirect('/register/organization');
        $response->assertSessionHasErrors(['name']);
    }

    public function test_authenticated_user_cannot_access_registration(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);

        $response = $this->actingAs($user)->get('/register/organization');

        $response->assertRedirect(route('dashboard'));
    }
}
