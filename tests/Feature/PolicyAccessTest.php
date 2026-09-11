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

    /** @test */
    public function user_can_access_own_resource_with_permission(): void
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

    /** @test */
    public function user_cannot_access_other_organization_resource_even_with_permission(): void
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

    /** @test */
    public function admin_cannot_access_other_organization_resource(): void
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

    /** @test */
    public function super_admin_can_access_any_resource(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $super = User::factory()->for($orgA)->create();
        $groupB = Group::factory()->for($orgB)->create();

        $role = Role::findOrCreate('super-admin', 'web');
        $super->assignRole($role);

        $this->assertTrue($super->can('view', $groupB));
    }

    /** @test */
    public function user_without_permission_is_denied(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->for($org)->create();
        $group = Group::factory()->for($org)->create();

        // No role/permission assigned
        $this->assertFalse($user->can('view', $group));
    }
}
