<?php

namespace Tests\Feature;

use App\Enums\AccessScopeType;
use App\Models\Direction;
use App\Models\Document;
use App\Models\DocumentPermission;
use App\Models\Organization;
use App\Models\Service;
use App\Models\User;
use App\Services\AccessControlService;
use App\Services\AccessScopeService;
use App\Services\OrganizationStructureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class OrganizationStructurePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;

    protected User $admin;

    protected User $user;

    protected Direction $directionDaf;

    protected Direction $directionRh;

    protected Service $serviceCompta;

    protected Service $serviceFinance;

    protected Service $servicePaie;

    protected Document $docCompta;

    protected Document $docFinance;

    protected Document $docPaie;

    protected AccessControlService $accessControl;

    protected AccessScopeService $scopeService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::factory()->create();

        $teamForeignKey = config('permission.column_names.team_foreign_key', 'organization_id');
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->org->id);

        $permissions = ['folders.view', 'folders.create', 'folders.update', 'folders.delete', 'documents.view'];
        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'sanctum']);
        }

        // Roles
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web', $teamForeignKey => $this->org->id]);
        $adminRole->givePermissionTo($permissions);

        $collaboratorRole = Role::firstOrCreate(['name' => 'collaborator', 'guard_name' => 'web', $teamForeignKey => $this->org->id]);
        $collaboratorRole->givePermissionTo(['folders.view', 'documents.view']);

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web', $teamForeignKey => $this->org->id]);

        $this->admin = User::factory()->create(['organization_id' => $this->org->id]);
        $this->admin->assignRole('admin');

        $this->user = User::factory()->create(['organization_id' => $this->org->id]);
        $this->user->assignRole('collaborator');

        // Structure
        $structService = app(OrganizationStructureService::class);
        $this->directionDaf = $structService->createDirection(['name' => 'Direction Administrative & Financière', 'code' => 'DAF'], $this->admin);
        $this->serviceCompta = $structService->createService(['direction_id' => $this->directionDaf->id, 'name' => 'Comptabilité', 'code' => 'COMPTA'], $this->admin);
        $this->serviceFinance = $structService->createService(['direction_id' => $this->directionDaf->id, 'name' => 'Finance', 'code' => 'FIN'], $this->admin);

        $this->directionRh = $structService->createDirection(['name' => 'Direction RH', 'code' => 'DRH'], $this->admin);
        $this->servicePaie = $structService->createService(['direction_id' => $this->directionRh->id, 'name' => 'Paie', 'code' => 'PAIE'], $this->admin);

        // Documents
        $this->docCompta = Document::factory()->create([
            'organization_id' => $this->org->id,
            'direction_id' => $this->directionDaf->id,
            'service_id' => $this->serviceCompta->id,
            'folder_id' => $this->serviceCompta->folder_id,
            'name' => 'Bilan Comptable 2026',
        ]);

        $this->docFinance = Document::factory()->create([
            'organization_id' => $this->org->id,
            'direction_id' => $this->directionDaf->id,
            'service_id' => $this->serviceFinance->id,
            'folder_id' => $this->serviceFinance->folder_id,
            'name' => 'Prévisionnel Trésorerie 2026',
        ]);

        $this->docPaie = Document::factory()->create([
            'organization_id' => $this->org->id,
            'direction_id' => $this->directionRh->id,
            'service_id' => $this->servicePaie->id,
            'folder_id' => $this->servicePaie->folder_id,
            'name' => 'Bulletins de Paie Janvier 2026',
        ]);

        $this->accessControl = app(AccessControlService::class);
        $this->scopeService = app(AccessScopeService::class);
    }

    public function test_collaborator_without_scope_permissions_cannot_manage_directions(): void
    {
        $response = $this->actingAs($this->user)->post('/directions', [
            'name' => 'Nouvelle Direction Illégale',
        ]);
        $response->assertForbidden();

        $deleteResponse = $this->actingAs($this->user)->delete("/directions/{$this->directionDaf->id}");
        $deleteResponse->assertForbidden();
    }

    public function test_service_level_access_scope_restricts_document_access(): void
    {
        // Give user scope only to Comptabilité service
        $this->scopeService->grantScope($this->user, AccessScopeType::Service, ['service_id' => $this->serviceCompta->id]);

        $this->assertTrue($this->accessControl->canAccessDocument($this->user, $this->docCompta, 'view'));
        $this->assertFalse($this->accessControl->canAccessDocument($this->user, $this->docFinance, 'view'));
        $this->assertFalse($this->accessControl->canAccessDocument($this->user, $this->docPaie, 'view'));
    }

    public function test_direction_level_access_scope_grants_access_to_all_underlying_services(): void
    {
        // Give user scope to entire DAF direction
        $this->scopeService->grantScope($this->user, AccessScopeType::Direction, ['direction_id' => $this->directionDaf->id]);

        $this->assertTrue($this->accessControl->canAccessDocument($this->user, $this->docCompta, 'view'));
        $this->assertTrue($this->accessControl->canAccessDocument($this->user, $this->docFinance, 'view'));
        $this->assertFalse($this->accessControl->canAccessDocument($this->user, $this->docPaie, 'view'));
    }

    public function test_revoking_access_scope_immediately_removes_access(): void
    {
        $scope = $this->scopeService->grantScope($this->user, AccessScopeType::Service, ['service_id' => $this->serviceCompta->id]);
        $this->assertTrue($this->accessControl->canAccessDocument($this->user, $this->docCompta, 'view'));

        $this->scopeService->revokeScope($scope);
        $this->user->refresh();

        // Without scope, user has no access unless granted organization-wide or via specific ACL
        $this->assertFalse($this->accessControl->canAccessDocument($this->user, $this->docCompta, 'view'));
    }

    public function test_document_direct_acl_overrides_scope_absence(): void
    {
        // User has scope on Comptabilité, none on Paie
        $this->scopeService->grantScope($this->user, AccessScopeType::Service, ['service_id' => $this->serviceCompta->id]);
        $this->assertFalse($this->accessControl->canAccessDocument($this->user, $this->docPaie, 'view'));

        // Now grant explicit document ACL to docPaie for this user
        DocumentPermission::create([
            'document_id' => $this->docPaie->id,
            'user_id' => $this->user->id,
            'permission' => 'view',
        ]);

        // Explicit document ACL must grant access as an exception
        $this->assertTrue($this->accessControl->canAccessDocument($this->user, $this->docPaie, 'view'));
    }

    public function test_super_admin_has_unrestricted_access(): void
    {
        $superAdmin = User::factory()->create([
            'organization_id' => $this->org->id,
        ]);
        $superAdmin->assignRole('super-admin');

        $this->assertTrue($this->accessControl->canAccessDocument($superAdmin, $this->docCompta, 'view'));
        $this->assertTrue($this->accessControl->canAccessDocument($superAdmin, $this->docFinance, 'view'));
        $this->assertTrue($this->accessControl->canAccessDocument($superAdmin, $this->docPaie, 'view'));
    }
}
