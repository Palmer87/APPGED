<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use Database\Seeders\BillingPermissionSeeder;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BillingPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;

    protected User $admin;

    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanSeeder::class);

        $this->org = Organization::factory()->create();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(BillingPermissionSeeder::class);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->org->id);

        $this->admin = User::factory()->create(['organization_id' => $this->org->id]);
        $this->admin->assignRole('admin');

        $this->regularUser = User::factory()->create(['organization_id' => $this->org->id]);
        $this->regularUser->assignRole('utilisateur');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/settings/subscription')->assertRedirect('/login');
        $this->post('/settings/subscription/change-plan', [
            'plan_slug' => 'professional',
            'billing_cycle' => 'monthly',
        ])->assertRedirect('/login');
    }

    public function test_regular_user_can_view_subscription_overview(): void
    {
        $this->actingAs($this->regularUser)
            ->get('/settings/subscription')
            ->assertStatus(200);
    }

    public function test_regular_user_cannot_modify_billing(): void
    {
        // Change plan denied
        $this->actingAs($this->regularUser)
            ->post('/settings/subscription/change-plan', [
                'plan_slug' => 'professional',
                'billing_cycle' => 'monthly',
            ])->assertStatus(403);

        // Cancel denied
        $this->actingAs($this->regularUser)
            ->post('/settings/subscription/cancel')
            ->assertStatus(403);

        // Resume denied
        $this->actingAs($this->regularUser)
            ->post('/settings/subscription/resume')
            ->assertStatus(403);
    }

    public function test_admin_can_manage_billing(): void
    {
        $this->actingAs($this->admin)
            ->get('/settings/subscription')
            ->assertStatus(200);

        $this->actingAs($this->admin)
            ->post('/settings/subscription/change-plan', [
                'plan_slug' => 'professional',
                'billing_cycle' => 'monthly',
            ])->assertRedirect(route('subscription.show'));

        $this->actingAs($this->admin)
            ->post('/settings/subscription/cancel')
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->post('/settings/subscription/resume')
            ->assertRedirect();
    }
}
