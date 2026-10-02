<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PlatformUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformOrganizationTest extends TestCase
{
    use RefreshDatabase;

    protected PlatformUser $owner;

    protected Organization $org;

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

        $this->org = Organization::create([
            'name' => 'Beta Industries',
            'slug' => 'beta-industries',
            'status' => 'active',
        ]);

        $this->tenantUser = User::factory()->create([
            'organization_id' => $this->org->id,
            'email' => 'user@beta.test',
        ]);
    }

    public function test_can_list_organizations(): void
    {
        $response = $this->actingAs($this->owner, 'platform')
            ->get('/platform/organizations');

        $response->assertOk();
    }

    public function test_can_view_organization_details(): void
    {
        $response = $this->actingAs($this->owner, 'platform')
            ->get("/platform/organizations/{$this->org->id}");

        $response->assertOk();
    }

    public function test_can_suspend_organization_and_tenant_users_are_blocked(): void
    {
        // 1. Tenant user can access dashboard before suspension
        $this->actingAs($this->tenantUser, 'web')
            ->get('/dashboard')
            ->assertOk();

        // 2. Platform owner suspends organization
        $response = $this->actingAs($this->owner, 'platform')
            ->post("/platform/organizations/{$this->org->id}/suspend", [
                'reason' => 'Facture impayée depuis 30 jours',
            ]);

        $response->assertRedirect();
        $this->org->refresh();
        $this->assertEquals('suspended', $this->org->status);

        // 3. Tenant user is now blocked by EnsureOrganizationActive middleware
        $this->actingAs($this->tenantUser, 'web')
            ->get('/dashboard')
            ->assertStatus(403);
    }

    public function test_can_reactivate_organization_and_tenant_users_regain_access(): void
    {
        $this->org->update(['status' => 'suspended']);

        $response = $this->actingAs($this->owner, 'platform')
            ->post("/platform/organizations/{$this->org->id}/reactivate");

        $response->assertRedirect();
        $this->org->refresh();
        $this->assertEquals('active', $this->org->status);

        // Tenant user regains access
        $this->actingAs($this->tenantUser, 'web')
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_non_existent_organization_returns_404(): void
    {
        $this->actingAs($this->owner, 'platform')
            ->get('/platform/organizations/99999999-9999-9999-9999-999999999999')
            ->assertNotFound();
    }
}
