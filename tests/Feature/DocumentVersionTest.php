<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Organization;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class DocumentVersionTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    private User $user;

    private Role $role;

    private DocumentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::factory()->create();
        $this->user = User::factory()->for($this->org)->create();
        $this->service = new DocumentService;

        // Set up permissions for the user's organization team
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->org->id);
        Permission::findOrCreate('documents.create', 'web');
        Permission::findOrCreate('documents.update', 'web');
        Permission::findOrCreate('documents.download', 'web');
        Permission::findOrCreate('documents.view', 'web');
        Permission::findOrCreate('documents.delete', 'web');
        Permission::findOrCreate('documents.restore', 'web');

        $this->role = Role::findOrCreate('editor', 'web');
        $this->role->givePermissionTo([
            'documents.create',
            'documents.update',
            'documents.download',
            'documents.view',
            'documents.delete',
            'documents.restore',
        ]);
        $this->user->assignRole($this->role);
    }

    /**
     * Helper: create a document via the service.
     */
    private function makeDocument(): Document
    {
        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        return $this->service->upload([
            'organization_id' => $this->org->id,
            'uploaded_by' => $this->user->id,
            'name' => 'Test Document',
        ], $file);
    }

    // ===================================================================
    // TEST 1: Création du document crée Version 1
    // ===================================================================

    public function test_creating_a_document_also_creates_version_1(): void
    {
        Storage::fake('private');

        $document = $this->makeDocument();

        $this->assertDatabaseHas('document_versions', [
            'document_id' => $document->id,
            'version_number' => 1,
        ]);
        $this->assertCount(1, $document->versions);
    }

    // ===================================================================
    // TEST 2: Upload d'une deuxième version
    // ===================================================================

    public function test_uploading_a_second_version_creates_version_2(): void
    {
        Storage::fake('private');

        $document = $this->makeDocument();

        $this->actingAs($this->user);
        $file2 = UploadedFile::fake()->create('v2.pdf', 200, 'application/pdf');
        $version2 = $this->service->uploadNewVersion($document, $file2, 'Updated version');

        $this->assertEquals(2, $version2->version_number);
        $this->assertCount(2, $document->fresh()->versions);
    }

    // ===================================================================
    // TEST 3: Les deux fichiers existent dans Storage::fake('private')
    // ===================================================================

    public function test_both_version_files_exist_on_disk(): void
    {
        Storage::fake('private');

        $document = $this->makeDocument();

        $this->actingAs($this->user);
        $file2 = UploadedFile::fake()->create('v2.pdf', 200, 'application/pdf');
        $this->service->uploadNewVersion($document, $file2);

        $versions = $document->fresh()->versions()->orderBy('version_number')->get();

        Storage::disk('private')->assertExists($versions[0]->storage_path);
        Storage::disk('private')->assertExists($versions[1]->storage_path);
    }

    // ===================================================================
    // TEST 4: Les storage_path sont différents
    // ===================================================================

    public function test_storage_paths_are_different_between_versions(): void
    {
        Storage::fake('private');

        $document = $this->makeDocument();

        $this->actingAs($this->user);
        $file2 = UploadedFile::fake()->create('v2.pdf', 200, 'application/pdf');
        $this->service->uploadNewVersion($document, $file2);

        $versions = $document->fresh()->versions()->orderBy('version_number')->get();

        $this->assertNotEquals($versions[0]->storage_path, $versions[1]->storage_path);
    }

    // ===================================================================
    // TEST 5: Le Document correspond à V2
    // ===================================================================

    public function test_document_reflects_latest_version_after_upload(): void
    {
        Storage::fake('private');

        $document = $this->makeDocument();

        $this->actingAs($this->user);
        $file2 = UploadedFile::fake()->create('v2.docx', 200, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $version2 = $this->service->uploadNewVersion($document, $file2);

        $document->refresh();

        $this->assertEquals($version2->file_name, $document->file_name);
        $this->assertEquals($version2->storage_path, $document->storage_path);
        $this->assertEquals($version2->size, $document->size);
    }

    // ===================================================================
    // TEST 6: User d'une autre organisation ne peut pas créer une version
    // ===================================================================

    public function test_user_from_another_org_cannot_create_version(): void
    {
        Storage::fake('private');

        $document = $this->makeDocument();

        $orgB = Organization::factory()->create();
        $userB = User::factory()->for($orgB)->create();
        app(PermissionRegistrar::class)->setPermissionsTeamId($orgB->id);
        $roleB = Role::findOrCreate('editor-b', 'web');
        Permission::findOrCreate('documents.update', 'web');
        $roleB->givePermissionTo('documents.update');
        $userB->assignRole($roleB);

        $this->actingAs($userB);
        $file2 = UploadedFile::fake()->create('v2.pdf', 100, 'application/pdf');

        $this->expectException(HttpException::class);
        $this->service->uploadNewVersion($document, $file2);
    }

    // ===================================================================
    // TEST 7: User d'une autre organisation ne peut pas télécharger une version
    // ===================================================================

    public function test_user_from_another_org_cannot_download_version(): void
    {
        Storage::fake('private');

        $document = $this->makeDocument();
        $version = $document->versions()->first();

        $orgB = Organization::factory()->create();
        $userB = User::factory()->for($orgB)->create();
        app(PermissionRegistrar::class)->setPermissionsTeamId($orgB->id);
        $roleB = Role::findOrCreate('editor-b', 'web');
        Permission::findOrCreate('documents.download', 'web');
        $roleB->givePermissionTo('documents.download');
        $userB->assignRole($roleB);

        $this->actingAs($userB);

        $this->expectException(HttpException::class);
        $this->service->downloadVersion($document, $version);
    }

    // ===================================================================
    // TEST 8: User d'une autre organisation ne peut pas restaurer une version
    // ===================================================================

    public function test_user_from_another_org_cannot_restore_version(): void
    {
        Storage::fake('private');

        $document = $this->makeDocument();
        $version = $document->versions()->first();

        $orgB = Organization::factory()->create();
        $userB = User::factory()->for($orgB)->create();
        app(PermissionRegistrar::class)->setPermissionsTeamId($orgB->id);
        $roleB = Role::findOrCreate('editor-b', 'web');
        Permission::findOrCreate('documents.update', 'web');
        $roleB->givePermissionTo('documents.update');
        $userB->assignRole($roleB);

        $this->actingAs($userB);

        $this->expectException(HttpException::class);
        $this->service->restoreVersion($document, $version);
    }

    // ===================================================================
    // TEST 9: Restaurer V1 crée V3
    // ===================================================================

    public function test_restoring_v1_creates_v3(): void
    {
        Storage::fake('private');

        $document = $this->makeDocument();

        $this->actingAs($this->user);

        $file2 = UploadedFile::fake()->create('v2.pdf', 200, 'application/pdf');
        $this->service->uploadNewVersion($document, $file2);

        $v1 = $document->versions()->where('version_number', 1)->first();
        $v3 = $this->service->restoreVersion($document, $v1);

        $this->assertEquals(3, $v3->version_number);
        $this->assertCount(3, $document->fresh()->versions);
    }

    // ===================================================================
    // TEST 10: V1 et V2 restent physiquement présents après restauration
    // ===================================================================

    public function test_v1_and_v2_files_remain_after_restore(): void
    {
        Storage::fake('private');

        $document = $this->makeDocument();

        $this->actingAs($this->user);

        $file2 = UploadedFile::fake()->create('v2.pdf', 200, 'application/pdf');
        $this->service->uploadNewVersion($document, $file2);

        $v1 = $document->versions()->where('version_number', 1)->first();
        $this->service->restoreVersion($document, $v1);

        // All 3 version files must exist
        $versions = $document->fresh()->versions()->orderBy('version_number')->get();
        foreach ($versions as $version) {
            Storage::disk('private')->assertExists($version->storage_path);
        }
    }

    // ===================================================================
    // TEST 11: Téléchargement d'une version précise
    // ===================================================================

    public function test_downloading_a_specific_version_returns_streamed_response(): void
    {
        Storage::fake('private');

        $document = $this->makeDocument();

        $this->actingAs($this->user);

        $version = $document->versions()->first();
        $response = $this->service->downloadVersion($document, $version);

        $this->assertInstanceOf(StreamedResponse::class, $response);
    }

    // ===================================================================
    // TEST 12: documents.update obligatoire pour créer une version
    // ===================================================================

    public function test_user_without_update_permission_cannot_create_version(): void
    {
        Storage::fake('private');

        $document = $this->makeDocument();

        $userNoPerms = User::factory()->for($this->org)->create();
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->org->id);
        $viewerRole = Role::findOrCreate('viewer', 'web');
        Permission::findOrCreate('documents.view', 'web');
        $viewerRole->givePermissionTo('documents.view');
        $userNoPerms->assignRole($viewerRole);

        $this->actingAs($userNoPerms);

        $file2 = UploadedFile::fake()->create('v2.pdf', 100, 'application/pdf');

        $this->expectException(AuthorizationException::class);
        $this->service->uploadNewVersion($document, $file2);
    }

    // ===================================================================
    // TEST 13: documents.download obligatoire pour télécharger
    // ===================================================================

    public function test_user_without_download_permission_cannot_download_version(): void
    {
        Storage::fake('private');

        $document = $this->makeDocument();
        $version = $document->versions()->first();

        $userNoPerms = User::factory()->for($this->org)->create();
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->org->id);
        $viewerRole = Role::findOrCreate('viewer-no-dl', 'web');
        Permission::findOrCreate('documents.view', 'web');
        $viewerRole->givePermissionTo('documents.view');
        $userNoPerms->assignRole($viewerRole);

        $this->actingAs($userNoPerms);

        $this->expectException(AuthorizationException::class);
        $this->service->downloadVersion($document, $version);
    }

    // ===================================================================
    // TEST 14: documents.update obligatoire pour restaurer
    // ===================================================================

    public function test_user_without_update_permission_cannot_restore_version(): void
    {
        Storage::fake('private');

        $document = $this->makeDocument();
        $version = $document->versions()->first();

        $userNoPerms = User::factory()->for($this->org)->create();
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->org->id);
        $viewerRole = Role::findOrCreate('viewer-no-update', 'web');
        Permission::findOrCreate('documents.view', 'web');
        $viewerRole->givePermissionTo('documents.view');
        $userNoPerms->assignRole($viewerRole);

        $this->actingAs($userNoPerms);

        $this->expectException(AuthorizationException::class);
        $this->service->restoreVersion($document, $version);
    }

    // ===================================================================
    // TEST 15: Version appartenant à un autre document refusée
    // ===================================================================

    public function test_version_belonging_to_another_document_is_refused(): void
    {
        Storage::fake('private');

        $docA = $this->makeDocument();

        $file2 = UploadedFile::fake()->create('b.pdf', 100, 'application/pdf');
        $docB = $this->service->upload([
            'organization_id' => $this->org->id,
            'uploaded_by' => $this->user->id,
            'name' => 'Doc B',
        ], $file2);

        $versionB = $docB->versions()->first();

        $this->actingAs($this->user);

        $this->expectException(HttpException::class);
        $this->service->restoreVersion($docA, $versionB);
    }

    // ===================================================================
    // TEST 16: Version inexistante refusée
    // ===================================================================

    public function test_non_existent_version_is_refused(): void
    {
        Storage::fake('private');

        $document = $this->makeDocument();

        $this->actingAs($this->user);

        // Create a fake version that doesn't belong to the document
        $fakeVersion = new DocumentVersion;
        $fakeVersion->id = 99999;
        $fakeVersion->document_id = 99999;
        $fakeVersion->version_number = 1;
        $fakeVersion->storage_path = 'nonexistent/path.pdf';
        $fakeVersion->storage_disk = 'private';
        $fakeVersion->mime_type = 'application/pdf';
        $fakeVersion->extension = 'pdf';
        $fakeVersion->size = 100;

        $this->expectException(HttpException::class);
        $this->service->restoreVersion($document, $fakeVersion);
    }

    // ===================================================================
    // TEST 17: Document supprimé refusé
    // ===================================================================

    public function test_cannot_upload_version_to_deleted_document(): void
    {
        Storage::fake('private');

        $document = $this->makeDocument();
        $this->service->delete($document);

        $this->actingAs($this->user);

        $file2 = UploadedFile::fake()->create('v2.pdf', 100, 'application/pdf');

        $this->expectException(HttpException::class);
        $this->service->uploadNewVersion($document->fresh(), $file2);
    }

    // ===================================================================
    // TEST 18: Super-admin conserve l'accès global
    // ===================================================================

    public function test_super_admin_can_create_version_on_any_document(): void
    {
        Storage::fake('private');

        $document = $this->makeDocument();

        // Create super-admin in DIFFERENT org to truly verify cross-org access
        $orgB = Organization::factory()->create();
        $superAdmin = User::factory()->for($orgB)->create();
        app(PermissionRegistrar::class)->setPermissionsTeamId($orgB->id);
        $superRole = Role::findOrCreate('super-admin', 'web');
        $superAdmin->assignRole($superRole);

        $this->actingAs($superAdmin);

        $file2 = UploadedFile::fake()->create('v2.pdf', 200, 'application/pdf');
        $version = $this->service->uploadNewVersion($document, $file2, 'Super admin version');

        $this->assertEquals(2, $version->version_number);
    }

    // ===================================================================
    // TEST 19: Échec DB → suppression du fichier nouvellement uploadé
    // ===================================================================

    public function test_failed_db_transaction_cleans_up_orphan_file(): void
    {
        Storage::fake('private');

        $document = $this->makeDocument();

        $this->actingAs($this->user);

        // Count files on disk before
        $filesBefore = count(Storage::disk('private')->allFiles());

        // Use a listener on Eloquent to force an exception after the file is stored
        // by hooking into DocumentVersion creation
        DocumentVersion::creating(function (DocumentVersion $version) {
            // Only trigger on version_number > 1 to let the initial version succeed
            if ($version->version_number > 1) {
                throw new \RuntimeException('Simulated DB failure');
            }
        });

        $file2 = UploadedFile::fake()->create('v2.pdf', 200, 'application/pdf');

        try {
            $this->service->uploadNewVersion($document, $file2);
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Simulated DB failure', $e->getMessage());

            // No new files should remain on disk (orphan was cleaned up)
            $filesAfter = count(Storage::disk('private')->allFiles());
            $this->assertEquals($filesBefore, $filesAfter);

            // Version count should still be 1
            $this->assertCount(1, $document->fresh()->versions);
        }
    }
}
