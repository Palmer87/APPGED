<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Organization;
use App\Models\User;
use App\Services\AccessControlService;
use App\Services\PreviewService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DocumentPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected PreviewService $previewService;

    protected AccessControlService $aclService;

    protected Organization $orgA;

    protected Organization $orgB;

    protected User $userA;

    protected User $userB;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');

        $this->aclService = app(AccessControlService::class);
        $this->previewService = app(PreviewService::class);

        $this->orgA = Organization::factory()->create();
        $this->orgB = Organization::factory()->create();

        $this->seed(RolePermissionSeeder::class);

        $this->userA = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->userB = User::factory()->create(['organization_id' => $this->orgB->id]);

        $perms = ['documents.view', 'documents.download', 'documents.create', 'documents.update', 'documents.delete'];
        foreach ($perms as $p) {
            Permission::findOrCreate($p, 'web');
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $roleA = Role::create(['name' => 'member_a', 'guard_name' => 'web']);
        $roleA->givePermissionTo($perms);
        $this->userA->assignRole($roleA);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgB->id);
        $roleB = Role::create(['name' => 'member_b', 'guard_name' => 'web']);
        $roleB->givePermissionTo($perms);
        $this->userB->assignRole($roleB);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $superAdminRole = Role::findOrCreate('super-admin', 'web');
        $this->superAdmin = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->superAdmin->assignRole($superAdminRole);
    }

    protected function createStoredDocument(array $attributes = []): Document
    {
        $orgId = $attributes['organization_id'] ?? $this->orgA->id;
        $uploaderId = $attributes['uploaded_by'] ?? $this->userA->id;
        $ext = $attributes['extension'] ?? 'pdf';
        $mime = $attributes['mime_type'] ?? 'application/pdf';
        $path = $attributes['storage_path'] ?? "organizations/{$orgId}/documents/test_{$ext}.".$ext;
        $disk = $attributes['storage_disk'] ?? 'private';

        Storage::disk($disk)->put($path, '%PDF-1.4 sample content');

        return Document::factory()->create(array_merge([
            'organization_id' => $orgId,
            'uploaded_by' => $uploaderId,
            'extension' => $ext,
            'mime_type' => $mime,
            'storage_disk' => $disk,
            'storage_path' => $path,
            'file_name' => "test_document.{$ext}",
            'status' => 'active',
        ], $attributes));
    }

    // ==========================================
    // 1. PDF PREVIEW
    // ==========================================

    public function test_authorized_user_can_preview_pdf(): void
    {
        $doc = $this->createStoredDocument(['extension' => 'pdf', 'mime_type' => 'application/pdf']);

        $response = $this->actingAs($this->userA)->get(route('documents.preview', $doc));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('inline', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('test_document.pdf', (string) $response->headers->get('Content-Disposition'));
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    // ==========================================
    // 2. IMAGE PREVIEWS (JPG, PNG, WEBP)
    // ==========================================

    public function test_authorized_user_can_preview_jpeg_image(): void
    {
        $doc = $this->createStoredDocument(['extension' => 'jpg', 'mime_type' => 'image/jpeg']);

        $response = $this->actingAs($this->userA)->get(route('documents.preview', $doc));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/jpeg');
        $this->assertStringContainsString('inline', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_authorized_user_can_preview_png_image(): void
    {
        $doc = $this->createStoredDocument(['extension' => 'png', 'mime_type' => 'image/png']);

        $response = $this->actingAs($this->userA)->get(route('documents.preview', $doc));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
        $this->assertStringContainsString('inline', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_authorized_user_can_preview_webp_image(): void
    {
        $doc = $this->createStoredDocument(['extension' => 'webp', 'mime_type' => 'image/webp']);

        $response = $this->actingAs($this->userA)->get(route('documents.preview', $doc));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/webp');
        $this->assertStringContainsString('inline', (string) $response->headers->get('Content-Disposition'));
    }

    // ==========================================
    // 3. PERMISSIONS & ACLS
    // ==========================================

    public function test_user_with_documents_view_can_preview_document(): void
    {
        $doc = $this->createStoredDocument();

        $response = $this->actingAs($this->userA)->get(route('documents.preview', $doc));
        $response->assertOk();
    }

    public function test_user_without_documents_view_is_forbidden(): void
    {
        // User without any roles/permissions
        $restrictedUser = User::factory()->create(['organization_id' => $this->orgA->id]);
        $doc = $this->createStoredDocument();

        $response = $this->actingAs($restrictedUser)->get(route('documents.preview', $doc));
        $response->assertForbidden();
    }

    public function test_user_with_only_documents_view_without_download_can_preview(): void
    {
        $viewerUser = User::factory()->create(['organization_id' => $this->orgA->id]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $viewerRole = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);
        $viewerRole->givePermissionTo('documents.view');
        $viewerUser->assignRole($viewerRole);

        $doc = $this->createStoredDocument();

        $response = $this->actingAs($viewerUser)->get(route('documents.preview', $doc));
        $response->assertOk();
    }

    public function test_user_with_only_download_permission_without_view_is_forbidden(): void
    {
        $downloaderUser = User::factory()->create(['organization_id' => $this->orgA->id]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $downloaderRole = Role::firstOrCreate(['name' => 'downloader_only', 'guard_name' => 'web']);
        $downloaderRole->givePermissionTo('documents.download');
        $downloaderUser->assignRole($downloaderRole);

        $doc = $this->createStoredDocument();

        $response = $this->actingAs($downloaderUser)->get(route('documents.preview', $doc));
        $response->assertForbidden();
    }

    // ==========================================
    // 4. MULTI-TENANT ISOLATION
    // ==========================================

    public function test_user_cannot_preview_document_from_another_organization(): void
    {
        $docB = $this->createStoredDocument(['organization_id' => $this->orgB->id, 'uploaded_by' => $this->userB->id]);

        $response = $this->actingAs($this->userA)->get(route('documents.preview', $docB));
        $response->assertForbidden();
    }

    // ==========================================
    // 5. SUPER-ADMIN ACCESS
    // ==========================================

    public function test_super_admin_can_preview_documents_of_any_organization(): void
    {
        $docA = $this->createStoredDocument(['organization_id' => $this->orgA->id]);
        $docB = $this->createStoredDocument(['organization_id' => $this->orgB->id, 'uploaded_by' => $this->userB->id]);

        $responseA = $this->actingAs($this->superAdmin)->get(route('documents.preview', $docA));
        $responseA->assertOk();

        $responseB = $this->actingAs($this->superAdmin)->get(route('documents.preview', $docB));
        $responseB->assertOk();
    }

    // ==========================================
    // 6. SOFT DELETE
    // ==========================================

    public function test_soft_deleted_document_returns_404(): void
    {
        $doc = $this->createStoredDocument();
        $doc->delete();

        $response = $this->actingAs($this->userA)->get(route('documents.preview', $doc));
        $response->assertNotFound();
    }

    // ==========================================
    // 7. MISSING STORAGE FILE
    // ==========================================

    public function test_missing_storage_file_returns_clean_404(): void
    {
        $doc = $this->createStoredDocument();
        Storage::disk('private')->delete($doc->storage_path);

        $response = $this->actingAs($this->userA)->get(route('documents.preview', $doc));
        $response->assertNotFound();
    }

    // ==========================================
    // 8. UNSUPPORTED FORMATS (415)
    // ==========================================

    public function test_unsupported_formats_return_415(): void
    {
        $formats = [
            ['docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            ['xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            ['pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'],
            ['zip', 'application/zip'],
            ['txt', 'text/plain'],
        ];

        foreach ($formats as [$ext, $mime]) {
            $doc = $this->createStoredDocument(['extension' => $ext, 'mime_type' => $mime]);
            $response = $this->actingAs($this->userA)->get(route('documents.preview', $doc));
            $response->assertStatus(415);
        }
    }

    // ==========================================
    // 9. VERSIONS PREVIEW
    // ==========================================

    public function test_preview_specific_document_version(): void
    {
        $doc = $this->createStoredDocument(['extension' => 'pdf', 'mime_type' => 'application/pdf']);

        $versionPath = "organizations/{$this->orgA->id}/documents/{$doc->id}/versions/2/v2.pdf";
        Storage::disk('private')->put($versionPath, '%PDF-1.4 Version 2 content');

        $version = DocumentVersion::create([
            'document_id' => $doc->id,
            'uploaded_by' => $this->userA->id,
            'version_number' => 2,
            'file_name' => 'v2.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size' => 1024,
            'storage_disk' => 'private',
            'storage_path' => $versionPath,
        ]);

        $response = $this->actingAs($this->userA)->get(route('documents.versions.preview', [
            'document' => $doc,
            'version' => $version,
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('inline', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('v2.pdf', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_version_belonging_to_another_document_is_rejected(): void
    {
        $doc1 = $this->createStoredDocument();
        $doc2 = $this->createStoredDocument();

        $versionForDoc2 = DocumentVersion::create([
            'document_id' => $doc2->id,
            'uploaded_by' => $this->userA->id,
            'version_number' => 1,
            'file_name' => 'v1.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size' => 1024,
            'storage_disk' => 'private',
            'storage_path' => 'dummy/path.pdf',
        ]);

        // Attempt to access doc2's version under doc1
        $response = $this->actingAs($this->userA)->get(route('documents.versions.preview', [
            'document' => $doc1,
            'version' => $versionForDoc2,
        ]));

        $response->assertForbidden();
    }

    public function test_version_belonging_to_another_organization_is_rejected(): void
    {
        $docB = $this->createStoredDocument(['organization_id' => $this->orgB->id, 'uploaded_by' => $this->userB->id]);

        $versionB = DocumentVersion::create([
            'document_id' => $docB->id,
            'uploaded_by' => $this->userB->id,
            'version_number' => 1,
            'file_name' => 'v1_b.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size' => 1024,
            'storage_disk' => 'private',
            'storage_path' => 'dummy/path_b.pdf',
        ]);

        $response = $this->actingAs($this->userA)->get(route('documents.versions.preview', [
            'document' => $docB,
            'version' => $versionB,
        ]));

        $response->assertForbidden();
    }

    // ==========================================
    // 10. UNREACHABLE STORAGE / SERVICE UNIT
    // ==========================================

    public function test_preview_service_requires_authenticated_user(): void
    {
        $doc = $this->createStoredDocument();

        $response = $this->getJson(route('documents.preview', $doc));
        $response->assertUnauthorized();
    }
}
