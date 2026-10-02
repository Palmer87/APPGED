<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PlatformUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_platform_login_when_accessing_platform_dashboard(): void
    {
        $response = $this->get('/platform');

        $response->assertRedirect('/platform/login');
    }

    public function test_guest_can_view_platform_login_page(): void
    {
        $response = $this->get('/platform/login');

        $response->assertStatus(200);
    }

    public function test_platform_user_can_authenticate_via_platform_login(): void
    {
        $platformUser = PlatformUser::create([
            'name' => 'Owner SaaS',
            'email' => 'owner@gedapp.com',
            'password' => Hash::make('Password123!'),
            'role' => 'platform_owner',
            'is_active' => true,
        ]);

        $response = $this->post('/platform/login', [
            'email' => 'owner@gedapp.com',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect('/platform');
        $this->assertTrue(Auth::guard('platform')->check());
        $this->assertEquals($platformUser->id, Auth::guard('platform')->id());
    }

    public function test_inactive_platform_user_cannot_authenticate(): void
    {
        PlatformUser::create([
            'name' => 'Disabled Admin',
            'email' => 'inactive@gedapp.com',
            'password' => Hash::make('Password123!'),
            'role' => 'platform_admin',
            'is_active' => false,
        ]);

        $response = $this->post('/platform/login', [
            'email' => 'inactive@gedapp.com',
            'password' => 'Password123!',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(Auth::guard('platform')->check());
    }

    public function test_tenant_user_cannot_login_as_platform_user(): void
    {
        $org = Organization::create([
            'name' => 'Client Org',
            'slug' => 'client-org',
            'status' => 'active',
        ]);

        User::factory()->create([
            'organization_id' => $org->id,
            'email' => 'tenant@client.com',
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->post('/platform/login', [
            'email' => 'tenant@client.com',
            'password' => 'Password123!',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(Auth::guard('platform')->check());
    }

    public function test_platform_user_can_logout(): void
    {
        $platformUser = PlatformUser::create([
            'name' => 'Owner SaaS',
            'email' => 'owner@gedapp.com',
            'password' => Hash::make('Password123!'),
            'role' => 'platform_owner',
            'is_active' => true,
        ]);

        $response = $this->actingAs($platformUser, 'platform')
            ->post('/platform/logout');

        $response->assertRedirect('/platform/login');
        $this->assertFalse(Auth::guard('platform')->check());
    }
}
