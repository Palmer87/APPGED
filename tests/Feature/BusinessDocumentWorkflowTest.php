<?php

namespace Tests\Feature;

use App\Enums\FolderType;
use App\Models\Document;
use App\Models\Folder;
use App\Models\MetadataDefinition;
use App\Models\Organization;
use App\Models\User;
use App\Services\DocumentMetadataService;
use App\Services\DocumentService;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BusinessDocumentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $orgA;

    protected Organization $orgB;

    protected User $adminA;

    protected User $userA;

    protected User $userB;

    protected Role $adminRoleA;

    protected Role $memberRoleA;

    protected Role $adminRoleB;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');
        Storage::fake('r2');

        $this->orgA = Organization::factory()->create(['name' => 'Organisation A']);
        $this->orgB = Organization::factory()->create(['name' => 'Organisation B']);

        $teamForeignKey = config('permission.column_names.team_foreign_key', 'organization_id');

        $permissions = [
            'folders.view', 'folders.create', 'folders.update', 'folders.delete',
            'documents.view', 'documents.create', 'documents.update', 'documents.delete', 'documents.download',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        // Setup Org A roles and users
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);

        $this->adminRoleA = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
            $teamForeignKey => $this->orgA->id,
        ]);
        $this->adminRoleA->syncPermissions($permissions);

        $this->memberRoleA = Role::firstOrCreate([
            'name' => 'member',
            'guard_name' => 'web',
            $teamForeignKey => $this->orgA->id,
        ]);
        $this->memberRoleA->syncPermissions(['documents.view', 'documents.create', 'documents.update', 'folders.view']);

        $this->adminA = User::factory()->create([
            'organization_id' => $this->orgA->id,
            'email' => 'admin@orga.test',
        ]);
        $this->adminA->assignRole($this->adminRoleA);

        $this->userA = User::factory()->create([
            'organization_id' => $this->orgA->id,
            'email' => 'user@orga.test',
        ]);
        $this->userA->assignRole($this->memberRoleA);

        // Setup Org B
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgB->id);
        $this->adminRoleB = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
            $teamForeignKey => $this->orgB->id,
        ]);
        $this->adminRoleB->syncPermissions($permissions);

        $this->userB = User::factory()->create([
            'organization_id' => $this->orgB->id,
            'email' => 'user@orgb.test',
        ]);
        $this->userB->assignRole($this->adminRoleB);

        // Reset to Org A
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
    }

    /**
     * Helper to create a business structure: Direction -> Document Type with Metadata
     */
    protected function createBusinessStructure(): array
    {
        $dept = Folder::factory()->department()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'Comptabilité',
            'created_by' => $this->adminA->id,
        ]);

        $docType = Folder::factory()->documentType()->withParent($dept)->create([
            'organization_id' => $this->orgA->id,
            'name' => 'Facture client',
            'created_by' => $this->adminA->id,
        ]);

        $defNumFacture = MetadataDefinition::create([
            'organization_id' => $this->orgA->id,
            'name' => 'Numéro Facture',
            'key' => 'numero_facture',
            'type' => 'string',
            'is_required' => true,
            'is_active' => true,
        ]);

        $defClient = MetadataDefinition::create([
            'organization_id' => $this->orgA->id,
            'name' => 'Nom Client',
            'key' => 'client',
            'type' => 'string',
            'is_required' => true,
            'is_active' => true,
        ]);

        $defMontant = MetadataDefinition::create([
            'organization_id' => $this->orgA->id,
            'name' => 'Montant TTC',
            'key' => 'montant_ttc',
            'type' => 'decimal',
            'is_required' => true,
            'is_active' => true,
        ]);

        $defDate = MetadataDefinition::create([
            'organization_id' => $this->orgA->id,
            'name' => 'Date Facture',
            'key' => 'date_facture',
            'type' => 'date',
            'is_required' => false,
            'is_active' => true,
        ]);

        // Associate definitions with Document Type
        $docType->metadataDefinitions()->attach([
            $defNumFacture->id => ['is_required' => true, 'order' => 1],
            $defClient->id => ['is_required' => true, 'order' => 2],
            $defMontant->id => ['is_required' => true, 'order' => 3],
            $defDate->id => ['is_required' => false, 'order' => 4],
        ]);

        return compact('dept', 'docType', 'defNumFacture', 'defClient', 'defMontant', 'defDate');
    }

    // =========================================================================
    // A & B: DIRECTION & TYPE DOCUMENTAIRE
    // =========================================================================

    public function test_direction_and_type_creation_and_isolation(): void
    {
        // Admin creates Direction
        $deptResponse = $this->actingAs($this->adminA)->post('/departments', [
            'name' => 'Ressources Humaines',
            'description' => 'Direction RH',
        ]);
        $deptResponse->assertRedirect();

        $dept = Folder::where('name', 'Ressources Humaines')->firstOrFail();
        $this->assertEquals(FolderType::Department, $dept->folder_type);
        $this->assertEquals($this->orgA->id, $dept->organization_id);

        // Admin creates Type Documentaire under Direction
        $typeResponse = $this->actingAs($this->adminA)->post('/document-types', [
            'parent_id' => $dept->id,
            'name' => 'Contrat de travail',
            'description' => 'Contrats employés',
        ]);
        $typeResponse->assertRedirect();

        $type = Folder::where('name', 'Contrat de travail')->firstOrFail();
        $this->assertEquals(FolderType::DocumentType, $type->folder_type);
        $this->assertEquals($dept->id, $type->parent_id);
        $this->assertEquals($this->orgA->id, $type->organization_id);

        // User from Org B cannot access Org A direction
        $crossResponse = $this->actingAs($this->userB)->put("/departments/{$dept->id}", [
            'name' => 'Hacked Name',
        ]);
        $crossResponse->assertForbidden();

        // User from Org B cannot attach document type to Org A direction
        $crossTypeResponse = $this->actingAs($this->userB)->post('/document-types', [
            'parent_id' => $dept->id,
            'name' => 'Cross Type',
        ]);
        $crossTypeResponse->assertNotFound();
    }

    // =========================================================================
    // C: MÉTADONNÉES & ASSOCIATION
    // =========================================================================

    public function test_metadata_definitions_association_and_required_validation(): void
    {
        $structure = $this->createBusinessStructure();
        $docType = $structure['docType'];

        $this->assertCount(4, $docType->metadataDefinitions);

        // Admin can sync/update metadata configuration
        $syncResponse = $this->actingAs($this->adminA)->post("/document-types/{$docType->id}/metadata", [
            'definitions' => [
                ['id' => $structure['defNumFacture']->id, 'is_required' => true, 'order' => 1],
                ['id' => $structure['defClient']->id, 'is_required' => false, 'order' => 2],
            ],
        ]);
        $syncResponse->assertRedirect();

        $docType->refresh();
        $this->assertCount(2, $docType->metadataDefinitions);
    }

    // =========================================================================
    // D: WORKFLOW D'IMPORTATION MÉTIER
    // =========================================================================

    public function test_import_page_loads_with_departments_and_metadata_structure(): void
    {
        $structure = $this->createBusinessStructure();

        $response = $this->actingAs($this->userA)->get('/documents/create');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Documents/Create')
            ->has('departments')
            ->has('departments.0.document_types')
            ->has('departments.0.document_types.0.metadata_definitions')
        );
    }

    public function test_import_document_with_business_metadata_and_internal_folder(): void
    {
        $structure = $this->createBusinessStructure();
        $dept = $structure['dept'];
        $docType = $structure['docType'];

        $file = UploadedFile::fake()->create('facture-2026-001.pdf', 200, 'application/pdf');

        $response = $this->actingAs($this->userA)->post('/documents', [
            'file' => $file,
            'name' => 'Facture Client ABC',
            'department_id' => $dept->id,
            'document_type_id' => $docType->id,
            'metadata' => [
                'numero_facture' => 'FAC-2026-001',
                'client' => 'Société ABC',
                'montant_ttc' => '250000',
                'date_facture' => '2026-09-18',
            ],
        ]);

        $document = Document::where('name', 'Facture Client ABC')->firstOrFail();
        $response->assertRedirect("/documents/{$document->id}");

        // Verifications:
        // 1. Tenant
        $this->assertEquals($this->orgA->id, $document->organization_id);

        // 2. Internal folder is automatically assigned to the Document Type folder
        $this->assertEquals($docType->id, $document->folder_id);
        $this->assertEquals($docType->id, $document->document_type_id);

        // 3. Version 1 created
        $this->assertDatabaseHas('document_versions', [
            'document_id' => $document->id,
            'version_number' => 1,
        ]);

        // 4. Physical storage exists
        Storage::disk($document->storage_disk)->assertExists($document->storage_path);

        // 5. Metadata records persisted
        $this->assertDatabaseHas('document_metadata', [
            'document_id' => $document->id,
            'metadata_definition_id' => $structure['defNumFacture']->id,
            'value_string' => 'FAC-2026-001',
        ]);
        $this->assertDatabaseHas('document_metadata', [
            'document_id' => $document->id,
            'metadata_definition_id' => $structure['defClient']->id,
            'value_string' => 'Société ABC',
        ]);
        $this->assertDatabaseHas('document_metadata', [
            'document_id' => $document->id,
            'metadata_definition_id' => $structure['defMontant']->id,
            'value_decimal' => 250000.0,
        ]);

        // 6. Audit log created
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Document::class,
            'auditable_id' => $document->id,
            'action' => 'document.created',
        ]);
    }

    public function test_import_document_validates_required_metadata_and_returns_validation_error(): void
    {
        $structure = $this->createBusinessStructure();
        $dept = $structure['dept'];
        $docType = $structure['docType'];

        $file = UploadedFile::fake()->create('facture-test.pdf', 200, 'application/pdf');

        // Missing required metadata 'numero_facture' and 'client'
        $response = $this->actingAs($this->userA)->post('/documents', [
            'file' => $file,
            'name' => 'Facture Sans Numero',
            'department_id' => $dept->id,
            'document_type_id' => $docType->id,
            'metadata' => [
                'montant_ttc' => '250000',
            ],
        ]);

        $response->assertSessionHasErrors(['metadata.numero_facture', 'metadata.client']);
    }

    // =========================================================================
    // E: RECHERCHE MÉTIER & PAR MÉTADONNÉES
    // =========================================================================

    public function test_search_page_and_dynamic_metadata_filtering(): void
    {
        $structure = $this->createBusinessStructure();
        $dept = $structure['dept'];
        $docType = $structure['docType'];

        $fileA = UploadedFile::fake()->create('facA.pdf', 100, 'application/pdf');
        $fileB = UploadedFile::fake()->create('facB.pdf', 100, 'application/pdf');

        $docService = app(DocumentService::class);
        $metaService = app(DocumentMetadataService::class);

        // Doc 1: FAC-2026-001 for ABC
        $doc1 = $docService->upload([
            'organization_id' => $this->orgA->id,
            'uploaded_by' => $this->userA->id,
            'folder_id' => $docType->id,
            'document_type_id' => $docType->id,
            'name' => 'Facture 001 ABC',
        ], $fileA);
        $metaService->setValues($doc1, [
            'numero_facture' => 'FAC-2026-001',
            'client' => 'Société ABC',
            'montant_ttc' => '250000',
        ]);

        // Doc 2: FAC-2026-002 for XYZ
        $doc2 = $docService->upload([
            'organization_id' => $this->orgA->id,
            'uploaded_by' => $this->userA->id,
            'folder_id' => $docType->id,
            'document_type_id' => $docType->id,
            'name' => 'Facture 002 XYZ',
        ], $fileB);
        $metaService->setValues($doc2, [
            'numero_facture' => 'FAC-2026-002',
            'client' => 'Entreprise XYZ',
            'montant_ttc' => '500000',
        ]);

        // Search Web Route rendering
        $searchPageResponse = $this->actingAs($this->userA)->get('/search');
        $searchPageResponse->assertOk();
        $searchPageResponse->assertInertia(fn ($page) => $page
            ->component('Search/Index')
            ->has('departments')
        );

        // Search by Direction + Document Type + Metadata criterion: client = ABC
        $searchService = app(SearchService::class);

        $results = $searchService->search($this->userA, [
            'department_id' => $dept->id,
            'document_type_id' => $docType->id,
            'metadata' => [
                'client' => 'ABC',
            ],
        ]);

        $this->assertEquals(1, $results->total());
        $this->assertEquals($doc1->id, $results->items()[0]->id);

        // Search by partial numero_facture "FAC-2026-" -> should return both
        $resultsAll = $searchService->search($this->userA, [
            'document_type_id' => $docType->id,
            'metadata' => [
                'numero_facture' => 'FAC-2026-',
            ],
        ]);
        $this->assertEquals(2, $resultsAll->total());
    }

    // =========================================================================
    // F: MODIFICATION MÉTIER & REMPLACEMENT FICHIER (VERSIONING)
    // =========================================================================

    public function test_edit_page_loads_with_document_and_metadata(): void
    {
        $structure = $this->createBusinessStructure();
        $docType = $structure['docType'];
        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $document = app(DocumentService::class)->upload([
            'organization_id' => $this->orgA->id,
            'uploaded_by' => $this->userA->id,
            'folder_id' => $docType->id,
            'document_type_id' => $docType->id,
            'name' => 'Test Document',
        ], $file);

        $response = $this->actingAs($this->userA)->get("/documents/{$document->id}/edit");
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Documents/Edit')
            ->has('document')
            ->has('departments')
            ->has('currentMetadata')
        );
    }

    public function test_modify_document_metadata_and_replace_file_creates_new_version(): void
    {
        $structure = $this->createBusinessStructure();
        $docType = $structure['docType'];
        $fileV1 = UploadedFile::fake()->create('facture_v1.pdf', 100, 'application/pdf');

        $document = app(DocumentService::class)->upload([
            'organization_id' => $this->orgA->id,
            'uploaded_by' => $this->userA->id,
            'folder_id' => $docType->id,
            'document_type_id' => $docType->id,
            'name' => 'Facture Initiale',
        ], $fileV1);

        app(DocumentMetadataService::class)->setValues($document, [
            'numero_facture' => 'FAC-2026-001',
            'client' => 'Société ABC',
            'montant_ttc' => '250000',
        ]);

        $v1Path = $document->storage_path;
        $this->assertEquals(1, $document->versions()->count());

        // Update with modified metadata AND replacement file
        $fileV2 = UploadedFile::fake()->create('facture_v2.pdf', 150, 'application/pdf');

        $updateResponse = $this->actingAs($this->userA)->put("/documents/{$document->id}", [
            'name' => 'Facture Modifiée ABC',
            'description' => 'Montant corrigé',
            'metadata' => [
                'numero_facture' => 'FAC-2026-001-CORR',
                'client' => 'Société ABC Inc',
                'montant_ttc' => '275000',
            ],
            'file' => $fileV2,
            'change_notes' => 'Avenant tarifaire',
        ]);

        $updateResponse->assertRedirect("/documents/{$document->id}");

        $document->refresh();

        // 1. Name & description updated
        $this->assertEquals('Facture Modifiée ABC', $document->name);
        $this->assertEquals('Montant corrigé', $document->description);

        // 2. Metadata updated
        $this->assertDatabaseHas('document_metadata', [
            'document_id' => $document->id,
            'metadata_definition_id' => $structure['defNumFacture']->id,
            'value_string' => 'FAC-2026-001-CORR',
        ]);
        $this->assertDatabaseHas('document_metadata', [
            'document_id' => $document->id,
            'metadata_definition_id' => $structure['defMontant']->id,
            'value_decimal' => 275000.0,
        ]);

        // 3. New version V2 created, V1 preserved physically
        $this->assertEquals(2, $document->versions()->count());
        $v1 = $document->versions()->where('version_number', 1)->firstOrFail();
        $v2 = $document->versions()->where('version_number', 2)->firstOrFail();

        $this->assertNotEquals($v1->storage_path, $v2->storage_path);
        Storage::disk($document->storage_disk)->assertExists($v1->storage_path);
        Storage::disk($document->storage_disk)->assertExists($v2->storage_path);

        // 4. Audit logged for version and update
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Document::class,
            'auditable_id' => $document->id,
            'action' => 'document.version_created',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Document::class,
            'auditable_id' => $document->id,
            'action' => 'document.updated',
        ]);
    }

    // =========================================================================
    // G: SÉCURITÉ & TENANT ISOLATION
    // =========================================================================

    public function test_tenant_cannot_access_or_modify_other_tenant_document(): void
    {
        $structure = $this->createBusinessStructure();
        $docType = $structure['docType'];
        $file = UploadedFile::fake()->create('private.pdf', 100, 'application/pdf');

        $document = app(DocumentService::class)->upload([
            'organization_id' => $this->orgA->id,
            'uploaded_by' => $this->userA->id,
            'folder_id' => $docType->id,
            'document_type_id' => $docType->id,
            'name' => 'Confidentiel Org A',
        ], $file);

        // User B cannot view document
        $viewResponse = $this->actingAs($this->userB)->get("/documents/{$document->id}");
        $viewResponse->assertForbidden();

        // User B cannot edit document
        $editResponse = $this->actingAs($this->userB)->get("/documents/{$document->id}/edit");
        $editResponse->assertForbidden();

        // User B cannot update document
        $updateResponse = $this->actingAs($this->userB)->put("/documents/{$document->id}", [
            'name' => 'Compromised Document',
        ]);
        $updateResponse->assertForbidden();

        // User B search does NOT return Org A documents
        $searchService = app(SearchService::class);
        $results = $searchService->search($this->userB, [
            'q' => 'Confidentiel Org A',
        ]);
        $this->assertEquals(0, $results->total());
    }

    public function test_document_show_page_provides_type_metadata_definitions_and_formatted_values(): void
    {
        $structure = $this->createBusinessStructure();
        $docType = $structure['docType'];
        $file = UploadedFile::fake()->create('facture_show.pdf', 100, 'application/pdf');

        $document = app(DocumentService::class)->upload([
            'organization_id' => $this->orgA->id,
            'uploaded_by' => $this->userA->id,
            'folder_id' => $docType->id,
            'document_type_id' => $docType->id,
            'name' => 'Facture Test Show',
        ], $file);

        // Add metadata values
        app(DocumentMetadataService::class)->setValues($document, [
            'numero_facture' => 'FAC-2026-999',
            'client' => 'Client Test SA',
            'date_facture' => '2026-09-21',
            'montant_ttc' => 1500.50,
        ]);

        $response = $this->actingAs($this->userA)->get("/documents/{$document->id}");
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Documents/Show')
            ->has('document.metadata_values')
            ->where('document.metadata_values.0.value', fn ($val) => ! empty($val))
            ->has('typeMetadataDefinitions', 4)
        );
    }

    public function test_user_without_permission_cannot_create_direction_or_type(): void
    {
        $unauthorizedUser = User::factory()->create([
            'organization_id' => $this->orgA->id,
        ]);
        // No roles or permissions assigned

        $responseDept = $this->actingAs($unauthorizedUser)->post('/departments', [
            'name' => 'Direction Non Autorisée',
        ]);
        $responseDept->assertForbidden();

        $structure = $this->createBusinessStructure();
        $dept = $structure['dept'];

        $responseType = $this->actingAs($unauthorizedUser)->post('/document-types', [
            'parent_id' => $dept->id,
            'name' => 'Type Non Autorisé',
        ]);
        $responseType->assertForbidden();
    }

    public function test_update_document_metadata_only_does_not_create_new_version(): void
    {
        $structure = $this->createBusinessStructure();
        $docType = $structure['docType'];
        $file = UploadedFile::fake()->create('doc_initial.pdf', 100, 'application/pdf');

        $document = app(DocumentService::class)->upload([
            'organization_id' => $this->orgA->id,
            'uploaded_by' => $this->userA->id,
            'folder_id' => $docType->id,
            'document_type_id' => $docType->id,
            'name' => 'Doc Initial',
        ], $file);

        $this->assertEquals(1, $document->versions()->count());

        // Update metadata without file
        $response = $this->actingAs($this->userA)->put("/documents/{$document->id}", [
            'name' => 'Doc Titre Mis à Jour',
            'metadata' => [
                'numero_facture' => 'FAC-UPDATE-001',
                'client' => 'Client Mis à Jour',
                'montant_ttc' => '300000',
            ],
        ]);

        $response->assertRedirect("/documents/{$document->id}");
        $document->refresh();

        // Still version 1 (no file replacement)
        $this->assertEquals(1, $document->versions()->count());
        $this->assertEquals('Doc Titre Mis à Jour', $document->name);

        $this->assertDatabaseHas('document_metadata', [
            'document_id' => $document->id,
            'value_string' => 'FAC-UPDATE-001',
        ]);
    }
}
