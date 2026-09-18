<?php

namespace Tests\Feature;

use App\Enums\FolderType;
use App\Models\Document;
use App\Models\Folder;
use App\Models\MetadataDefinition;
use App\Models\Organization;
use App\Models\User;
use App\Services\DocumentService;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DocumentStructureTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $orgA;

    protected Organization $orgB;

    protected User $adminUser;

    protected User $userOrgB;

    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');
        Storage::fake('r2');

        $this->orgA = Organization::factory()->create(['name' => 'Organisation A']);
        $this->orgB = Organization::factory()->create(['name' => 'Organisation B']);

        $teamForeignKey = config('permission.column_names.team_foreign_key', 'organization_id');
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);

        $permissions = [
            'folders.view', 'folders.create', 'folders.update', 'folders.delete',
            'documents.view', 'documents.create', 'documents.update', 'documents.delete',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $this->adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
            $teamForeignKey => $this->orgA->id,
        ]);
        $this->adminRole->syncPermissions($permissions);

        $this->adminUser = User::factory()->create([
            'organization_id' => $this->orgA->id,
            'email' => 'admin@orga.test',
        ]);
        $this->adminUser->assignRole($this->adminRole);

        // User from Org B
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgB->id);
        $roleOrgB = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
            $teamForeignKey => $this->orgB->id,
        ]);
        $roleOrgB->syncPermissions($permissions);

        $this->userOrgB = User::factory()->create([
            'organization_id' => $this->orgB->id,
            'email' => 'admin@orgb.test',
        ]);
        $this->userOrgB->assignRole($roleOrgB);

        // Reset team id to org A
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
    }

    public function test_admin_can_create_department(): void
    {
        $response = $this->from('/departments')->actingAs($this->adminUser)->post('/departments', [
            'name' => 'Comptabilité',
            'description' => 'Direction financière et comptable',
        ]);

        $response->assertRedirect('/departments');

        $this->assertDatabaseHas('folders', [
            'organization_id' => $this->orgA->id,
            'name' => 'Comptabilité',
            'folder_type' => FolderType::Department->value,
            'parent_id' => null,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_create_document_type_under_department(): void
    {
        $department = Folder::factory()->department()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'Comptabilité',
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->from('/document-types')->actingAs($this->adminUser)->post('/document-types', [
            'parent_id' => $department->id,
            'name' => 'Facture client',
            'description' => 'Factures émises aux clients',
        ]);

        $response->assertRedirect('/document-types');

        $this->assertDatabaseHas('folders', [
            'organization_id' => $this->orgA->id,
            'parent_id' => $department->id,
            'name' => 'Facture client',
            'folder_type' => FolderType::DocumentType->value,
            'is_active' => true,
        ]);
    }

    public function test_cannot_create_document_type_without_department(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/document-types', [
            'name' => 'Orphan Type',
            'parent_id' => null,
        ]);

        $response->assertSessionHasErrors('parent_id');
    }

    public function test_tenant_isolation_cannot_view_or_modify_other_tenant_departments(): void
    {
        $departmentB = Folder::factory()->department()->create([
            'organization_id' => $this->orgB->id,
            'name' => 'Direction Org B',
            'created_by' => $this->userOrgB->id,
        ]);

        // Org A user tries to update Org B department
        $response = $this->actingAs($this->adminUser)->put("/departments/{$departmentB->id}", [
            'name' => 'Hacked Department',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('folders', [
            'name' => 'Hacked Department',
        ]);
    }

    public function test_can_link_metadata_definitions_to_document_type(): void
    {
        $department = Folder::factory()->department()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'Comptabilité',
            'created_by' => $this->adminUser->id,
        ]);

        $docType = Folder::factory()->documentType()->create([
            'organization_id' => $this->orgA->id,
            'parent_id' => $department->id,
            'name' => 'Facture client',
            'created_by' => $this->adminUser->id,
        ]);

        $meta1 = MetadataDefinition::factory()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'Numéro facture',
            'key' => 'num_facture',
            'type' => 'string',
        ]);

        $meta2 = MetadataDefinition::factory()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'Date facture',
            'key' => 'date_facture',
            'type' => 'date',
        ]);

        $response = $this->from("/document-types/{$docType->id}/metadata")->actingAs($this->adminUser)->post("/document-types/{$docType->id}/metadata", [
            'definitions' => [
                ['id' => $meta1->id, 'is_required' => true, 'order' => 1],
                ['id' => $meta2->id, 'is_required' => false, 'order' => 2],
            ],
        ]);

        $response->assertRedirect("/document-types/{$docType->id}/metadata");

        $this->assertDatabaseHas('folder_metadata_definition', [
            'folder_id' => $docType->id,
            'metadata_definition_id' => $meta1->id,
            'is_required' => true,
            'order' => 1,
        ]);

        $this->assertDatabaseHas('folder_metadata_definition', [
            'folder_id' => $docType->id,
            'metadata_definition_id' => $meta2->id,
            'is_required' => false,
            'order' => 2,
        ]);
    }

    public function test_document_upload_in_document_type_folder_sets_document_type_id(): void
    {
        $department = Folder::factory()->department()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'Comptabilité',
            'created_by' => $this->adminUser->id,
        ]);

        $docType = Folder::factory()->documentType()->create([
            'organization_id' => $this->orgA->id,
            'parent_id' => $department->id,
            'name' => 'Facture client',
            'created_by' => $this->adminUser->id,
        ]);

        $metaNum = MetadataDefinition::factory()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'Numéro facture',
            'key' => 'num_facture',
            'type' => 'string',
        ]);

        $docType->metadataDefinitions()->attach($metaNum->id, ['is_required' => true, 'order' => 1]);

        $file = UploadedFile::fake()->create('facture-2026-001.pdf', 150, 'application/pdf');

        $response = $this->actingAs($this->adminUser)->post('/documents', [
            'file' => $file,
            'name' => 'Facture #001',
            'folder_id' => $docType->id,
            'metadata' => [
                $metaNum->id => 'FAC-2026-001',
            ],
        ]);

        $response->assertRedirect();

        $document = Document::where('name', 'Facture #001')->first();
        $this->assertNotNull($document);
        $this->assertEquals($docType->id, $document->document_type_id);
        $this->assertEquals($docType->id, $document->folder_id);

        // Check metadata value was saved
        $this->assertDatabaseHas('document_metadata', [
            'document_id' => $document->id,
            'metadata_definition_id' => $metaNum->id,
            'value_string' => 'FAC-2026-001',
        ]);
    }

    public function test_moving_document_updates_document_type_id(): void
    {
        $department = Folder::factory()->department()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'Comptabilité',
            'created_by' => $this->adminUser->id,
        ]);

        $docType1 = Folder::factory()->documentType()->create([
            'organization_id' => $this->orgA->id,
            'parent_id' => $department->id,
            'name' => 'Facture client',
            'created_by' => $this->adminUser->id,
        ]);

        $docType2 = Folder::factory()->documentType()->create([
            'organization_id' => $this->orgA->id,
            'parent_id' => $department->id,
            'name' => 'Facture fournisseur',
            'created_by' => $this->adminUser->id,
        ]);

        $documentService = app(DocumentService::class);
        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $doc = $documentService->upload([
            'organization_id' => $this->orgA->id,
            'uploaded_by' => $this->adminUser->id,
            'folder_id' => $docType1->id,
            'name' => 'Facture test',
        ], $file);

        $this->assertEquals($docType1->id, $doc->document_type_id);

        // Move to docType2
        $response = $this->actingAs($this->adminUser)->post("/documents/{$doc->id}/move", [
            'folder_id' => $docType2->id,
        ]);

        $response->assertRedirect();
        $doc->refresh();

        $this->assertEquals($docType2->id, $doc->folder_id);
        $this->assertEquals($docType2->id, $doc->document_type_id);
    }

    public function test_api_v1_document_types_list_and_show(): void
    {
        $department = Folder::factory()->department()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'RH',
            'created_by' => $this->adminUser->id,
        ]);

        $docType = Folder::factory()->documentType()->create([
            'organization_id' => $this->orgA->id,
            'parent_id' => $department->id,
            'name' => 'Contrat RH',
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->getJson('/api/v1/document-types');
        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Contrat RH']);

        $showResponse = $this->actingAs($this->adminUser)->getJson("/api/v1/document-types/{$docType->id}");
        $showResponse->assertOk();
        $showResponse->assertJsonFragment(['id' => $docType->id, 'name' => 'Contrat RH']);
    }

    public function test_cannot_delete_document_type_with_documents(): void
    {
        $department = Folder::factory()->department()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'Comptabilité',
            'created_by' => $this->adminUser->id,
        ]);

        $docType = Folder::factory()->documentType()->create([
            'organization_id' => $this->orgA->id,
            'parent_id' => $department->id,
            'name' => 'Factures',
            'created_by' => $this->adminUser->id,
        ]);

        $file = UploadedFile::fake()->create('facture.pdf', 100, 'application/pdf');
        app(DocumentService::class)->upload([
            'organization_id' => $this->orgA->id,
            'uploaded_by' => $this->adminUser->id,
            'folder_id' => $docType->id,
            'name' => 'Facture active',
        ], $file);

        // Trying to delete via controller should fail or return with error
        $response = $this->actingAs($this->adminUser)->delete("/document-types/{$docType->id}");
        $response->assertSessionHas('error');

        // Document type should still exist in database
        $this->assertDatabaseHas('folders', [
            'id' => $docType->id,
            'is_archived' => false,
        ]);
    }

    public function test_search_filters_by_department_and_document_type(): void
    {
        $department = Folder::factory()->department()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'Direction Juridique',
            'created_by' => $this->adminUser->id,
        ]);

        $docType = Folder::factory()->documentType()->create([
            'organization_id' => $this->orgA->id,
            'parent_id' => $department->id,
            'name' => 'Statuts',
            'created_by' => $this->adminUser->id,
        ]);

        $file = UploadedFile::fake()->create('statuts.pdf', 100, 'application/pdf');
        $doc = app(DocumentService::class)->upload([
            'organization_id' => $this->orgA->id,
            'uploaded_by' => $this->adminUser->id,
            'folder_id' => $docType->id,
            'name' => 'Statuts SAS 2026',
        ], $file);

        $searchService = app(SearchService::class);

        $results = $searchService->search($this->adminUser, ['department_id' => $department->id]);
        $this->assertEquals(1, $results->total());
        $this->assertEquals($doc->id, $results->first()->id);

        $resultsType = $searchService->search($this->adminUser, ['document_type_id' => $docType->id]);
        $this->assertEquals(1, $resultsType->total());
        $this->assertEquals($doc->id, $resultsType->first()->id);
    }
}
