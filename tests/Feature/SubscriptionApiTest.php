<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\BillingPermissionSeeder;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SubscriptionApiTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;

    protected User $admin;

    protected User $regularUser;

    protected string $adminToken;

    protected string $userToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanSeeder::class);

        $this->org = Organization::factory()->create(['name' => 'API Test Org']);

        $this->seed(RolePermissionSeeder::class);
        $this->seed(BillingPermissionSeeder::class);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->org->id);

        $this->admin = User::factory()->create(['organization_id' => $this->org->id]);
        $this->admin->assignRole('admin');
        $this->adminToken = $this->admin->createToken('admin-token')->plainTextToken;

        $this->regularUser = User::factory()->create(['organization_id' => $this->org->id]);
        $this->regularUser->assignRole('utilisateur');
        $this->userToken = $this->regularUser->createToken('user-token')->plainTextToken;
    }

    public function test_get_plans_is_public(): void
    {
        $response = $this->getJson('/api/v1/plans');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'monthly_price',
                        'annual_price',
                        'currency',
                        'limits',
                        'features',
                    ],
                ],
            ]);
    }

    public function test_get_subscription_authenticated(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->getJson('/api/v1/subscription');

        $response->assertStatus(200)
            ->assertJsonPath('data.organization_id', $this->org->id)
            ->assertJsonPath('data.status', 'trialing')
            ->assertJsonPath('data.plan.slug', 'essential');
    }

    public function test_get_subscription_usage_authenticated(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->getJson('/api/v1/subscription/usage');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'plan_name',
                    'plan_slug',
                    'is_any_exceeded',
                    'warnings',
                    'metrics' => [
                        'users',
                        'storage',
                        'directions',
                        'document_types',
                        'ocr',
                    ],
                ],
            ]);
    }

    public function test_change_plan_via_api(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson('/api/v1/subscription/change-plan', [
                'plan_slug' => 'professional',
                'billing_cycle' => 'annual',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.plan.slug', 'professional')
            ->assertJsonPath('data.billing_cycle', 'annual')
            ->assertJsonPath('data.status', 'active');
    }

    public function test_cancel_and_resume_via_api(): void
    {
        // Cancel
        $cancelResponse = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson('/api/v1/subscription/cancel');

        $cancelResponse->assertStatus(200)
            ->assertJsonPath('data.auto_renew', false);

        // Resume
        $resumeResponse = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson('/api/v1/subscription/resume');

        $resumeResponse->assertStatus(200)
            ->assertJsonPath('data.auto_renew', true);
    }

    public function test_regular_user_cannot_change_plan_via_api(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->userToken}")
            ->postJson('/api/v1/subscription/change-plan', [
                'plan_slug' => 'professional',
                'billing_cycle' => 'monthly',
            ]);

        $response->assertStatus(403);
    }
}
