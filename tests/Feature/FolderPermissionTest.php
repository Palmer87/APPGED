<?php

namespace Tests\Feature;

use App\Models\Folder;
use App\Models\Group;
use App\Models\Organization;
use App\Models\User;
use App\Services\AccessControlService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class FolderPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $orgA;

    protected Organization $orgB;

    protected User $userA;

    protected User $userB;

    protected Group $groupA;

    protected Group $groupB;

    protected Folder $folderA;

    protected Folder $folderB;

    protected AccessControlService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AccessControlService;

        $this->orgA = Organization::factory()->create();
        $this->orgB = Organization::factory()->create();

        $this->userA = User::factory()->for($this->orgA)->create();
        $this->userB = User::factory()->for($this->orgB)->create();

        $this->groupA = Group::factory()->create(['organization_id' => $this->orgA->id]);
        $this->groupB = Group::factory()->create(['organization_id' => $this->orgB->id]);

        $this->folderA = Folder::factory()->create(['organization_id' => $this->orgA->id]);
        $this->folderB = Folder::factory()->create(['organization_id' => $this->orgB->id]);
    }

    public function test_grant_permission_to_user(): void
    {
        $acl = $this->service->grantFolderPermission($this->folderA, $this->userA, 'view');

        $this->assertEquals($this->folderA->id, $acl->folder_id);
        $this->assertEquals($this->userA->id, $acl->user_id);
        $this->assertNull($acl->group_id);
        $this->assertEquals('view', $acl->permission);
    }

    public function test_grant_permission_to_group(): void
    {
        $acl = $this->service->grantFolderPermissionToGroup($this->folderA, $this->groupA, 'update');

        $this->assertEquals($this->folderA->id, $acl->folder_id);
        $this->assertEquals($this->groupA->id, $acl->group_id);
        $this->assertNull($acl->user_id);
        $this->assertEquals('update', $acl->permission);
    }

    public function test_revoke_user_permission(): void
    {
        $this->service->grantFolderPermission($this->folderA, $this->userA, 'view');
        $this->assertCount(1, $this->folderA->fresh()->permissions);

        $this->service->revokeFolderPermission($this->folderA, $this->userA, 'view');
        $this->assertCount(0, $this->folderA->fresh()->permissions);
    }

    public function test_revoke_group_permission(): void
    {
        $this->service->grantFolderPermissionToGroup($this->folderA, $this->groupA, 'delete');
        $this->assertCount(1, $this->folderA->fresh()->permissions);

        $this->service->revokeFolderPermissionFromGroup($this->folderA, $this->groupA, 'delete');
        $this->assertCount(0, $this->folderA->fresh()->permissions);
    }

    public function test_grant_is_idempotent_no_duplicates(): void
    {
        $acl1 = $this->service->grantFolderPermission($this->folderA, $this->userA, 'view');
        $acl2 = $this->service->grantFolderPermission($this->folderA, $this->userA, 'view');

        $this->assertEquals($acl1->id, $acl2->id);
        $this->assertCount(1, $this->folderA->fresh()->permissions);
    }

    public function test_user_cross_tenant_is_refused(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('User belongs to a different organization');

        $this->service->grantFolderPermission($this->folderA, $this->userB, 'view');
    }

    public function test_group_cross_tenant_is_refused(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Group belongs to a different organization');

        $this->service->grantFolderPermissionToGroup($this->folderA, $this->groupB, 'view');
    }

    public function test_invalid_permission_is_refused(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage("Invalid folder permission 'arbitrary_permission'");

        $this->service->grantFolderPermission($this->folderA, $this->userA, 'arbitrary_permission');
    }

    public function test_can_access_folder_returns_true_with_user_acl(): void
    {
        $this->service->grantFolderPermission($this->folderA, $this->userA, 'view');

        $this->assertTrue($this->service->canAccessFolder($this->userA, $this->folderA, 'view'));
        $this->assertFalse($this->service->canAccessFolder($this->userA, $this->folderA, 'delete'));
    }

    public function test_can_access_folder_returns_true_via_group(): void
    {
        $this->userA->groups()->attach($this->groupA->id);
        $this->service->grantFolderPermissionToGroup($this->folderA, $this->groupA, 'update');

        $this->assertTrue($this->service->canAccessFolder($this->userA, $this->folderA, 'update'));
        $this->assertFalse($this->service->canAccessFolder($this->userA, $this->folderA, 'delete'));
    }

    public function test_absence_of_acl_returns_false(): void
    {
        $this->assertFalse($this->service->canAccessFolder($this->userA, $this->folderA, 'view'));
    }

    public function test_super_admin_has_global_access(): void
    {
        $super = User::factory()->for($this->orgA)->create();
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $role = Role::findOrCreate('super-admin', 'web');
        $super->assignRole($role);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Super admin has access to folder in Org B without any ACL
        $this->assertTrue($this->service->canAccessFolder($super, $this->folderB, 'view'));
        $this->assertTrue($this->service->canAccessFolder($super, $this->folderB, 'delete'));
    }

    public function test_soft_deleted_folder_denies_access(): void
    {
        $this->service->grantFolderPermission($this->folderA, $this->userA, 'view');
        $this->folderA->delete();

        $this->assertFalse($this->service->canAccessFolder($this->userA, $this->folderA, 'view'));
    }

    public function test_restoring_folder_preserves_and_restores_acl_access(): void
    {
        $this->service->grantFolderPermission($this->folderA, $this->userA, 'view');
        $this->folderA->delete();
        $this->assertFalse($this->service->canAccessFolder($this->userA, $this->folderA, 'view'));

        $this->folderA->restore();
        $this->assertTrue($this->service->canAccessFolder($this->userA, $this->folderA, 'view'));
    }

    public function test_check_constraint_rejects_both_user_and_group(): void
    {
        $this->expectException(QueryException::class);

        DB::table('folder_permissions')->insert([
            'folder_id' => $this->folderA->id,
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

        DB::table('folder_permissions')->insert([
            'folder_id' => $this->folderA->id,
            'user_id' => null,
            'group_id' => null,
            'permission' => 'view',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_database_unique_constraint_rejects_duplicate(): void
    {
        $this->service->grantFolderPermission($this->folderA, $this->userA, 'view');

        $this->expectException(QueryException::class);

        DB::table('folder_permissions')->insert([
            'folder_id' => $this->folderA->id,
            'user_id' => $this->userA->id,
            'group_id' => null,
            'permission' => 'view',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
