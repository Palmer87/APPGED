<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthWebTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::factory()->create(['name' => 'Acme Test Corp']);

        $this->user = User::factory()->create([
            'organization_id' => $this->org->id,
            'email' => 'jean.dupont@acme.test',
            'password' => Hash::make('SecretPassword123!'),
            'status' => 'active',
        ]);
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Auth/Login')
        );
    }

    public function test_authenticated_user_visiting_login_redirects_to_dashboard(): void
    {
        $response = $this->actingAs($this->user)->get('/login');

        $response->assertRedirect('/dashboard');
    }

    public function test_user_can_authenticate_using_valid_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => 'jean.dupont@acme.test',
            'password' => 'SecretPassword123!',
        ]);

        $this->assertAuthenticatedAs($this->user);
        $response->assertRedirect('/dashboard');

        // Audit log created
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.login',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_user_can_authenticate_with_remember_me(): void
    {
        $response = $this->post('/login', [
            'email' => 'jean.dupont@acme.test',
            'password' => 'SecretPassword123!',
            'remember' => true,
        ]);

        $this->assertAuthenticatedAs($this->user);
        $response->assertRedirect('/dashboard');
    }

    public function test_user_cannot_authenticate_with_invalid_password(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'jean.dupont@acme.test',
            'password' => 'WrongPassword!',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
    }

    public function test_user_cannot_authenticate_with_nonexistent_email(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'inconnu@acme.test',
            'password' => 'SecretPassword123!',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
    }

    public function test_inactive_user_cannot_authenticate(): void
    {
        $inactiveUser = User::factory()->create([
            'organization_id' => $this->org->id,
            'email' => 'inactif@acme.test',
            'password' => Hash::make('SecretPassword123!'),
            'status' => 'inactive',
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'inactif@acme.test',
            'password' => 'SecretPassword123!',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
    }

    public function test_user_can_logout(): void
    {
        $response = $this->actingAs($this->user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHas('success');
    }
}
