<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Folder;
use App\Models\Group;
use App\Models\Organization;
use App\Models\User;
use App\Services\AccessControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PermissionInheritanceTest extends TestCase
{
    use RefreshDatabase;

    protected AccessControlService $aclService;

    protected Organization $org;

    protected User $user;

    protected Folder $folder;

    protected Document $document;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aclService = app(AccessControlService::class);

        $this->org = Organization::factory()->create();
        $this->user = User::factory()->create(['organization_id' => $this->org->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->org->id);

        $role = Role::create(['name' => 'member', 'guard_name' => 'web']);
        $perms = [
            'folders.view', 'folders.create', 'folders.update', 'folders.delete',
            'documents.view', 'documents.create', 'documents.update', 'documents.delete', 'documents.download',
        ];
        foreach ($perms as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $role->givePermissionTo($perms);
        $this->user->assignRole($role);

        $this->folder = Folder::factory()->create(['organization_id' => $this->org->id]);
        $this->document = Document::factory()->create([
            'organization_id' => $this->org->id,
            'folder_id' => $this->folder->id,
            'uploaded_by' => $this->user->id,
        ]);
    }

    public function test_document_inherits_user_view_permission_from_parent_folder(): void
    {
        $this->aclService->grantFolderPermission($this->folder, $this->user, 'view');

        $this->assertTrue($this->aclService->canAccessDocument($this->user, $this->document, 'view'));
    }

    public function test_document_inherits_download_when_user_has_view_on_parent_folder(): void
    {
        $this->aclService->grantFolderPermission($this->folder, $this->user, 'view');

        $this->assertTrue($this->aclService->canAccessDocument($this->user, $this->document, 'download'));
    }

    public function test_document_inherits_user_update_permission_from_parent_folder(): void
    {
        $this->aclService->grantFolderPermission($this->folder, $this->user, 'update');

        $this->assertTrue($this->aclService->canAccessDocument($this->user, $this->document, 'update'));
    }

    public function test_document_inherits_user_delete_permission_from_parent_folder(): void
    {
        $this->aclService->grantFolderPermission($this->folder, $this->user, 'delete');

        $this->assertTrue($this->aclService->canAccessDocument($this->user, $this->document, 'delete'));
    }

    public function test_document_inherits_group_permission_from_parent_folder(): void
    {
        $group = Group::factory()->create(['organization_id' => $this->org->id]);
        $this->user->groups()->attach($group->id);

        $this->aclService->grantFolderPermissionToGroup($this->folder, $group, 'view');

        $this->assertTrue($this->aclService->canAccessDocument($this->user, $this->document, 'view'));
        $this->assertTrue($this->aclService->canAccessDocument($this->user, $this->document, 'download'));
    }

    public function test_revoking_folder_permission_immediately_denies_document_access(): void
    {
        $this->aclService->grantFolderPermission($this->folder, $this->user, 'view');
        $this->assertTrue($this->aclService->canAccessDocument($this->user, $this->document, 'view'));

        $this->aclService->revokeFolderPermission($this->folder, $this->user, 'view');
        $this->assertFalse($this->aclService->canAccessDocument($this->user, $this->document, 'view'));
    }

    public function test_direct_document_permission_grants_access_even_if_parent_folder_has_no_permission(): void
    {
        // Folder has no permission for user, but document does
        $this->aclService->grantDocumentPermission($this->document, $this->user, 'view');

        $this->assertFalse($this->aclService->canAccessFolder($this->user, $this->folder, 'view'));
        $this->assertTrue($this->aclService->canAccessDocument($this->user, $this->document, 'view'));
    }

    public function test_direct_document_permission_overrides_parent_folder_permission_scope(): void
    {
        // Folder has view only
        $this->aclService->grantFolderPermission($this->folder, $this->user, 'view');
        // Document specifically grants update
        $this->aclService->grantDocumentPermission($this->document, $this->user, 'update');

        $this->assertTrue($this->aclService->canAccessDocument($this->user, $this->document, 'view'));
        $this->assertTrue($this->aclService->canAccessDocument($this->user, $this->document, 'update'));
    }

    public function test_document_does_not_inherit_folder_permission_if_user_in_different_organization(): void
    {
        $otherOrg = Organization::factory()->create();
        $otherUser = User::factory()->create(['organization_id' => $otherOrg->id]);

        $this->aclService->grantFolderPermission($this->folder, $this->user, 'view');

        $this->assertFalse($this->aclService->canAccessDocument($otherUser, $this->document, 'view'));
    }

    public function test_document_does_not_inherit_folder_permission_if_parent_folder_is_trashed(): void
    {
        $this->aclService->grantFolderPermission($this->folder, $this->user, 'view');
        $this->folder->delete(); // Soft delete

        $this->assertFalse($this->aclService->canAccessDocument($this->user, $this->document, 'view'));
    }

    public function test_policy_authorizes_document_view_via_folder_inheritance(): void
    {
        $this->aclService->grantFolderPermission($this->folder, $this->user, 'view');

        $this->assertTrue(Gate::forUser($this->user)->allows('view', $this->document));
    }

    public function test_policy_authorizes_document_download_via_folder_inheritance(): void
    {
        $this->aclService->grantFolderPermission($this->folder, $this->user, 'view');

        $this->assertTrue(Gate::forUser($this->user)->allows('download', $this->document));
    }

    public function test_policy_authorizes_document_update_via_folder_inheritance(): void
    {
        $this->aclService->grantFolderPermission($this->folder, $this->user, 'update');

        $this->assertTrue(Gate::forUser($this->user)->allows('update', $this->document));
    }

    public function test_policy_authorizes_document_delete_via_folder_inheritance(): void
    {
        $this->aclService->grantFolderPermission($this->folder, $this->user, 'delete');

        $this->assertTrue(Gate::forUser($this->user)->allows('delete', $this->document));
    }

    public function test_super_admin_bypasses_all_inheritance_and_acl_checks(): void
    {
        $superAdminRole = Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdmin = User::factory()->create(['organization_id' => $this->org->id]);
        $superAdmin->assignRole($superAdminRole);

        // No ACL granted on folder or document
        $this->assertTrue($this->aclService->canAccessFolder($superAdmin, $this->folder, 'view'));
        $this->assertTrue($this->aclService->canAccessDocument($superAdmin, $this->document, 'view'));
        $this->assertTrue($this->aclService->canAccessDocument($superAdmin, $this->document, 'delete'));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('view', $this->document));
    }
}
