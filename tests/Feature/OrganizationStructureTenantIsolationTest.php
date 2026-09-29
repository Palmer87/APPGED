<?php

namespace Tests\Feature;

use App\Enums\AccessScopeType;
use App\Models\Direction;
use App\Models\Document;
use App\Models\Organization;
use App\Models\Service;
use App\Models\User;
use App\Services\AccessControlService;
use App\Services\AccessScopeService;
use App\Services\OrganizationStructureService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class OrganizationStructureTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $orgA;

    protected Organization $orgB;

    protected User $adminA;

    protected User $adminB;

    protected Direction $dirA;

    protected Direction $dirB;

    protected Service $servA;

    protected Service $servB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orgA = Organization::factory()->create();
        $this->orgB = Organization::factory()->create();

        $teamForeignKey = config('permission.column_names.team_foreign_key', 'organization_id');

        $permissions = ['folders.view', 'folders.create', 'folders.update', 'folders.delete', 'documents.view'];
        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'sanctum']);
        }

        // Admin A
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $roleA = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web', $teamForeignKey => $this->orgA->id]);
        $roleA->givePermissionTo($permissions);
        $this->adminA = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->adminA->assignRole('admin');

        // Admin B
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgB->id);
        $roleB = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web', $teamForeignKey => $this->orgB->id]);
        $roleB->givePermissionTo($permissions);
        $this->adminB = User::factory()->create(['organization_id' => $this->orgB->id]);
        $this->adminB->assignRole('admin');

        $this->adminA->refresh();
        $this->adminB->refresh();

        // Directions & Services
        $structService = app(OrganizationStructureService::class);
        $this->dirA = $structService->createDirection(['name' => 'Direction A', 'code' => 'DIR_A'], $this->adminA);
        $this->servA = $structService->createService(['direction_id' => $this->dirA->id, 'name' => 'Service A', 'code' => 'SERV_A'], $this->adminA);

        $this->dirB = $structService->createDirection(['name' => 'Direction B', 'code' => 'DIR_B'], $this->adminB);
        $this->servB = $structService->createService(['direction_id' => $this->dirB->id, 'name' => 'Service B', 'code' => 'SERV_B'], $this->adminB);
    }

    public function test_org_a_cannot_view_or_modify_org_b_directions_via_web(): void
    {
        $response = $this->actingAs($this->adminA)->put("/directions/{$this->dirB->id}", [
            'name' => 'Hacked Direction B',
        ]);
        $response->assertForbidden();

        $deleteResponse = $this->actingAs($this->adminA)->delete("/directions/{$this->dirB->id}");
        $deleteResponse->assertForbidden();

        $this->assertDatabaseHas('directions', [
            'id' => $this->dirB->id,
            'name' => 'Direction B',
        ]);
    }

    public function test_org_a_cannot_view_or_modify_org_b_services_via_web(): void
    {
        $response = $this->actingAs($this->adminA)->put("/services/{$this->servB->id}", [
            'name' => 'Hacked Service B',
            'direction_id' => $this->dirB->id,
        ]);
        $response->assertForbidden();

        $deleteResponse = $this->actingAs($this->adminA)->delete("/services/{$this->servB->id}");
        $deleteResponse->assertForbidden();

        $this->assertDatabaseHas('services', [
            'id' => $this->servB->id,
            'name' => 'Service B',
        ]);
    }

    public function test_org_a_cannot_view_or_modify_org_b_directions_via_api(): void
    {
        $response = $this->actingAs($this->adminA, 'sanctum')->getJson("/api/v1/directions/{$this->dirB->id}");
        $response->assertForbidden();

        $putResponse = $this->actingAs($this->adminA, 'sanctum')->putJson("/api/v1/directions/{$this->dirB->id}", [
            'name' => 'Attempt API Hack',
        ]);
        $putResponse->assertForbidden();
    }

    public function test_org_a_cannot_view_or_modify_org_b_services_via_api(): void
    {
        $response = $this->actingAs($this->adminA, 'sanctum')->getJson("/api/v1/services/{$this->servB->id}");
        $response->assertForbidden();

        $putResponse = $this->actingAs($this->adminA, 'sanctum')->putJson("/api/v1/services/{$this->servB->id}", [
            'name' => 'Attempt API Hack',
            'direction_id' => $this->dirB->id,
        ]);
        $putResponse->assertForbidden();
    }

    public function test_cannot_assign_cross_tenant_service_to_user(): void
    {
        $structService = app(OrganizationStructureService::class);

        $this->expectException(ModelNotFoundException::class);
        $structService->assignUserToService($this->adminA, $this->servB);
    }

    public function test_cannot_create_service_in_direction_of_another_organization(): void
    {
        $structService = app(OrganizationStructureService::class);

        $this->expectException(ModelNotFoundException::class);
        $structService->createService([
            'direction_id' => $this->dirB->id,
            'name' => 'Cross Tenant Service',
        ], $this->adminA);
    }

    public function test_cannot_grant_access_scope_for_cross_tenant_target(): void
    {
        $scopeService = app(AccessScopeService::class);

        $this->expectException(ModelNotFoundException::class);
        $scopeService->grantScope($this->adminA, AccessScopeType::Service, ['service_id' => $this->servB->id]);
    }

    public function test_search_and_access_control_strictly_isolate_tenant_documents(): void
    {
        $docA = Document::factory()->create([
            'organization_id' => $this->orgA->id,
            'direction_id' => $this->dirA->id,
            'service_id' => $this->servA->id,
            'name' => 'Doc Org A',
        ]);

        $docB = Document::factory()->create([
            'organization_id' => $this->orgB->id,
            'direction_id' => $this->dirB->id,
            'service_id' => $this->servB->id,
            'name' => 'Doc Org B',
        ]);

        $accessControl = app(AccessControlService::class);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $this->assertFalse($accessControl->canAccessDocument($this->adminA, $docB, 'view'));
        $this->assertTrue($accessControl->canAccessDocument($this->adminA, $docA, 'view'));

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgB->id);
        $this->assertFalse($accessControl->canAccessDocument($this->adminB, $docA, 'view'));
        $this->assertTrue($accessControl->canAccessDocument($this->adminB, $docB, 'view'));
    }
}
