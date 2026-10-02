<?php

namespace Tests\Feature;

use App\Enums\FolderType;
use App\Models\Direction;
use App\Models\Document;
use App\Models\Folder;
use App\Models\MetadataDefinition;
use App\Models\Organization;
use App\Models\Service;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BusinessDocumentUXTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $orgA;

    protected Organization $orgB;

    protected User $userA;

    protected User $userB;

    protected Direction $dirA;

    protected Service $serviceA;

    protected Folder $docTypeA;

    protected MetadataDefinition $metaDef1;

    protected MetadataDefinition $metaDef2;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');
        Storage::fake('r2');

        $this->orgA = Organization::factory()->create(['name' => 'Org Alpha']);
        $this->orgB = Organization::factory()->create(['name' => 'Org Beta']);

        $teamForeignKey = config('permission.column_names.team_foreign_key', 'organization_id');

        $permissions = [
            'documents.view', 'documents.create', 'documents.update', 'documents.delete',
            'documents.download', 'documents.share', 'documents.archive', 'folders.view',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        // Setup Org A
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $roleA = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
            $teamForeignKey => $this->orgA->id,
        ]);
        $roleA->syncPermissions($permissions);

        $this->userA = User::factory()->create([
            'organization_id' => $this->orgA->id,
            'email' => 'user_alpha@test.com',
        ]);
        $this->userA->assignRole($roleA);

        // Setup Org B
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgB->id);
        $roleB = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
            $teamForeignKey => $this->orgB->id,
        ]);
        $roleB->syncPermissions($permissions);

        $this->userB = User::factory()->create([
            'organization_id' => $this->orgB->id,
            'email' => 'user_beta@test.com',
        ]);
        $this->userB->assignRole($roleB);

        // Create Direction & Service for Org A
        $this->dirA = Direction::create([
            'organization_id' => $this->orgA->id,
            'name' => 'Direction Financière',
            'code' => 'DF',
        ]);

        $this->serviceA = Service::create([
            'organization_id' => $this->orgA->id,
            'direction_id' => $this->dirA->id,
            'name' => 'Comptabilité Fournisseurs',
            'code' => 'COMPTA-FOURN',
        ]);

        $this->userA->services()->attach($this->serviceA->id, ['organization_id' => $this->orgA->id]);
        $this->userA->update(['primary_service_id' => $this->serviceA->id]);

        // Document Type for Org A linked to Direction & Service
        $this->docTypeA = Folder::create([
            'organization_id' => $this->orgA->id,
            'name' => 'Facture Fournisseur',
            'folder_type' => FolderType::DocumentType,
            'direction_id' => $this->dirA->id,
            'service_id' => $this->serviceA->id,
            'created_by' => $this->userA->id,
        ]);

        // Metadata definitions linked to doc type
        $this->metaDef1 = MetadataDefinition::create([
            'organization_id' => $this->orgA->id,
            'name' => 'Numéro de Facture',
            'key' => 'numero_facture',
            'type' => 'string',
            'is_required' => true,
            'is_active' => true,
        ]);

        $this->metaDef2 = MetadataDefinition::create([
            'organization_id' => $this->orgA->id,
            'name' => 'Montant TTC',
            'key' => 'montant_ttc',
            'type' => 'integer',
            'is_required' => false,
            'is_active' => true,
        ]);

        $this->docTypeA->metadataDefinitions()->attach([
            $this->metaDef1->id => ['is_required' => true, 'order' => 1],
            $this->metaDef2->id => ['is_required' => false, 'order' => 2],
        ]);
    }

    public function test_create_document_page_provides_cascading_directions_and_document_types(): void
    {
        $response = $this->actingAs($this->userA)->get('/documents/create');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Documents/Create')
            ->has('directions')
            ->has('documentTypes')
            ->where('directions.0.name', 'Direction Financière')
            ->where('directions.0.services.0.name', 'Comptabilité Fournisseurs')
            ->where('documentTypes.0.name', 'Facture Fournisseur')
            ->has('documentTypes.0.metadata_definitions', 2)
        );
    }

    public function test_business_import_creates_document_with_direction_service_metadata_and_version(): void
    {
        Queue::fake();

        $file = UploadedFile::fake()->create('facture_abc.pdf', 150, 'application/pdf');

        $payload = [
            'name' => 'Facture ABC Corp',
            'folder_id' => $this->docTypeA->id,
            'document_type_id' => $this->docTypeA->id,
            'direction_id' => $this->dirA->id,
            'service_id' => $this->serviceA->id,
            'file' => $file,
            'metadata' => [
                'numero_facture' => 'FAC-2026-999',
                'montant_ttc' => '450000',
            ],
        ];

        $response = $this->actingAs($this->userA)->post('/documents', $payload);

        $document = Document::where('name', 'Facture ABC Corp')->first();
        $this->assertNotNull($document);

        $response->assertRedirect("/documents/{$document->id}");

        // Assert organizational structure and relationships
        $this->assertEquals($this->orgA->id, $document->organization_id);
        $this->assertEquals($this->dirA->id, $document->direction_id);
        $this->assertEquals($this->serviceA->id, $document->service_id);
        $this->assertEquals($this->docTypeA->id, $document->document_type_id);

        // Assert Version 1 created
        $this->assertEquals(1, $document->versions()->count());
        $this->assertEquals(1, $document->currentVersion->version_number);

        // Assert Metadata stored correctly
        $this->assertDatabaseHas('document_metadata', [
            'document_id' => $document->id,
            'metadata_definition_id' => $this->metaDef1->id,
            'value_string' => 'FAC-2026-999',
        ]);
        $this->assertDatabaseHas('document_metadata', [
            'document_id' => $document->id,
            'metadata_definition_id' => $this->metaDef2->id,
            'value_integer' => 450000,
        ]);

        // Assert Audit log was created
        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $this->orgA->id,
            'user_id' => $this->userA->id,
            'auditable_type' => Document::class,
            'auditable_id' => $document->id,
        ]);
    }

    public function test_document_show_loads_direction_and_service_relations(): void
    {
        $file = UploadedFile::fake()->create('doc_show.pdf', 100, 'application/pdf');
        $document = app(DocumentService::class)->upload([
            'organization_id' => $this->orgA->id,
            'uploaded_by' => $this->userA->id,
            'folder_id' => $this->docTypeA->id,
            'document_type_id' => $this->docTypeA->id,
            'direction_id' => $this->dirA->id,
            'service_id' => $this->serviceA->id,
            'name' => 'Doc Show Test',
        ], $file);

        $response = $this->actingAs($this->userA)->get("/documents/{$document->id}");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Documents/Show')
            ->where('document.name', 'Doc Show Test')
            ->where('document.direction.name', 'Direction Financière')
            ->where('document.service.name', 'Comptabilité Fournisseurs')
            ->has('document.versions', 1)
            ->has('permissions')
        );
    }

    public function test_document_edit_loads_cascading_organizational_data(): void
    {
        $file = UploadedFile::fake()->create('doc_edit.pdf', 100, 'application/pdf');
        $document = app(DocumentService::class)->upload([
            'organization_id' => $this->orgA->id,
            'uploaded_by' => $this->userA->id,
            'folder_id' => $this->docTypeA->id,
            'document_type_id' => $this->docTypeA->id,
            'direction_id' => $this->dirA->id,
            'service_id' => $this->serviceA->id,
            'name' => 'Doc Edit Test',
        ], $file);

        $response = $this->actingAs($this->userA)->get("/documents/{$document->id}/edit");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Documents/Edit')
            ->has('directions')
            ->has('services')
            ->has('documentTypes')
            ->where('currentDirectionId', $this->dirA->id)
            ->where('currentServiceId', $this->serviceA->id)
        );
    }

    public function test_edit_document_with_file_replacement_creates_new_version(): void
    {
        $file1 = UploadedFile::fake()->create('doc_v1.pdf', 100, 'application/pdf');
        $document = app(DocumentService::class)->upload([
            'organization_id' => $this->orgA->id,
            'uploaded_by' => $this->userA->id,
            'folder_id' => $this->docTypeA->id,
            'document_type_id' => $this->docTypeA->id,
            'direction_id' => $this->dirA->id,
            'service_id' => $this->serviceA->id,
            'name' => 'Doc Versioning Test',
        ], $file1);

        $this->assertEquals(1, $document->versions()->count());

        $file2 = UploadedFile::fake()->create('doc_v2.pdf', 200, 'application/pdf');

        $response = $this->actingAs($this->userA)->put("/documents/{$document->id}", [
            'name' => 'Doc Versioning Test Updated',
            'file' => $file2,
            'metadata' => [
                'numero_facture' => 'FAC-V2-001',
            ],
        ]);

        $response->assertRedirect("/documents/{$document->id}");
        $document->refresh();

        $this->assertEquals(2, $document->versions()->count());
        $this->assertEquals(2, $document->currentVersion->version_number);
        $this->assertEquals('Doc Versioning Test Updated', $document->name);
    }

    public function test_search_view_receives_directions_services_and_document_types(): void
    {
        $response = $this->actingAs($this->userA)->get('/search');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Search/Index')
            ->has('directions')
            ->has('services')
            ->has('documentTypes')
            ->where('directions.0.name', 'Direction Financière')
            ->where('documentTypes.0.name', 'Facture Fournisseur')
        );
    }

    public function test_search_by_direction_service_and_document_type_filters_results(): void
    {
        $file = UploadedFile::fake()->create('doc_searchable.pdf', 100, 'application/pdf');
        $docMatch = app(DocumentService::class)->upload([
            'organization_id' => $this->orgA->id,
            'uploaded_by' => $this->userA->id,
            'folder_id' => $this->docTypeA->id,
            'document_type_id' => $this->docTypeA->id,
            'direction_id' => $this->dirA->id,
            'service_id' => $this->serviceA->id,
            'name' => 'Contrat Special Alpha',
        ], $file);

        $response = $this->actingAs($this->userA)->get('/search?'.http_build_query([
            'direction_id' => $this->dirA->id,
            'service_id' => $this->serviceA->id,
            'document_type_id' => $this->docTypeA->id,
            'q' => 'Special',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Search/Index')
            ->has('results.data', 1)
            ->where('results.data.0.id', $docMatch->id)
            ->where('results.data.0.name', 'Contrat Special Alpha')
        );
    }

    public function test_cross_tenant_isolation_prevents_viewing_or_editing_other_tenant_document(): void
    {
        $file = UploadedFile::fake()->create('doc_secret.pdf', 100, 'application/pdf');
        $docA = app(DocumentService::class)->upload([
            'organization_id' => $this->orgA->id,
            'uploaded_by' => $this->userA->id,
            'folder_id' => $this->docTypeA->id,
            'document_type_id' => $this->docTypeA->id,
            'name' => 'Secret Alpha',
        ], $file);

        // User B from Org B cannot view Doc A
        $responseShow = $this->actingAs($this->userB)->get("/documents/{$docA->id}");
        $this->assertTrue(in_array($responseShow->status(), [403, 404]));

        // User B cannot edit Doc A
        $responseEdit = $this->actingAs($this->userB)->get("/documents/{$docA->id}/edit");
        $this->assertTrue(in_array($responseEdit->status(), [403, 404]));

        // User B cannot download Doc A
        $responseDownload = $this->actingAs($this->userB)->get("/documents/{$docA->id}/download");
        $this->assertTrue(in_array($responseDownload->status(), [403, 404]));
    }
}
