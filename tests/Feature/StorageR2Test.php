<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Organization;
use App\Models\User;
use App\Services\DocumentLifecycleService;
use App\Services\DocumentService;
use App\Services\PreviewService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class StorageR2Test extends TestCase
{
    use RefreshDatabase;

    protected Organization $orgA;

    protected Organization $orgB;

    protected User $adminA;

    protected User $userA;

    protected User $userB;

    protected DocumentService $documentService;

    protected DocumentLifecycleService $lifecycleService;

    protected PreviewService $previewService;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');
        Storage::fake('r2');
        Storage::fake('s3');

        $this->seed(RolePermissionSeeder::class);

        $this->documentService = app(DocumentService::class);
        $this->lifecycleService = app(DocumentLifecycleService::class);
        $this->previewService = app(PreviewService::class);

        $this->orgA = Organization::factory()->create();
        $this->orgB = Organization::factory()->create();

        $this->adminA = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->userA = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->userB = User::factory()->create(['organization_id' => $this->orgB->id]);

        $permissions = [
            'documents.view', 'documents.create', 'documents.update',
            'documents.delete', 'documents.download', 'documents.archive',
            'documents.restore',
        ];
        foreach ($permissions as $p) {
            Permission::findOrCreate($p, 'web');
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $roleA = Role::create(['name' => 'admin_a_storage', 'guard_name' => 'web']);
        $roleA->givePermissionTo($permissions);
        $this->adminA->assignRole($roleA);

        $readerRole = Role::create(['name' => 'reader_a_storage', 'guard_name' => 'web']);
        $readerRole->givePermissionTo(['documents.view', 'documents.download']);
        $this->userA->assignRole($readerRole);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgB->id);
        $roleB = Role::create(['name' => 'admin_b_storage', 'guard_name' => 'web']);
        $roleB->givePermissionTo($permissions);
        $this->userB->assignRole($roleB);

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    }

    /**
     * Act as a user with correct Spatie team ID set.
     */
    protected function actAsUser(User $user): static
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($user->organization_id);

        return $this->actingAs($user);
    }

    /**
     * Create a document with a physical file on a given disk.
     */
    protected function createDocument(string $disk = 'private', array $attributes = []): Document
    {
        $orgId = $attributes['organization_id'] ?? $this->orgA->id;
        $uploaderId = $attributes['uploaded_by'] ?? $this->adminA->id;
        $path = $attributes['storage_path'] ?? "organizations/{$orgId}/documents/doc_".uniqid().'.pdf';

        Storage::disk($disk)->put($path, 'sample physical document content');

        $doc = Document::factory()->create(array_merge([
            'organization_id' => $orgId,
            'uploaded_by' => $uploaderId,
            'name' => 'Test Document',
            'file_name' => 'test.pdf',
            'storage_disk' => $disk,
            'storage_path' => $path,
            'extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'status' => 'active',
        ], $attributes));

        // Create matching version
        DocumentVersion::factory()->create([
            'document_id' => $doc->id,
            'uploaded_by' => $uploaderId,
            'version_number' => 1,
            'file_name' => basename($path),
            'mime_type' => $doc->mime_type,
            'extension' => $doc->extension,
            'size' => $doc->size,
            'storage_disk' => $disk,
            'storage_path' => $path,
            'comment' => 'Initial version',
        ]);

        return $doc;
    }

    // ==========================================
    // 1. CONFIGURATION TESTS
    // ==========================================

    public function test_s3_disk_is_configured(): void
    {
        $s3Config = config('filesystems.disks.s3');

        $this->assertNotNull($s3Config);
        $this->assertEquals('s3', $s3Config['driver']);
        $this->assertEquals('private', $s3Config['visibility']);
    }

    public function test_r2_disk_is_configured_with_s3_driver(): void
    {
        $r2Config = config('filesystems.disks.r2');

        $this->assertNotNull($r2Config);
        $this->assertEquals('s3', $r2Config['driver']);
        $this->assertEquals('private', $r2Config['visibility']);
    }

    public function test_private_disk_is_configured(): void
    {
        $privateConfig = config('filesystems.disks.private');

        $this->assertNotNull($privateConfig);
        $this->assertEquals('local', $privateConfig['driver']);
    }

    public function test_documents_disk_config_exists(): void
    {
        $documentsDisk = config('filesystems.documents_disk');

        $this->assertNotNull($documentsDisk);
    }

    public function test_no_credentials_are_hardcoded_in_filesystem_config(): void
    {
        $configContent = file_get_contents(config_path('filesystems.php'));

        // Ensure no raw credentials are present
        $this->assertStringNotContainsString('AKID', $configContent);
        $this->assertStringNotContainsString('sk_live', $configContent);
        $this->assertStringNotContainsString('secret_key_value', $configContent);
    }

    // ==========================================
    // 2. UPLOAD TESTS
    // ==========================================

    public function test_upload_stores_file_on_configured_disk(): void
    {
        config(['filesystems.documents_disk' => 'r2']);

        $this->actAsUser($this->adminA);

        $file = UploadedFile::fake()->create('report.pdf', 200, 'application/pdf');

        $document = $this->documentService->upload([
            'organization_id' => $this->orgA->id,
            'uploaded_by' => $this->adminA->id,
            'name' => 'Report on R2',
        ], $file);

        $this->assertEquals('r2', $document->storage_disk);
        Storage::disk('r2')->assertExists($document->storage_path);

        // Verify version also stored on r2
        $version = $document->currentVersion;
        $this->assertEquals('r2', $version->storage_disk);
        Storage::disk('r2')->assertExists($version->storage_path);
    }

    public function test_upload_stores_file_on_private_disk_by_default(): void
    {
        config(['filesystems.documents_disk' => 'private']);

        $this->actAsUser($this->adminA);

        $file = UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf');

        $document = $this->documentService->upload([
            'organization_id' => $this->orgA->id,
            'uploaded_by' => $this->adminA->id,
            'name' => 'Notes on Private',
        ], $file);

        $this->assertEquals('private', $document->storage_disk);
        Storage::disk('private')->assertExists($document->storage_path);
    }

    public function test_upload_respects_explicit_storage_disk_parameter(): void
    {
        $this->actAsUser($this->adminA);

        $file = UploadedFile::fake()->create('override.pdf', 100, 'application/pdf');

        $document = $this->documentService->upload([
            'organization_id' => $this->orgA->id,
            'uploaded_by' => $this->adminA->id,
            'name' => 'Override Disk',
            'storage_disk' => 's3',
        ], $file);

        $this->assertEquals('s3', $document->storage_disk);
        Storage::disk('s3')->assertExists($document->storage_path);
    }

    public function test_upload_storage_path_follows_tenant_structure(): void
    {
        config(['filesystems.documents_disk' => 'r2']);

        $this->actAsUser($this->adminA);

        $file = UploadedFile::fake()->create('contract.pdf', 50, 'application/pdf');

        $document = $this->documentService->upload([
            'organization_id' => $this->orgA->id,
            'uploaded_by' => $this->adminA->id,
            'name' => 'Contract',
        ], $file);

        $this->assertStringStartsWith(
            "organizations/{$this->orgA->id}/documents/{$document->id}/versions/1/",
            $document->storage_path
        );
    }

    // ==========================================
    // 3. VERSION TESTS
    // ==========================================

    public function test_new_version_stored_on_same_disk_as_document(): void
    {
        $doc = $this->createDocument('r2');

        $this->actAsUser($this->adminA);

        $file = UploadedFile::fake()->create('v2.pdf', 150, 'application/pdf');

        $newVersion = $this->documentService->uploadNewVersion($doc, $file, 'Version 2');

        $this->assertEquals('r2', $newVersion->storage_disk);
        $this->assertEquals(2, $newVersion->version_number);
        Storage::disk('r2')->assertExists($newVersion->storage_path);
    }

    public function test_version_restore_creates_new_version_on_same_disk(): void
    {
        $doc = $this->createDocument('r2');
        $v1 = $doc->currentVersion;

        $this->actAsUser($this->adminA);

        // Upload v2
        $file = UploadedFile::fake()->create('v2.pdf', 150, 'application/pdf');
        $this->documentService->uploadNewVersion($doc, $file, 'Version 2');

        // Restore v1 → creates v3
        $v3 = $this->documentService->restoreVersion($doc->fresh(), $v1);

        $this->assertEquals(3, $v3->version_number);
        $this->assertEquals('r2', $v3->storage_disk);
        Storage::disk('r2')->assertExists($v3->storage_path);

        // Original v1 file still intact
        Storage::disk('r2')->assertExists($v1->storage_path);
    }

    public function test_multiple_versions_each_have_separate_files(): void
    {
        $doc = $this->createDocument('r2');
        $v1Path = $doc->currentVersion->storage_path;

        $this->actAsUser($this->adminA);

        $file2 = UploadedFile::fake()->create('v2.pdf', 200, 'application/pdf');
        $v2 = $this->documentService->uploadNewVersion($doc, $file2, 'Version 2');

        $this->assertNotEquals($v1Path, $v2->storage_path);
        Storage::disk('r2')->assertExists($v1Path);
        Storage::disk('r2')->assertExists($v2->storage_path);
    }

    // ==========================================
    // 4. PREVIEW TESTS
    // ==========================================

    public function test_preview_streams_file_from_configured_disk(): void
    {
        $doc = $this->createDocument('r2', [
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
        ]);

        $this->actAsUser($this->adminA);

        $response = $this->previewService->preview($doc, null, $this->adminA);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_preview_rejects_missing_file_on_disk(): void
    {
        $doc = $this->createDocument('r2');

        // Delete the physical file
        Storage::disk('r2')->delete($doc->storage_path);

        $this->actAsUser($this->adminA);

        $this->expectException(HttpException::class);
        $this->previewService->preview($doc, null, $this->adminA);
    }

    public function test_preview_rejects_trashed_document(): void
    {
        $doc = $this->createDocument('r2');
        $doc->delete();

        $this->actAsUser($this->adminA);

        $this->expectException(HttpException::class);
        $this->previewService->preview($doc, null, $this->adminA);
    }

    // ==========================================
    // 5. DOWNLOAD TESTS
    // ==========================================

    public function test_download_from_configured_disk(): void
    {
        $doc = $this->createDocument('r2');

        $this->actAsUser($this->adminA);

        $response = $this->documentService->download($doc);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_download_version_from_configured_disk(): void
    {
        $doc = $this->createDocument('r2');
        $version = $doc->currentVersion;

        $this->actAsUser($this->adminA);

        $response = $this->documentService->downloadVersion($doc, $version);

        $this->assertEquals(200, $response->getStatusCode());
    }

    // ==========================================
    // 6. TRASH TESTS
    // ==========================================

    public function test_trash_preserves_physical_file_on_disk(): void
    {
        $doc = $this->createDocument('r2');
        $storagePath = $doc->storage_path;

        $this->actAsUser($this->adminA);

        $this->lifecycleService->moveToTrash($this->adminA, $doc);

        $this->assertTrue($doc->trashed());
        Storage::disk('r2')->assertExists($storagePath);
    }

    public function test_restore_from_trash_keeps_file_intact(): void
    {
        $doc = $this->createDocument('r2');
        $storagePath = $doc->storage_path;

        $this->actAsUser($this->adminA);

        $this->lifecycleService->moveToTrash($this->adminA, $doc);
        $this->lifecycleService->restoreFromTrash($this->adminA, $doc);

        $this->assertFalse($doc->trashed());
        Storage::disk('r2')->assertExists($storagePath);
    }

    // ==========================================
    // 7. FORCE DELETE TESTS
    // ==========================================

    public function test_force_delete_purges_all_physical_files(): void
    {
        $doc = $this->createDocument('r2');
        $docPath = $doc->storage_path;
        $versionPath = $doc->currentVersion->storage_path;

        // Add a second version
        $this->actAsUser($this->adminA);
        $file2 = UploadedFile::fake()->create('v2.pdf', 100, 'application/pdf');
        $v2 = $this->documentService->uploadNewVersion($doc, $file2, 'Version 2');
        $v2Path = $v2->storage_path;

        // Move to trash first (required by forceDelete)
        $this->lifecycleService->moveToTrash($this->adminA, $doc->fresh());

        // Force delete
        $this->lifecycleService->forceDelete($this->adminA, $doc->fresh());

        Storage::disk('r2')->assertMissing($docPath);
        Storage::disk('r2')->assertMissing($versionPath);
        Storage::disk('r2')->assertMissing($v2Path);

        $this->assertDatabaseMissing('documents', ['id' => $doc->id]);
    }

    // ==========================================
    // 8. ORPHAN CLEANUP TESTS
    // ==========================================

    public function test_upload_cleans_orphan_file_on_db_failure(): void
    {
        config(['filesystems.documents_disk' => 'r2']);

        $this->actAsUser($this->adminA);

        $file = UploadedFile::fake()->create('fail.pdf', 100, 'application/pdf');

        // Force a DB failure by using an invalid organization_id
        try {
            $this->documentService->upload([
                'organization_id' => 0, // invalid FK
                'uploaded_by' => $this->adminA->id,
                'name' => 'Should Fail',
            ], $file);
        } catch (\Throwable) {
            // Expected
        }

        // No orphan files should remain on r2
        $files = Storage::disk('r2')->allFiles();
        $this->assertEmpty($files, 'Orphan files should be cleaned up after DB failure');
    }

    // ==========================================
    // 9. MULTI-TENANT SECURITY TESTS
    // ==========================================

    public function test_cross_tenant_download_is_forbidden(): void
    {
        $doc = $this->createDocument('r2', [
            'organization_id' => $this->orgA->id,
            'uploaded_by' => $this->adminA->id,
        ]);

        $this->actAsUser($this->userB);

        $this->expectException(HttpException::class);
        $this->documentService->downloadVersion($doc, $doc->currentVersion);
    }

    public function test_cross_tenant_preview_is_forbidden(): void
    {
        $doc = $this->createDocument('r2', [
            'organization_id' => $this->orgA->id,
            'uploaded_by' => $this->adminA->id,
        ]);

        $this->actAsUser($this->userB);

        $this->expectException(HttpException::class);
        $this->previewService->preview($doc, null, $this->userB);
    }

    public function test_cross_tenant_version_upload_is_forbidden(): void
    {
        $doc = $this->createDocument('r2', [
            'organization_id' => $this->orgA->id,
            'uploaded_by' => $this->adminA->id,
        ]);

        $this->actAsUser($this->userB);

        $file = UploadedFile::fake()->create('malicious.pdf', 100, 'application/pdf');

        $this->expectException(HttpException::class);
        $this->documentService->uploadNewVersion($doc, $file, 'Cross-tenant attempt');
    }

    // ==========================================
    // 10. API COMPATIBILITY TESTS
    // ==========================================

    public function test_api_upload_works_with_configured_disk(): void
    {
        config(['filesystems.documents_disk' => 'r2']);

        $token = $this->adminA->createToken('test')->plainTextToken;

        $file = UploadedFile::fake()->create('api-doc.pdf', 100, 'application/pdf');

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ])->postJson('/api/v1/documents', [
            'file' => $file,
            'name' => 'API Upload to R2',
        ]);

        $response->assertStatus(201);

        $docId = $response->json('data.id');
        $doc = Document::find($docId);
        $this->assertEquals('r2', $doc->storage_disk);
        Storage::disk('r2')->assertExists($doc->storage_path);
    }

    // ==========================================
    // 11. DISK ABSTRACTION TESTS
    // ==========================================

    public function test_document_on_private_disk_works_after_r2_config(): void
    {
        // Simulate having an older document on 'private' while config is now 'r2'
        config(['filesystems.documents_disk' => 'r2']);

        $doc = $this->createDocument('private');

        $this->actAsUser($this->adminA);

        // Download should still work from 'private' disk because it uses document's storage_disk
        $response = $this->documentService->download($doc);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_preview_works_regardless_of_documents_disk_config(): void
    {
        config(['filesystems.documents_disk' => 'r2']);

        $doc = $this->createDocument('private', [
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
        ]);

        $this->actAsUser($this->adminA);

        $response = $this->previewService->preview($doc, null, $this->adminA);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
