<?php

namespace Tests\Feature;

use App\Enums\AccessScopeType;
use App\Models\Direction;
use App\Models\Organization;
use App\Models\Service;
use App\Models\User;
use App\Services\AccessScopeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AccessScopeTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;

    protected User $admin;

    protected User $user;

    protected Direction $daf;

    protected Direction $rh;

    protected Service $compta;

    protected AccessScopeService $scopeService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scopeService = app(AccessScopeService::class);
        $this->org = Organization::factory()->create();

        $teamForeignKey = config('permission.column_names.team_foreign_key', 'organization_id');
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->org->id);

        $role = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
            $teamForeignKey => $this->org->id,
        ]);

        $this->admin = User::factory()->create([
            'organization_id' => $this->org->id,
        ]);
        $this->admin->assignRole('admin');

        $this->user = User::factory()->create([
            'organization_id' => $this->org->id,
        ]);

        $this->daf = Direction::factory()->create([
            'organization_id' => $this->org->id,
            'name' => 'DAF',
        ]);

        $this->rh = Direction::factory()->create([
            'organization_id' => $this->org->id,
            'name' => 'RH',
        ]);

        $this->compta = Service::factory()->create([
            'organization_id' => $this->org->id,
            'direction_id' => $this->daf->id,
            'name' => 'Comptabilité',
        ]);
    }

    public function test_can_grant_service_scope(): void
    {
        $scope = $this->scopeService->grantScope(
            $this->user,
            AccessScopeType::Service,
            ['service_id' => $this->compta->id],
            $this->admin
        );

        $this->assertDatabaseHas('access_scopes', [
            'id' => $scope->id,
            'organization_id' => $this->org->id,
            'user_id' => $this->user->id,
            'scope_type' => 'service',
            'service_id' => $this->compta->id,
        ]);

        $this->assertTrue($this->scopeService->canAccess($this->user, AccessScopeType::Service, $this->compta->id));
        $this->assertFalse($this->scopeService->canAccess($this->user, AccessScopeType::Direction, $this->rh->id));
    }

    public function test_organization_scope_grants_access_to_all_sub_scopes(): void
    {
        $this->scopeService->grantScope(
            $this->user,
            AccessScopeType::Organization,
            [],
            $this->admin
        );

        $this->assertTrue($this->scopeService->canAccess($this->user, AccessScopeType::Organization));
        $this->assertTrue($this->scopeService->canAccess($this->user, AccessScopeType::Direction, $this->daf->id));
        $this->assertTrue($this->scopeService->canAccess($this->user, AccessScopeType::Service, $this->compta->id));
    }

    public function test_can_revoke_scope(): void
    {
        $scope = $this->scopeService->grantScope(
            $this->user,
            AccessScopeType::Service,
            ['service_id' => $this->compta->id],
            $this->admin
        );

        $this->assertTrue($this->scopeService->canAccess($this->user, AccessScopeType::Service, $this->compta->id));

        $this->scopeService->revokeScope($scope, $this->admin);

        $this->assertDatabaseMissing('access_scopes', ['id' => $scope->id]);
        $this->assertFalse($this->scopeService->canAccess($this->user, AccessScopeType::Service, $this->compta->id));
    }

    public function test_web_store_creates_access_scope_successfully(): void
    {
        $response = $this->actingAs($this->admin)->post('/access-scopes', [
            'user_id' => $this->user->id,
            'scope_type' => 'direction',
            'direction_id' => $this->daf->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('access_scopes', [
            'organization_id' => $this->org->id,
            'user_id' => $this->user->id,
            'scope_type' => 'direction',
            'direction_id' => $this->daf->id,
        ]);
    }

    public function test_web_store_validates_coherence_between_scope_type_and_target_ids(): void
    {
        // direction scope missing direction_id
        $response = $this->actingAs($this->admin)->post('/access-scopes', [
            'user_id' => $this->user->id,
            'scope_type' => 'direction',
        ]);
        $response->assertSessionHasErrors(['direction_id']);

        // service scope with prohibited direction_id
        $response = $this->actingAs($this->admin)->post('/access-scopes', [
            'user_id' => $this->user->id,
            'scope_type' => 'service',
            'service_id' => $this->compta->id,
            'direction_id' => $this->daf->id,
        ]);
        $response->assertSessionHasErrors(['direction_id']);
    }
}
