<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PlatformUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected PlatformUser $owner;

    protected PlatformUser $admin;

    protected PlatformUser $support;

    protected PlatformUser $billing;

    protected User $tenantUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = PlatformUser::create([
            'name' => 'Owner',
            'email' => 'owner@platform.test',
            'password' => Hash::make('password'),
            'role' => 'platform_owner',
            'is_active' => true,
        ]);

        $this->admin = PlatformUser::create([
            'name' => 'Admin',
            'email' => 'admin@platform.test',
            'password' => Hash::make('password'),
            'role' => 'platform_admin',
            'is_active' => true,
        ]);

        $this->support = PlatformUser::create([
            'name' => 'Support',
            'email' => 'support@platform.test',
            'password' => Hash::make('password'),
            'role' => 'platform_support',
            'is_active' => true,
        ]);

        $this->billing = PlatformUser::create([
            'name' => 'Billing',
            'email' => 'billing@platform.test',
            'password' => Hash::make('password'),
            'role' => 'platform_billing',
            'is_active' => true,
        ]);

        $org = Organization::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
            'status' => 'active',
        ]);

        $this->tenantUser = User::factory()->create([
            'organization_id' => $org->id,
            'email' => 'tenant@acme.test',
        ]);
    }

    public function test_tenant_user_is_forbidden_from_accessing_platform_routes(): void
    {
        $response = $this->actingAs($this->tenantUser, 'web')
            ->get('/platform');

        // EnsurePlatformUser checks Auth::guard('platform')->check(), redirects to /platform/login
        $response->assertRedirect('/platform/login');
    }

    public function test_platform_owner_can_access_all_platform_sections(): void
    {
        $this->actingAs($this->owner, 'platform')
            ->get('/platform')
            ->assertOk();

        $this->actingAs($this->owner, 'platform')
            ->get('/platform/organizations')
            ->assertOk();

        $this->actingAs($this->owner, 'platform')
            ->get('/platform/plans')
            ->assertOk();

        $this->actingAs($this->owner, 'platform')
            ->get('/platform/subscriptions')
            ->assertOk();

        $this->actingAs($this->owner, 'platform')
            ->get('/platform/payments')
            ->assertOk();

        $this->actingAs($this->owner, 'platform')
            ->get('/platform/invoices')
            ->assertOk();

        $this->actingAs($this->owner, 'platform')
            ->get('/platform/support')
            ->assertOk();

        $this->actingAs($this->owner, 'platform')
            ->get('/platform/audit')
            ->assertOk();

        $this->actingAs($this->owner, 'platform')
            ->get('/platform/settings')
            ->assertOk();
    }

    public function test_platform_admin_can_access_admin_sections_but_not_settings(): void
    {
        $this->actingAs($this->admin, 'platform')
            ->get('/platform/organizations')
            ->assertOk();

        $this->actingAs($this->admin, 'platform')
            ->get('/platform/plans')
            ->assertOk();

        $this->actingAs($this->admin, 'platform')
            ->get('/platform/settings')
            ->assertForbidden();
    }

    public function test_platform_support_can_access_support_and_organizations_but_not_billing(): void
    {
        $this->actingAs($this->support, 'platform')
            ->get('/platform/support')
            ->assertOk();

        $this->actingAs($this->support, 'platform')
            ->get('/platform/organizations')
            ->assertOk();

        $this->actingAs($this->support, 'platform')
            ->get('/platform/plans/create')
            ->assertForbidden();

        $this->actingAs($this->support, 'platform')
            ->get('/platform/payments')
            ->assertForbidden();

        $this->actingAs($this->support, 'platform')
            ->get('/platform/invoices')
            ->assertForbidden();
    }

    public function test_platform_billing_can_access_billing_sections_but_not_support(): void
    {
        $this->actingAs($this->billing, 'platform')
            ->get('/platform/plans')
            ->assertOk();

        $this->actingAs($this->billing, 'platform')
            ->get('/platform/subscriptions')
            ->assertOk();

        $this->actingAs($this->billing, 'platform')
            ->get('/platform/payments')
            ->assertOk();

        $this->actingAs($this->billing, 'platform')
            ->get('/platform/invoices')
            ->assertOk();

        $this->actingAs($this->billing, 'platform')
            ->get('/platform/support')
            ->assertForbidden();
    }
}
