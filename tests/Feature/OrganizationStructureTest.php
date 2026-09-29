<?php

namespace Tests\Feature;

use App\Enums\FolderType;
use App\Models\Direction;
use App\Models\Document;
use App\Models\Folder;
use App\Models\Organization;
use App\Models\Service;
use App\Models\User;
use App\Services\OrganizationStructureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class OrganizationStructureTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;

    protected User $admin;

    protected OrganizationStructureService $structureService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::factory()->create();

        $teamForeignKey = config('permission.column_names.team_foreign_key', 'organization_id');
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->org->id);

        $permissions = ['folders.view', 'folders.create', 'folders.update', 'folders.delete'];
        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'sanctum']);
        }

        $role = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
            $teamForeignKey => $this->org->id,
        ]);
        $role->givePermissionTo($permissions);

        $this->admin = User::factory()->create([
            'organization_id' => $this->org->id,
        ]);
        $this->admin->assignRole('admin');

        $this->structureService = app(OrganizationStructureService::class);
    }

    public function test_complete_organizational_hierarchy_creation(): void
    {
        // 1. Create Direction
        $direction = $this->structureService->createDirection([
            'name' => 'Direction Administrative & Financière',
            'code' => 'DAF',
            'description' => 'Direction en charge des finances',
        ], $this->admin);

        $this->assertInstanceOf(Direction::class, $direction);
        $this->assertEquals('DAF', $direction->code);
        $this->assertNotNull($direction->folder_id);

        $backingFolder = Folder::find($direction->folder_id);
        $this->assertNotNull($backingFolder);
        $this->assertEquals(FolderType::Department, $backingFolder->folder_type);
        $this->assertEquals($this->org->id, $backingFolder->organization_id);

        // 2. Create Services under Direction
        $comptaService = $this->structureService->createService([
            'direction_id' => $direction->id,
            'name' => 'Comptabilité',
            'code' => 'COMPTA',
        ], $this->admin);

        $financeService = $this->structureService->createService([
            'direction_id' => $direction->id,
            'name' => 'Finance',
            'code' => 'FIN',
        ], $this->admin);

        $this->assertEquals($direction->id, $comptaService->direction_id);
        $this->assertEquals($this->org->id, $comptaService->organization_id);
        $this->assertEquals($direction->id, $financeService->direction_id);

        $comptaFolder = Folder::find($comptaService->folder_id);
        $this->assertNotNull($comptaFolder);
        $this->assertEquals(FolderType::Service, $comptaFolder->folder_type);
        $this->assertEquals($backingFolder->id, $comptaFolder->parent_id);

        // 3. Assign User to Services (Primary & Associated)
        $user = User::factory()->create([
            'organization_id' => $this->org->id,
        ]);

        $this->structureService->assignUserToService($user, $comptaService, true);
        $this->structureService->assignUserToService($user, $financeService, false);

        $user->refresh();
        $this->assertEquals($comptaService->id, $user->primary_service_id);
        $this->assertTrue($user->primaryService->is($comptaService));
        $this->assertCount(2, $user->services);
        $this->assertTrue($user->services->contains($comptaService));
        $this->assertTrue($user->services->contains($financeService));

        // 4. Create Document Type and attach Document in Service Context
        $docType = Folder::create([
            'organization_id' => $this->org->id,
            'name' => 'Facture Fournisseur',
            'folder_type' => FolderType::DocumentType,
            'parent_id' => $comptaFolder->id,
            'direction_id' => $direction->id,
            'service_id' => $comptaService->id,
            'created_by' => $this->admin->id,
            'path' => $comptaFolder->path.'/Facture Fournisseur',
        ]);

        $document = Document::factory()->create([
            'organization_id' => $this->org->id,
            'folder_id' => $comptaFolder->id,
            'direction_id' => $direction->id,
            'service_id' => $comptaService->id,
            'name' => 'Facture Dell 2026',
        ]);

        $this->assertEquals($direction->id, $document->direction_id);
        $this->assertEquals($comptaService->id, $document->service_id);
        $this->assertEquals('Facture Dell 2026', $document->name);
    }

    public function test_updating_direction_syncs_with_folder_and_services(): void
    {
        $direction = $this->structureService->createDirection([
            'name' => 'Ressources Humaines',
            'code' => 'RH',
        ], $this->admin);

        $service = $this->structureService->createService([
            'direction_id' => $direction->id,
            'name' => 'Paie',
            'code' => 'PAIE',
        ], $this->admin);

        $this->structureService->updateDirection($direction, [
            'name' => 'Direction des Ressources Humaines',
            'code' => 'DRH',
        ]);

        $direction->refresh();
        $this->assertEquals('Direction des Ressources Humaines', $direction->name);
        $this->assertEquals('DRH', $direction->code);

        $backingFolder = Folder::find($direction->folder_id);
        $this->assertEquals('Direction des Ressources Humaines', $backingFolder->name);

        $service->refresh();
        $this->assertEquals('Direction des Ressources Humaines', $service->direction->name);
    }

    public function test_deleting_service_unlinks_primary_service_from_users(): void
    {
        $direction = $this->structureService->createDirection([
            'name' => 'Direction Commerciale',
            'code' => 'DIR_COMM',
        ], $this->admin);

        $service = $this->structureService->createService([
            'direction_id' => $direction->id,
            'name' => 'Ventes',
            'code' => 'VENTES',
        ], $this->admin);

        $user = User::factory()->create(['organization_id' => $this->org->id]);
        $this->structureService->assignUserToService($user, $service, true);

        $this->assertEquals($service->id, $user->fresh()->primary_service_id);

        $this->structureService->deleteService($service);

        $this->assertSoftDeleted('services', ['id' => $service->id]);
        $this->assertNull($user->fresh()->primary_service_id);
    }
}
