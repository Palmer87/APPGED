<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Group;
use App\Models\Organization;
use App\Models\User;
use App\Services\AccessControlService;
use App\Services\DocumentService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class DocumentPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $orgA;

    protected Organization $orgB;

    protected User $userA;

    protected User $userB;

    protected Group $groupA;

    protected Group $groupB;

    protected Document $documentA;

    protected Document $documentB;

    protected DocumentService $docService;

    protected AccessControlService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');

        $this->docService = new DocumentService;
        $this->service = new AccessControlService;

        $this->orgA = Organization::factory()->create();
        $this->orgB = Organization::factory()->create();

        $this->userA = User::factory()->for($this->orgA)->create();
        $this->userB = User::factory()->for($this->orgB)->create();

        $this->groupA = Group::factory()->create(['organization_id' => $this->orgA->id]);
        $this->groupB = Group::factory()->create(['organization_id' => $this->orgB->id]);

        $fileA = UploadedFile::fake()->create('docA.pdf', 100, 'application/pdf');
        $this->documentA = $this->docService->upload([
            'organization_id' => $this->orgA->id,
            'uploaded_by' => $this->userA->id,
            'storage_disk' => 'private',
        ], $fileA);

        $fileB = UploadedFile::fake()->create('docB.pdf', 100, 'application/pdf');
        $this->documentB = $this->docService->upload([
            'organization_id' => $this->orgB->id,
            'uploaded_by' => $this->userB->id,
            'storage_disk' => 'private',
        ], $fileB);
    }

    public function test_grant_user_view(): void
    {
        $acl = $this->service->grantDocumentPermission($this->documentA, $this->userA, 'view');

        $this->assertEquals($this->documentA->id, $acl->document_id);
        $this->assertEquals($this->userA->id, $acl->user_id);
        $this->assertNull($acl->group_id);
        $this->assertEquals('view', $acl->permission);
    }

    public function test_grant_user_download(): void
    {
        $acl = $this->service->grantDocumentPermission($this->documentA, $this->userA, 'download');

        $this->assertEquals('download', $acl->permission);
        $this->assertEquals($this->userA->id, $acl->user_id);
    }

    public function test_grant_user_update(): void
    {
        $acl = $this->service->grantDocumentPermission($this->documentA, $this->userA, 'update');

        $this->assertEquals('update', $acl->permission);
        $this->assertEquals($this->userA->id, $acl->user_id);
    }

    public function test_grant_group_view(): void
    {
        $acl = $this->service->grantDocumentPermissionToGroup($this->documentA, $this->groupA, 'view');

        $this->assertEquals($this->documentA->id, $acl->document_id);
        $this->assertEquals($this->groupA->id, $acl->group_id);
        $this->assertNull($acl->user_id);
        $this->assertEquals('view', $acl->permission);
    }

    public function test_grant_group_download(): void
    {
        $acl = $this->service->grantDocumentPermissionToGroup($this->documentA, $this->groupA, 'download');

        $this->assertEquals('download', $acl->permission);
        $this->assertEquals($this->groupA->id, $acl->group_id);
    }

    public function test_revoke_permission(): void
    {
        $this->service->grantDocumentPermission($this->documentA, $this->userA, 'view');
        $this->assertCount(1, $this->documentA->fresh()->permissions);

        $this->service->revokeDocumentPermission($this->documentA, $this->userA, 'view');
        $this->assertCount(0, $this->documentA->fresh()->permissions);
    }

    public function test_grant_is_idempotent_no_duplicate_records(): void
    {
        $acl1 = $this->service->grantDocumentPermission($this->documentA, $this->userA, 'view');
        $acl2 = $this->service->grantDocumentPermission($this->documentA, $this->userA, 'view');

        $this->assertEquals($acl1->id, $acl2->id);
        $this->assertCount(1, $this->documentA->fresh()->permissions);
    }

    public function test_cross_tenant_user_is_refused(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('User belongs to a different organization');

        $this->service->grantDocumentPermission($this->documentA, $this->userB, 'view');
    }

    public function test_cross_tenant_group_is_refused(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Group belongs to a different organization');

        $this->service->grantDocumentPermissionToGroup($this->documentA, $this->groupB, 'view');
    }

    public function test_invalid_permission_is_refused(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage("Invalid document permission 'unsupported_perm'");

        $this->service->grantDocumentPermission($this->documentA, $this->userA, 'unsupported_perm');
    }

    public function test_can_access_document_user(): void
    {
        $this->service->grantDocumentPermission($this->documentA, $this->userA, 'view');

        $this->assertTrue($this->service->canAccessDocument($this->userA, $this->documentA, 'view'));
        $this->assertFalse($this->service->canAccessDocument($this->userA, $this->documentA, 'delete'));
    }

    public function test_can_access_document_group(): void
    {
        $this->userA->groups()->attach($this->groupA->id);
        $this->service->grantDocumentPermissionToGroup($this->documentA, $this->groupA, 'download');

        $this->assertTrue($this->service->canAccessDocument($this->userA, $this->documentA, 'download'));
        $this->assertFalse($this->service->canAccessDocument($this->userA, $this->documentA, 'delete'));
    }

    public function test_absence_of_acl_returns_false(): void
    {
        $this->assertFalse($this->service->canAccessDocument($this->userA, $this->documentA, 'view'));
    }

    public function test_super_admin_has_global_access(): void
    {
        $super = User::factory()->for($this->orgA)->create();
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $role = Role::findOrCreate('super-admin', 'web');
        $super->assignRole($role);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Super admin has access to Document B (Org B) without any ACL
        $this->assertTrue($this->service->canAccessDocument($super, $this->documentB, 'view'));
        $this->assertTrue($this->service->canAccessDocument($super, $this->documentB, 'download'));
    }

    public function test_document_soft_deleted_denies_access(): void
    {
        $this->service->grantDocumentPermission($this->documentA, $this->userA, 'view');
        $this->documentA->delete();

        $this->assertFalse($this->service->canAccessDocument($this->userA, $this->documentA, 'view'));
    }

    public function test_restore_document_preserves_and_restores_acl(): void
    {
        $this->service->grantDocumentPermission($this->documentA, $this->userA, 'view');
        $this->documentA->delete();
        $this->assertFalse($this->service->canAccessDocument($this->userA, $this->documentA, 'view'));

        $this->documentA->restore();
        $this->assertTrue($this->service->canAccessDocument($this->userA, $this->documentA, 'view'));
    }

    public function test_check_constraint_rejects_both_user_and_group(): void
    {
        $this->expectException(QueryException::class);

        DB::table('document_permissions')->insert([
            'document_id' => $this->documentA->id,
            'user_id' => $this->userA->id,
            'group_id' => $this->groupA->id,
            'permission' => 'view',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_check_constraint_rejects_neither_user_nor_group(): void
    {
        $this->expectException(QueryException::class);

        DB::table('document_permissions')->insert([
            'document_id' => $this->documentA->id,
            'user_id' => null,
            'group_id' => null,
            'permission' => 'view',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_database_unique_constraint_rejects_duplicate(): void
    {
        $this->service->grantDocumentPermission($this->documentA, $this->userA, 'view');

        $this->expectException(QueryException::class);

        DB::table('document_permissions')->insert([
            'document_id' => $this->documentA->id,
            'user_id' => $this->userA->id,
            'group_id' => null,
            'permission' => 'view',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
