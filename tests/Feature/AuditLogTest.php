<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Folder;
use App\Models\Group;
use App\Models\MetadataDefinition;
use App\Models\Organization;
use App\Models\Tag;
use App\Models\User;
use App\Services\AccessControlService;
use App\Services\AuditLogService;
use App\Services\AuditService;
use App\Services\DocumentLifecycleService;
use App\Services\DocumentMetadataService;
use App\Services\DocumentService;
use App\Services\DocumentShareService;
use App\Services\FolderService;
use App\Services\PreviewService;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected AuditService $auditService;

    protected AuditLogService $auditLogService;

    protected DocumentService $documentService;

    protected DocumentLifecycleService $lifecycleService;

    protected DocumentShareService $shareService;

    protected AccessControlService $aclService;

    protected DocumentMetadataService $metadataService;

    protected FolderService $folderService;

    protected PreviewService $previewService;

    protected Organization $orgA;

    protected Organization $orgB;

    protected User $adminA;

    protected User $managerA;

    protected User $userA;

    protected User $userB;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');
        config(['filesystems.default' => 'private']);

        $this->orgA = Organization::factory()->create();
        $this->orgB = Organization::factory()->create();

        $this->seed(RolePermissionSeeder::class);

        $this->auditService = app(AuditService::class);
        $this->auditLogService = app(AuditLogService::class);
        $this->documentService = app(DocumentService::class);
        $this->lifecycleService = app(DocumentLifecycleService::class);
        $this->shareService = app(DocumentShareService::class);
        $this->aclService = app(AccessControlService::class);
        $this->metadataService = app(DocumentMetadataService::class);
        $this->folderService = app(FolderService::class);
        $this->previewService = app(PreviewService::class);

        $this->adminA = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->managerA = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->userA = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->userB = User::factory()->create(['organization_id' => $this->orgB->id]);

        $permissions = [
            'documents.view', 'documents.create', 'documents.update',
            'documents.delete', 'documents.download', 'documents.share',
            'documents.archive', 'documents.restore', 'audit.view',
        ];
        foreach ($permissions as $p) {
            Permission::findOrCreate($p, 'web');
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $roleAdminA = Role::create(['name' => 'admin_a', 'guard_name' => 'web', 'organization_id' => $this->orgA->id]);
        $roleAdminA->givePermissionTo($permissions);
        $this->adminA->assignRole($roleAdminA);

        $roleManagerA = Role::create(['name' => 'manager_a', 'guard_name' => 'web', 'organization_id' => $this->orgA->id]);
        $roleManagerA->givePermissionTo(['documents.view', 'documents.download', 'audit.view']);
        $this->managerA->assignRole($roleManagerA);

        $roleUserA = Role::create(['name' => 'user_a', 'guard_name' => 'web', 'organization_id' => $this->orgA->id]);
        $roleUserA->givePermissionTo(['documents.view', 'documents.download']);
        $this->userA->assignRole($roleUserA);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgB->id);
        $roleAdminB = Role::create(['name' => 'admin_b', 'guard_name' => 'web', 'organization_id' => $this->orgB->id]);
        $roleAdminB->givePermissionTo($permissions);
        $this->userB->assignRole($roleAdminB);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $superAdminRole = Role::findOrCreate('super-admin', 'web');
        $this->superAdmin = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->superAdmin->assignRole($superAdminRole);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
    }

    protected function createSampleDocument(array $attributes = []): Document
    {
        $orgId = $attributes['organization_id'] ?? $this->orgA->id;
        $uploaderId = $attributes['uploaded_by'] ?? $this->adminA->id;
        $path = $attributes['storage_path'] ?? "organizations/{$orgId}/documents/doc_".uniqid().'.pdf';

        Storage::disk('private')->put($path, '%PDF-1.4 sample content');

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
    // 1. AUDIT CREATION & DEFAULTS
    // ==========================================

    public function test_audit_log_records_correct_action_organization_and_user(): void
    {
        $doc = $this->createSampleDocument();

        $log = $this->auditService->success(
            action: 'document.custom_action',
            auditable: $doc,
            user: $this->adminA,
            description: 'Custom action executed'
        );

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'organization_id' => $this->orgA->id,
            'user_id' => $this->adminA->id,
            'action' => 'document.custom_action',
            'result' => 'success',
            'auditable_type' => $doc->getMorphClass(),
            'auditable_id' => $doc->id,
        ]);
        $this->assertNotNull($log->created_at);
    }

    public function test_audit_log_derives_organization_from_auditable_when_not_provided(): void
    {
        $doc = $this->createSampleDocument(['organization_id' => $this->orgB->id]);

        $log = $this->auditService->log(
            action: 'document.test',
            result: 'success',
            auditable: $doc,
            user: $this->superAdmin // super-admin belongs to orgA, but doc is orgB
        );

        $this->assertEquals($this->orgB->id, $log->organization_id);
    }

    public function test_audit_log_derives_organization_from_user_when_auditable_is_null(): void
    {
        $log = $this->auditService->success(
            action: 'auth.login',
            user: $this->adminA
        );

        $this->assertEquals($this->orgA->id, $log->organization_id);
    }

    public function test_audit_log_organization_id_can_be_null_for_global_actions(): void
    {
        $log = $this->auditService->log(
            action: 'system.maintenance',
            result: 'success',
            organizationId: null,
            user: null
        );

        $this->assertNull($log->organization_id);
        $this->assertNull($log->user_id);
    }

    public function test_failed_operation_records_failure_result_with_reason(): void
    {
        $doc = $this->createSampleDocument();

        $log = $this->auditService->failure(
            action: 'document.archived',
            reason: 'unauthorized',
            auditable: $doc,
            user: $this->userA
        );

        $this->assertEquals('failure', $log->result);
        $this->assertEquals('unauthorized', $log->metadata['reason']);
    }

    // ==========================================
    // 2. HTTP CONTEXT VS CLI
    // ==========================================

    public function test_cli_execution_stores_null_ip_and_user_agent_without_error(): void
    {
        $log = $this->auditService->success(
            action: 'cron.cleanup',
            user: null
        );

        $this->assertNull($log->ip_address);
        $this->assertNull($log->user_agent);
    }

    public function test_http_request_captures_ip_and_user_agent(): void
    {
        $response = $this->actingAs($this->adminA)
            ->withServerVariables([
                'REMOTE_ADDR' => '192.168.1.100',
                'HTTP_USER_AGENT' => 'Mozilla/5.0 GED-Test-Client',
            ])
            ->getJson(route('audit.logs.index'));

        $response->assertOk();

        // Check if any HTTP audit service execution grabs the request context
        $doc = $this->createSampleDocument();
        $this->actingAs($this->adminA)
            ->withServerVariables([
                'REMOTE_ADDR' => '10.0.0.42',
                'HTTP_USER_AGENT' => 'GED-Agent-1.0',
            ])
            ->postJson(route('documents.archive', $doc));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.archived',
            'auditable_id' => $doc->id,
            'ip_address' => '10.0.0.42',
            'user_agent' => 'GED-Agent-1.0',
        ]);
    }

    // ==========================================
    // 3. SENSITIVE DATA REDACTION
    // ==========================================

    public function test_redaction_masks_passwords_and_tokens_recursively(): void
    {
        $dirtyPayload = [
            'name' => 'Valid Name',
            'password' => 'super_secret_123',
            'password_confirmation' => 'super_secret_123',
            'nested' => [
                'token' => 'bearer-abc-123',
                'access_token' => 'tok_xyz',
                'api_key' => 'key_secret',
                'public_id' => 999,
            ],
        ];

        $clean = $this->auditService->redact($dirtyPayload);

        $this->assertEquals('Valid Name', $clean['name']);
        $this->assertEquals('[REDACTED]', $clean['password']);
        $this->assertEquals('[REDACTED]', $clean['password_confirmation']);
        $this->assertEquals('[REDACTED]', $clean['nested']['token']);
        $this->assertEquals('[REDACTED]', $clean['nested']['access_token']);
        $this->assertEquals('[REDACTED]', $clean['nested']['api_key']);
        $this->assertEquals(999, $clean['nested']['public_id']);
    }

    public function test_audit_log_stores_redacted_values_in_database(): void
    {
        $log = $this->auditService->success(
            action: 'user.credential_update',
            oldValues: ['password' => 'old_secret_pwd'],
            newValues: ['password' => 'new_secret_pwd', 'token' => 'xyz'],
            metadata: ['auth_secret' => 'top_secret']
        );

        $this->assertEquals('[REDACTED]', $log->old_values['password']);
        $this->assertEquals('[REDACTED]', $log->new_values['password']);
        $this->assertEquals('[REDACTED]', $log->new_values['token']);
        $this->assertEquals('[REDACTED]', $log->metadata['auth_secret']);
    }

    // ==========================================
    // 4. DOCUMENT OPERATIONS AUDIT
    // ==========================================

    public function test_document_creation_is_audited(): void
    {
        $file = UploadedFile::fake()->create('contract.pdf', 500, 'application/pdf');

        $doc = $this->documentService->upload([
            'organization_id' => $this->orgA->id,
            'uploaded_by' => $this->adminA->id,
            'name' => 'Contract 2026',
        ], $file);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.created',
            'auditable_type' => $doc->getMorphClass(),
            'auditable_id' => $doc->id,
            'organization_id' => $this->orgA->id,
            'result' => 'success',
        ]);
    }

    public function test_document_version_upload_is_audited(): void
    {
        $doc = $this->createSampleDocument();
        $file = UploadedFile::fake()->create('v2.pdf', 600, 'application/pdf');

        $this->actingAs($this->adminA);
        $version = $this->documentService->uploadNewVersion($doc, $file, 'Version 2 notes');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.version_created',
            'auditable_id' => $doc->id,
            'target_id' => $version->id,
            'result' => 'success',
        ]);
    }

    public function test_document_version_restore_is_audited(): void
    {
        $doc = $this->createSampleDocument();
        $v1Path = "organizations/{$this->orgA->id}/documents/v1.pdf";
        Storage::disk('private')->put($v1Path, 'v1 content');

        $v1 = DocumentVersion::factory()->create([
            'document_id' => $doc->id,
            'version_number' => 1,
            'storage_path' => $v1Path,
            'uploaded_by' => $this->adminA->id,
        ]);

        $this->actingAs($this->adminA);
        $restoredVersion = $this->documentService->restoreVersion($doc, $v1);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.version_restored',
            'auditable_id' => $doc->id,
            'target_id' => $restoredVersion->id,
        ]);
    }

    public function test_document_category_update_is_audited(): void
    {
        $doc = $this->createSampleDocument();
        $cat = Category::factory()->create(['organization_id' => $this->orgA->id, 'name' => 'Legal']);

        $this->documentService->setCategory($doc, $cat);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.category_updated',
            'auditable_id' => $doc->id,
            'target_id' => $cat->id,
        ]);
    }

    public function test_document_tags_update_is_audited(): void
    {
        $doc = $this->createSampleDocument();
        $tag1 = Tag::factory()->create(['organization_id' => $this->orgA->id, 'name' => 'Urgent']);
        $tag2 = Tag::factory()->create(['organization_id' => $this->orgA->id, 'name' => 'Confidential']);

        $this->documentService->syncTags($doc, [$tag1->id, $tag2->id]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.tags_updated',
            'auditable_id' => $doc->id,
        ]);
    }

    // ==========================================
    // 5. LIFECYCLE AUDITS
    // ==========================================

    public function test_document_archive_and_unarchive_are_audited(): void
    {
        $doc = $this->createSampleDocument(['status' => 'active']);

        $this->lifecycleService->archive($this->adminA, $doc);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.archived',
            'auditable_id' => $doc->id,
        ]);

        $this->lifecycleService->unarchive($this->adminA, $doc);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.unarchived',
            'auditable_id' => $doc->id,
        ]);
    }

    public function test_document_trash_restore_and_force_delete_are_audited(): void
    {
        $doc = $this->createSampleDocument(['status' => 'active']);

        $this->lifecycleService->moveToTrash($this->adminA, $doc);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.deleted',
            'auditable_id' => $doc->id,
        ]);

        $this->lifecycleService->restoreFromTrash($this->adminA, $doc);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.restored',
            'auditable_id' => $doc->id,
        ]);

        $this->lifecycleService->moveToTrash($this->adminA, $doc);
        $this->lifecycleService->forceDelete($this->adminA, $doc);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.force_deleted',
            'auditable_id' => $doc->id,
        ]);
    }

    // ==========================================
    // 6. SHARING & PERMISSIONS AUDITS
    // ==========================================

    public function test_document_share_with_user_is_audited(): void
    {
        $doc = $this->createSampleDocument();

        $share = $this->shareService->shareWithUser($this->adminA, $doc, $this->userA, 'download');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.shared',
            'auditable_id' => $doc->id,
            'target_id' => $this->userA->id,
        ]);
    }

    public function test_document_share_with_group_is_audited(): void
    {
        $doc = $this->createSampleDocument();
        $group = Group::factory()->create(['organization_id' => $this->orgA->id]);

        $share = $this->shareService->shareWithGroup($this->adminA, $doc, $group, 'view');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.shared',
            'auditable_id' => $doc->id,
            'target_id' => $group->id,
        ]);
    }

    public function test_document_share_revoke_is_audited(): void
    {
        $doc = $this->createSampleDocument();
        $share = $this->shareService->shareWithUser($this->adminA, $doc, $this->userA, 'download');

        $this->shareService->revoke($this->adminA, $share);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.share_revoked',
            'auditable_id' => $doc->id,
        ]);
    }

    public function test_document_permission_grant_and_revoke_are_audited(): void
    {
        $doc = $this->createSampleDocument();

        $this->aclService->grantDocumentPermission($doc, $this->userA, 'download');
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.permission_granted',
            'auditable_id' => $doc->id,
            'target_id' => $this->userA->id,
        ]);

        $this->aclService->revokeDocumentPermission($doc, $this->userA, 'download');
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.permission_revoked',
            'auditable_id' => $doc->id,
            'target_id' => $this->userA->id,
        ]);
    }

    public function test_folder_permission_grant_and_revoke_are_audited(): void
    {
        $folder = Folder::factory()->create(['organization_id' => $this->orgA->id]);

        $this->aclService->grantFolderPermission($folder, $this->userA, 'view');
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'folder.permission_granted',
            'auditable_id' => $folder->id,
            'target_id' => $this->userA->id,
        ]);

        $this->aclService->revokeFolderPermission($folder, $this->userA, 'view');
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'folder.permission_revoked',
            'auditable_id' => $folder->id,
            'target_id' => $this->userA->id,
        ]);
    }

    // ==========================================
    // 7. FOLDER & METADATA AUDITS
    // ==========================================

    public function test_folder_creation_move_delete_and_restore_are_audited(): void
    {
        $folder = $this->folderService->create(['name' => 'Finance'], $this->adminA);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'folder.created',
            'auditable_id' => $folder->id,
        ]);

        $parent = Folder::factory()->create(['organization_id' => $this->orgA->id]);
        $this->folderService->move($folder, $parent);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'folder.updated',
            'auditable_id' => $folder->id,
        ]);

        $this->folderService->delete($folder);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'folder.deleted',
            'auditable_id' => $folder->id,
        ]);

        $this->folderService->restore($folder);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'folder.restored',
            'auditable_id' => $folder->id,
        ]);
    }

    public function test_document_metadata_set_value_is_audited(): void
    {
        $doc = $this->createSampleDocument();
        $def = MetadataDefinition::factory()->create([
            'organization_id' => $this->orgA->id,
            'key' => 'invoice_number',
            'type' => 'string',
            'is_active' => true,
        ]);

        $this->metadataService->setValue($doc, $def, 'INV-2026-001');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.metadata_updated',
            'auditable_id' => $doc->id,
            'target_id' => $def->id,
        ]);
    }

    public function test_document_preview_is_audited(): void
    {
        $doc = $this->createSampleDocument();

        $this->actingAs($this->adminA);
        $this->previewService->preview($doc, null, $this->adminA);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.previewed',
            'auditable_id' => $doc->id,
            'user_id' => $this->adminA->id,
        ]);
    }

    // ==========================================
    // 8. SECURITY & MULTI-TENANT ISOLATION
    // ==========================================

    public function test_user_without_audit_view_permission_cannot_access_audit_logs(): void
    {
        $response = $this->actingAs($this->userA)->getJson(route('audit.logs.index'));

        $response->assertForbidden();
    }

    public function test_authorized_user_can_access_audit_logs_for_own_organization(): void
    {
        $this->auditService->success('action.a', user: $this->adminA);

        $response = $this->actingAs($this->managerA)->getJson(route('audit.logs.index'));

        $response->assertOk();
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    public function test_tenant_a_cannot_view_audit_logs_of_tenant_b(): void
    {
        $this->auditService->success('action.a', user: $this->adminA);
        $this->auditService->success('action.b', user: $this->userB);

        $response = $this->actingAs($this->adminA)->getJson(route('audit.logs.index'));

        $response->assertOk();
        $data = $response->json('data');
        foreach ($data as $log) {
            $this->assertEquals($this->orgA->id, $log['organization_id']);
        }
    }

    public function test_regular_user_cannot_override_organization_filter(): void
    {
        $this->auditService->success('action.b', user: $this->userB);

        // Attempt to pass organization_id of Org B
        $response = $this->actingAs($this->adminA)->getJson(route('audit.logs.index', [
            'organization_id' => $this->orgB->id,
        ]));

        $response->assertOk();
        $data = $response->json('data');
        foreach ($data as $log) {
            $this->assertEquals($this->orgA->id, $log['organization_id']);
        }
    }

    public function test_super_admin_can_view_all_audit_logs_and_filter_by_organization(): void
    {
        $this->auditService->success('action.a', user: $this->adminA);
        $this->auditService->success('action.b', user: $this->userB);

        // Super-admin sees all
        $responseAll = $this->actingAs($this->superAdmin)->getJson(route('audit.logs.index'));
        $responseAll->assertOk();
        $this->assertGreaterThanOrEqual(2, count($responseAll->json('data')));

        // Super-admin filters specifically for Org B
        $responseOrgB = $this->actingAs($this->superAdmin)->getJson(route('audit.logs.index', [
            'organization_id' => $this->orgB->id,
        ]));
        $responseOrgB->assertOk();
        $dataB = $responseOrgB->json('data');
        foreach ($dataB as $log) {
            $this->assertEquals($this->orgB->id, $log['organization_id']);
        }
    }

    // ==========================================
    // 9. DOCUMENT HISTORY ENDPOINT
    // ==========================================

    public function test_document_history_returns_chronological_descending_order(): void
    {
        $doc = $this->createSampleDocument();

        $log1 = $this->auditService->success('step.1', auditable: $doc);
        $log1->updateQuietly(['created_at' => Carbon::now()->subMinutes(10)]);

        $log2 = $this->auditService->success('step.2', auditable: $doc);
        $log2->updateQuietly(['created_at' => Carbon::now()->subMinutes(5)]);

        $log3 = $this->auditService->success('step.3', auditable: $doc);
        $log3->updateQuietly(['created_at' => Carbon::now()]);

        $response = $this->actingAs($this->adminA)->getJson(route('documents.history', $doc));

        $response->assertOk();
        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(3, count($data));
        $this->assertEquals('step.3', $data[0]['action']);
        $this->assertEquals('step.2', $data[1]['action']);
        $this->assertEquals('step.1', $data[2]['action']);
    }

    public function test_document_history_does_not_leak_records_from_other_documents(): void
    {
        $doc1 = $this->createSampleDocument(['name' => 'Doc 1']);
        $doc2 = $this->createSampleDocument(['name' => 'Doc 2']);

        $this->auditService->success('doc1.action', auditable: $doc1);
        $this->auditService->success('doc2.action', auditable: $doc2);

        $response = $this->actingAs($this->adminA)->getJson(route('documents.history', $doc1));

        $response->assertOk();
        $data = $response->json('data');
        foreach ($data as $log) {
            $this->assertEquals($doc1->id, $log['auditable_id']);
        }
    }

    public function test_document_history_cannot_be_viewed_by_user_without_document_view_access(): void
    {
        $docB = $this->createSampleDocument(['organization_id' => $this->orgB->id]);

        $response = $this->actingAs($this->adminA)->getJson(route('documents.history', $docB));

        $response->assertForbidden();
    }

    // ==========================================
    // 10. FILTERING & PAGINATION
    // ==========================================

    public function test_audit_logs_can_be_filtered_by_action_and_result(): void
    {
        $this->auditService->success('doc.created', user: $this->adminA);
        $this->auditService->failure('doc.archived', reason: 'fail', user: $this->adminA);

        $response = $this->actingAs($this->adminA)->getJson(route('audit.logs.index', [
            'action' => 'doc.created',
            'result' => 'success',
        ]));

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('doc.created', $data[0]['action']);
    }

    public function test_audit_logs_can_be_filtered_by_user(): void
    {
        $this->auditService->success('admin.action', user: $this->adminA);
        $this->auditService->success('manager.action', user: $this->managerA);

        $response = $this->actingAs($this->adminA)->getJson(route('audit.logs.index', [
            'user_id' => $this->managerA->id,
        ]));

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($this->managerA->id, $data[0]['user_id']);
    }

    public function test_audit_logs_pagination_respects_max_limit(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->auditService->success("bulk.{$i}", user: $this->adminA);
        }

        $response = $this->actingAs($this->adminA)->getJson(route('audit.logs.index', [
            'per_page' => 5,
        ]));

        $response->assertOk();
        $this->assertEquals(5, $response->json('per_page'));
        $this->assertCount(5, $response->json('data'));

        // per_page exceeds max (100)
        $responseMax = $this->actingAs($this->adminA)->getJson(route('audit.logs.index', [
            'per_page' => 500,
        ]));
        $responseMax->assertOk();
        $this->assertEquals(100, $responseMax->json('per_page'));
    }

    // ==========================================
    // 11. IMMUTABILITY & ANTI-TAMPERING
    // ==========================================

    public function test_audit_log_cannot_be_updated_directly_via_eloquent(): void
    {
        $log = $this->auditService->success('initial.action', user: $this->adminA);

        $result = $log->update(['action' => 'tampered.action']);

        $this->assertFalse($result);
        $this->assertEquals('initial.action', $log->fresh()->action);
    }

    public function test_audit_log_cannot_be_deleted_directly_via_eloquent(): void
    {
        $log = $this->auditService->success('initial.action', user: $this->adminA);

        $result = $log->delete();

        $this->assertFalse($result);
        $this->assertDatabaseHas('audit_logs', ['id' => $log->id]);
    }
}
