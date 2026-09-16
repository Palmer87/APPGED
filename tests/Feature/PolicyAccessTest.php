<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PolicyAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_own_resource_with_permission(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->for($org)->create();
        $group = Group::factory()->for($org)->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        Permission::findOrCreate('groups.view', 'web');
        $role = Role::findOrCreate('utilisateur', 'web');
        $role->givePermissionTo('groups.view');
        $user->assignRole($role);

        $this->assertTrue($user->can('view', $group));
    }

    public function test_user_cannot_access_other_organization_resource_even_with_permission(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $userA = User::factory()->for($orgA)->create();
        $groupB = Group::factory()->for($orgB)->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId($orgA->id);
        Permission::findOrCreate('groups.view', 'web');
        $role = Role::findOrCreate('utilisateur', 'web');
        $role->givePermissionTo('groups.view');
        $userA->assignRole($role);

        $this->assertFalse($userA->can('view', $groupB));
    }

    public function test_admin_cannot_access_other_organization_resource(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $admin = User::factory()->for($orgA)->create();
        $groupB = Group::factory()->for($orgB)->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId($orgA->id);
        $role = Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('groups.view', 'web');
        $role->givePermissionTo('groups.view');
        $admin->assignRole($role);

        $this->assertFalse($admin->can('view', $groupB));
    }

    public function test_super_admin_can_access_any_resource(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $super = User::factory()->for($orgA)->create();
        $groupB = Group::factory()->for($orgB)->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId($orgA->id);
        $role = Role::findOrCreate('super-admin', 'web');
        $super->assignRole($role);

        // Refresh user model and clear Spatie permission cache
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $super = $super->fresh();

        $this->assertTrue($super->can('view', $groupB));
    }

    public function test_user_without_permission_is_denied(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->for($org)->create();
        $group = Group::factory()->for($org)->create();

        // No role/permission assigned
        $this->assertFalse($user->can('view', $group));
    }

    /*
    |--------------------------------------------------------------------------
    | Organization Policy & Tenant Isolation Tests
    |--------------------------------------------------------------------------
    */

    public function test_user_can_view_own_organization_with_permission(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->for($org)->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        Permission::findOrCreate('organizations.view', 'web');
        $role = Role::findOrCreate('utilisateur', 'web');
        $role->givePermissionTo('organizations.view');
        $user->assignRole($role);

        $this->assertTrue($user->can('view', $org));
    }

    public function test_user_cannot_view_other_organization_even_with_permission(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $userA = User::factory()->for($orgA)->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId($orgA->id);
        Permission::findOrCreate('organizations.view', 'web');
        $role = Role::findOrCreate('utilisateur', 'web');
        $role->givePermissionTo('organizations.view');
        $userA->assignRole($role);

        $this->assertFalse($userA->can('view', $orgB));
        $this->assertFalse($userA->can('update', $orgB));
        $this->assertFalse($userA->can('delete', $orgB));
    }

    public function test_user_without_organization_permission_is_denied(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->for($org)->create();

        $this->assertFalse($user->can('view', $org));
        $this->assertFalse($user->can('update', $org));
        $this->assertFalse($user->can('delete', $org));
    }

    public function test_super_admin_can_access_any_organization(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $super = User::factory()->for($orgA)->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId($orgA->id);
        $role = Role::findOrCreate('super-admin', 'web');
        $super->assignRole($role);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $super = $super->fresh();

        $this->assertTrue($super->can('view', $orgB));
        $this->assertTrue($super->can('update', $orgB));
        $this->assertTrue($super->can('delete', $orgB));
    }

    /*
    |--------------------------------------------------------------------------
    | User Policy & Tenant Isolation Tests
    |--------------------------------------------------------------------------
    */

    public function test_user_can_view_member_of_same_organization_with_permission(): void
    {
        $org = Organization::factory()->create();
        $user1 = User::factory()->for($org)->create();
        $user2 = User::factory()->for($org)->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        Permission::findOrCreate('users.view', 'web');
        $role = Role::findOrCreate('utilisateur', 'web');
        $role->givePermissionTo('users.view');
        $user1->assignRole($role);

        $this->assertTrue($user1->can('view', $user2));
    }

    public function test_user_cannot_view_or_modify_user_of_other_organization(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $userA = User::factory()->for($orgA)->create();
        $userB = User::factory()->for($orgB)->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId($orgA->id);
        Permission::findOrCreate('users.view', 'web');
        Permission::findOrCreate('users.update', 'web');
        Permission::findOrCreate('users.delete', 'web');
        $role = Role::findOrCreate('admin', 'web');
        $role->givePermissionTo(['users.view', 'users.update', 'users.delete']);
        $userA->assignRole($role);

        $this->assertFalse($userA->can('view', $userB));
        $this->assertFalse($userA->can('update', $userB));
        $this->assertFalse($userA->can('delete', $userB));
    }

    public function test_user_without_user_permission_is_denied_same_organization(): void
    {
        $org = Organization::factory()->create();
        $user1 = User::factory()->for($org)->create();
        $user2 = User::factory()->for($org)->create();

        $this->assertFalse($user1->can('view', $user2));
        $this->assertFalse($user1->can('update', $user2));
        $this->assertFalse($user1->can('delete', $user2));
    }

    public function test_super_admin_can_access_any_user(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $super = User::factory()->for($orgA)->create();
        $userB = User::factory()->for($orgB)->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId($orgA->id);
        $role = Role::findOrCreate('super-admin', 'web');
        $super->assignRole($role);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $super = $super->fresh();

        $this->assertTrue($super->can('view', $userB));
        $this->assertTrue($super->can('update', $userB));
        $this->assertTrue($super->can('delete', $userB));
    }

    /*
    |--------------------------------------------------------------------------
    | Group Policy & Tenant Isolation Tests
    |--------------------------------------------------------------------------
    */

    public function test_user_cannot_update_or_delete_group_in_other_organization(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $userA = User::factory()->for($orgA)->create();
        $groupB = Group::factory()->for($orgB)->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId($orgA->id);
        Permission::findOrCreate('groups.update', 'web');
        Permission::findOrCreate('groups.delete', 'web');
        $role = Role::findOrCreate('admin', 'web');
        $role->givePermissionTo(['groups.update', 'groups.delete']);
        $userA->assignRole($role);

        $this->assertFalse($userA->can('update', $groupB));
        $this->assertFalse($userA->can('delete', $groupB));
    }

    public function test_user_can_update_own_organization_group_with_permission(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->for($org)->create();
        $group = Group::factory()->for($org)->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        Permission::findOrCreate('groups.update', 'web');
        $role = Role::findOrCreate('admin', 'web');
        $role->givePermissionTo('groups.update');
        $user->assignRole($role);

        $this->assertTrue($user->can('update', $group));
    }
}
