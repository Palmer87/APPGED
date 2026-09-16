<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Organization;
use App\Models\User;
use App\Services\AccessControlService;
use App\Services\DocumentLifecycleService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class DocumentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected DocumentLifecycleService $lifecycleService;

    protected Organization $orgA;

    protected Organization $orgB;

    protected User $adminA;

    protected User $userA;

    protected User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');
        config(['filesystems.default' => 'private']);

        $this->seed(RolePermissionSeeder::class);

        $this->lifecycleService = app(DocumentLifecycleService::class);

        $this->orgA = Organization::factory()->create();
        $this->orgB = Organization::factory()->create();

        $this->adminA = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->userA = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->userB = User::factory()->create(['organization_id' => $this->orgB->id]);

        $permissions = [
            'documents.view', 'documents.create', 'documents.update',
            'documents.delete', 'documents.download', 'documents.archive', 'documents.restore',
        ];
        foreach ($permissions as $p) {
            Permission::findOrCreate($p, 'web');
        }

        // Admin A has all document permissions
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $roleAdmin = Role::create(['name' => 'admin_a', 'guard_name' => 'web']);
        $roleAdmin->givePermissionTo($permissions);
        $this->adminA->assignRole($roleAdmin);

        // User A only has view & download permissions (no update/delete/archive/restore)
        $roleReader = Role::create(['name' => 'reader_a', 'guard_name' => 'web']);
        $roleReader->givePermissionTo(['documents.view', 'documents.download']);
        $this->userA->assignRole($roleReader);

        // User B belongs to orgB with all permissions in orgB
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgB->id);
        $roleB = Role::create(['name' => 'admin_b', 'guard_name' => 'web']);
        $roleB->givePermissionTo($permissions);
        $this->userB->assignRole($roleB);

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    }

    protected function createDocument(array $attributes = []): Document
    {
        $orgId = $attributes['organization_id'] ?? $this->orgA->id;
        $uploaderId = $attributes['uploaded_by'] ?? $this->adminA->id;
        $path = $attributes['storage_path'] ?? "organizations/{$orgId}/documents/doc_".uniqid().'.pdf';

        Storage::disk('private')->put($path, 'sample physical document content');

        return Document::factory()->create(array_merge([
            'organization_id' => $orgId,
            'uploaded_by' => $uploaderId,
            'name' => 'Sample Doc',
            'file_name' => 'sample.pdf',
            'storage_disk' => 'private',
            'storage_path' => $path,
            'extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'status' => 'active',
        ], $attributes));
    }

    // ==========================================
    // 1. ARCHIVE / UNARCHIVE TESTS
    // ==========================================

    public function test_authorized_user_can_archive_an_active_document(): void
    {
        $doc = $this->createDocument(['status' => 'active']);

        $response = $this->actingAs($this->adminA)->postJson(route('documents.archive', $doc));

        $response->assertOk();
        $response->assertJsonPath('document.status', 'archived');
        $this->assertDatabaseHas('documents', ['id' => $doc->id, 'status' => 'archived']);
    }

    public function test_archiving_an_already_archived_document_is_idempotent(): void
    {
        $doc = $this->createDocument(['status' => 'archived']);

        $response = $this->actingAs($this->adminA)->postJson(route('documents.archive', $doc));

        $response->assertOk();
        $response->assertJsonPath('document.status', 'archived');
        $this->assertDatabaseHas('documents', ['id' => $doc->id, 'status' => 'archived']);
    }

    public function test_cannot_archive_a_trashed_document(): void
    {
        $doc = $this->createDocument(['status' => 'active']);
        $doc->delete(); // Soft delete

        $this->expectException(HttpException::class);
        $this->lifecycleService->archive($this->adminA, $doc);
    }

    public function test_unauthorized_user_cannot_archive_document(): void
    {
        $doc = $this->createDocument(['status' => 'active']);

        $response = $this->actingAs($this->userA)->postJson(route('documents.archive', $doc));

        $response->assertForbidden();
        $this->assertDatabaseHas('documents', ['id' => $doc->id, 'status' => 'active']);
    }

    public function test_authorized_user_can_unarchive_an_archived_document(): void
    {
        $doc = $this->createDocument(['status' => 'archived']);

        $response = $this->actingAs($this->adminA)->postJson(route('documents.unarchive', $doc));

        $response->assertOk();
        $response->assertJsonPath('document.status', 'active');
        $this->assertDatabaseHas('documents', ['id' => $doc->id, 'status' => 'active']);
    }

    public function test_unarchiving_an_already_active_document_is_idempotent(): void
    {
        $doc = $this->createDocument(['status' => 'active']);

        $response = $this->actingAs($this->adminA)->postJson(route('documents.unarchive', $doc));

        $response->assertOk();
        $response->assertJsonPath('document.status', 'active');
        $this->assertDatabaseHas('documents', ['id' => $doc->id, 'status' => 'active']);
    }

    public function test_cannot_unarchive_a_trashed_document(): void
    {
        $doc = $this->createDocument(['status' => 'archived']);
        $doc->delete(); // Soft delete

        $this->expectException(HttpException::class);
        $this->lifecycleService->unarchive($this->adminA, $doc);
    }

    public function test_unauthorized_user_cannot_unarchive_document(): void
    {
        $doc = $this->createDocument(['status' => 'archived']);

        $response = $this->actingAs($this->userA)->postJson(route('documents.unarchive', $doc));

        $response->assertForbidden();
        $this->assertDatabaseHas('documents', ['id' => $doc->id, 'status' => 'archived']);
    }

    // ==========================================
    // 2. TRASH / SOFT DELETE TESTS
    // ==========================================

    public function test_authorized_user_can_move_active_document_to_trash(): void
    {
        $doc = $this->createDocument(['status' => 'active']);

        $response = $this->actingAs($this->adminA)->deleteJson(route('documents.destroy', $doc));

        $response->assertOk();
        $this->assertSoftDeleted('documents', ['id' => $doc->id]);
        $this->assertTrue(Storage::disk('private')->exists($doc->storage_path));
    }

    public function test_authorized_user_can_move_archived_document_to_trash(): void
    {
        $doc = $this->createDocument(['status' => 'archived']);

        $response = $this->actingAs($this->adminA)->deleteJson(route('documents.destroy', $doc));

        $response->assertOk();
        $this->assertSoftDeleted('documents', ['id' => $doc->id, 'status' => 'archived']);
        $this->assertTrue(Storage::disk('private')->exists($doc->storage_path));
    }

    public function test_unauthorized_user_cannot_move_document_to_trash(): void
    {
        $doc = $this->createDocument(['status' => 'active']);

        $response = $this->actingAs($this->userA)->deleteJson(route('documents.destroy', $doc));

        $response->assertForbidden();
        $this->assertNotSoftDeleted('documents', ['id' => $doc->id]);
    }

    public function test_trashing_preserves_physical_file_on_storage(): void
    {
        $doc = $this->createDocument(['status' => 'active']);
        $path = $doc->storage_path;

        $this->lifecycleService->moveToTrash($this->adminA, $doc);

        $this->assertSoftDeleted('documents', ['id' => $doc->id]);
        $this->assertTrue(Storage::disk('private')->exists($path));
    }

    // ==========================================
    // 3. RESTORE TESTS
    // ==========================================

    public function test_authorized_user_can_restore_trashed_active_document(): void
    {
        $doc = $this->createDocument(['status' => 'active']);
        $doc->delete();

        $response = $this->actingAs($this->adminA)->postJson(route('documents.restore', $doc));

        $response->assertOk();
        $response->assertJsonPath('document.status', 'active');
        $this->assertNotSoftDeleted('documents', ['id' => $doc->id]);
    }

    public function test_authorized_user_can_restore_trashed_archived_document_and_keep_archived_status(): void
    {
        $doc = $this->createDocument(['status' => 'archived']);
        $doc->delete();

        $response = $this->actingAs($this->adminA)->postJson(route('documents.restore', $doc));

        $response->assertOk();
        $response->assertJsonPath('document.status', 'archived');
        $this->assertNotSoftDeleted('documents', ['id' => $doc->id, 'status' => 'archived']);
    }

    public function test_cannot_restore_a_non_trashed_document(): void
    {
        $doc = $this->createDocument(['status' => 'active']);

        $this->expectException(HttpException::class);
        $this->lifecycleService->restoreFromTrash($this->adminA, $doc);
    }

    public function test_unauthorized_user_cannot_restore_document(): void
    {
        $doc = $this->createDocument(['status' => 'active']);
        $doc->delete();

        $response = $this->actingAs($this->userA)->postJson(route('documents.restore', $doc));

        $response->assertForbidden();
        $this->assertSoftDeleted('documents', ['id' => $doc->id]);
    }

    // ==========================================
    // 4. FORCE DELETE TESTS & PURGE
    // ==========================================

    public function test_authorized_user_can_permanently_delete_a_trashed_document(): void
    {
        $doc = $this->createDocument(['status' => 'active']);
        $filePath = $doc->storage_path;
        $doc->delete();

        $response = $this->actingAs($this->adminA)->deleteJson(route('documents.force-destroy', $doc));

        $response->assertOk();
        $this->assertDatabaseMissing('documents', ['id' => $doc->id]);
        $this->assertFalse(Storage::disk('private')->exists($filePath));
    }

    public function test_cannot_force_delete_an_active_document_without_trashing_first(): void
    {
        $doc = $this->createDocument(['status' => 'active']);

        $this->expectException(HttpException::class);
        $this->lifecycleService->forceDelete($this->adminA, $doc);
    }

    public function test_cannot_force_delete_an_archived_document_without_trashing_first(): void
    {
        $doc = $this->createDocument(['status' => 'archived']);

        $this->expectException(HttpException::class);
        $this->lifecycleService->forceDelete($this->adminA, $doc);
    }

    public function test_force_delete_purges_document_file_and_all_version_files_from_storage(): void
    {
        $doc = $this->createDocument(['status' => 'active']);
        $docPath = $doc->storage_path;

        $v1Path = "organizations/{$this->orgA->id}/documents/v1.pdf";
        $v2Path = "organizations/{$this->orgA->id}/documents/v2.pdf";
        Storage::disk('private')->put($v1Path, 'v1 content');
        Storage::disk('private')->put($v2Path, 'v2 content');

        $v1 = DocumentVersion::factory()->create([
            'document_id' => $doc->id,
            'version_number' => 1,
            'storage_path' => $v1Path,
            'uploaded_by' => $this->adminA->id,
        ]);

        $v2 = DocumentVersion::factory()->create([
            'document_id' => $doc->id,
            'version_number' => 2,
            'storage_path' => $v2Path,
            'uploaded_by' => $this->adminA->id,
        ]);

        $doc->delete(); // Soft delete first

        $this->lifecycleService->forceDelete($this->adminA, $doc);

        $this->assertDatabaseMissing('documents', ['id' => $doc->id]);
        $this->assertDatabaseMissing('document_versions', ['id' => $v1->id]);
        $this->assertDatabaseMissing('document_versions', ['id' => $v2->id]);

        $this->assertFalse(Storage::disk('private')->exists($docPath));
        $this->assertFalse(Storage::disk('private')->exists($v1Path));
        $this->assertFalse(Storage::disk('private')->exists($v2Path));
    }

    public function test_unauthorized_user_cannot_force_delete_document(): void
    {
        $doc = $this->createDocument(['status' => 'active']);
        $doc->delete();

        $response = $this->actingAs($this->userA)->deleteJson(route('documents.force-destroy', $doc));

        $response->assertForbidden();
        $this->assertSoftDeleted('documents', ['id' => $doc->id]);
    }

    // ==========================================
    // 5. EMPTY TRASH TESTS
    // ==========================================

    public function test_authorized_user_can_empty_trash(): void
    {
        $doc1 = $this->createDocument(['status' => 'active']);
        $doc2 = $this->createDocument(['status' => 'archived']);
        $path1 = $doc1->storage_path;
        $path2 = $doc2->storage_path;

        $doc1->delete();
        $doc2->delete();

        $response = $this->actingAs($this->adminA)->postJson(route('documents.trash.empty'));

        $response->assertOk();
        $response->assertJsonPath('deleted_count', 2);

        $this->assertDatabaseMissing('documents', ['id' => $doc1->id]);
        $this->assertDatabaseMissing('documents', ['id' => $doc2->id]);
        $this->assertFalse(Storage::disk('private')->exists($path1));
        $this->assertFalse(Storage::disk('private')->exists($path2));
    }

    public function test_empty_trash_only_deletes_current_tenant_documents(): void
    {
        $docA = $this->createDocument(['organization_id' => $this->orgA->id]);
        $docB = $this->createDocument(['organization_id' => $this->orgB->id]);
        $docA->delete();
        $docB->delete();

        $response = $this->actingAs($this->adminA)->postJson(route('documents.trash.empty'));

        $response->assertOk();
        $this->assertDatabaseMissing('documents', ['id' => $docA->id]);
        // Org B's trashed document remains intact!
        $this->assertSoftDeleted('documents', ['id' => $docB->id]);
    }

    // ==========================================
    // 6. LIST TRASH & ARCHIVED
    // ==========================================

    public function test_get_trash_lists_only_trashed_documents_for_current_tenant(): void
    {
        $trashedA = $this->createDocument(['organization_id' => $this->orgA->id]);
        $trashedA->delete();

        $activeA = $this->createDocument(['organization_id' => $this->orgA->id]);

        $trashedB = $this->createDocument(['organization_id' => $this->orgB->id]);
        $trashedB->delete();

        $response = $this->actingAs($this->adminA)->getJson(route('documents.trash.index'));

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($trashedA->id, $data[0]['id']);
    }

    public function test_get_archived_lists_only_archived_documents_for_current_tenant(): void
    {
        $archivedA = $this->createDocument(['organization_id' => $this->orgA->id, 'status' => 'archived']);
        app(AccessControlService::class)->grantDocumentPermission($archivedA, $this->adminA, 'view');

        $activeA = $this->createDocument(['organization_id' => $this->orgA->id, 'status' => 'active']);
        $trashedArchivedA = $this->createDocument(['organization_id' => $this->orgA->id, 'status' => 'archived']);
        $trashedArchivedA->delete();

        $archivedB = $this->createDocument(['organization_id' => $this->orgB->id, 'status' => 'archived']);

        $response = $this->actingAs($this->adminA)->getJson(route('documents.archived.index'));

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($archivedA->id, $data[0]['id']);
    }

    // ==========================================
    // 7. MULTI-TENANT ISOLATION & ANTI-LEAK
    // ==========================================

    public function test_user_cannot_archive_document_from_another_tenant(): void
    {
        $docB = $this->createDocument(['organization_id' => $this->orgB->id]);

        $response = $this->actingAs($this->adminA)->postJson(route('documents.archive', $docB));

        $response->assertForbidden();
    }

    public function test_user_cannot_unarchive_document_from_another_tenant(): void
    {
        $docB = $this->createDocument(['organization_id' => $this->orgB->id, 'status' => 'archived']);

        $response = $this->actingAs($this->adminA)->postJson(route('documents.unarchive', $docB));

        $response->assertForbidden();
    }

    public function test_user_cannot_trash_document_from_another_tenant(): void
    {
        $docB = $this->createDocument(['organization_id' => $this->orgB->id]);

        $response = $this->actingAs($this->adminA)->deleteJson(route('documents.destroy', $docB));

        $response->assertForbidden();
    }

    public function test_user_cannot_restore_document_from_another_tenant(): void
    {
        $docB = $this->createDocument(['organization_id' => $this->orgB->id]);
        $docB->delete();

        $response = $this->actingAs($this->adminA)->postJson(route('documents.restore', $docB));

        $response->assertForbidden();
    }

    public function test_user_cannot_force_delete_document_from_another_tenant(): void
    {
        $docB = $this->createDocument(['organization_id' => $this->orgB->id]);
        $docB->delete();

        $response = $this->actingAs($this->adminA)->deleteJson(route('documents.force-destroy', $docB));

        $response->assertForbidden();
    }

    // ==========================================
    // 8. SEARCH & PREVIEW INTERACTION WITH LIFECYCLE
    // ==========================================

    public function test_trashed_document_cannot_be_previewed(): void
    {
        $doc = $this->createDocument(['status' => 'active']);
        $doc->delete();

        $response = $this->actingAs($this->adminA)->get(route('documents.preview', $doc));

        // Route model binding without withTrashed will 404
        $response->assertNotFound();
    }

    public function test_archived_document_can_still_be_previewed_by_authorized_user(): void
    {
        $doc = $this->createDocument(['status' => 'archived']);
        app(AccessControlService::class)->grantDocumentPermission($doc, $this->adminA, 'view');

        $response = $this->actingAs($this->adminA)->get(route('documents.preview', $doc));

        $response->assertOk();
    }
}
