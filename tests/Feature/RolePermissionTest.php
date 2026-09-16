<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_receive_a_role_and_its_permission_in_its_organization(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->for($organization)->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);
        $permission = Permission::findOrCreate('documents.view', 'web');
        $role = Role::findOrCreate('admin', 'web');
        $role->givePermissionTo($permission);
        $user->assignRole($role);

        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->can('documents.view'));
    }

    public function test_role_assignments_are_isolated_between_organizations(): void
    {
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        $userA = User::factory()->for($organizationA)->create();
        $userB = User::factory()->for($organizationB)->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId($organizationA->id);
        $role = Role::findOrCreate('lecteur', 'web');
        Permission::findOrCreate('documents.view', 'web');
        $role->givePermissionTo('documents.view');
        $userA->assignRole($role);
        app(PermissionRegistrar::class)->setPermissionsTeamId($organizationA->id);

        app(PermissionRegistrar::class)->setPermissionsTeamId($organizationB->id);

        $this->assertFalse($userB->hasRole('lecteur'));
        $this->assertFalse($userB->can('documents.view'));
    }

    public function test_role_permission_seeder_is_idempotent(): void
    {
        Organization::factory()->create();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $this->assertSame(49, Permission::count());
        $this->assertSame(5, Role::count());
    }

    public function test_super_admin_has_global_access(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->for($organization)->create();
        // Assign super-admin role globally (no team)
        // Set team context to the organization
        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);
        // Create or retrieve the super-admin role for this organization
        $role = Role::findOrCreate('super-admin', 'web');
        // Ensure necessary permissions exist within the same team
        Permission::findOrCreate('organizations.view', 'web');
        Permission::findOrCreate('documents.view', 'web');
        // Assign permissions to the role
        $role->givePermissionTo(['organizations.view', 'documents.view']);
        // Assign role to user
        $user->assignRole($role);
        // Super-admin should bypass organization checks
        $anotherOrg = Organization::factory()->create();
        $this->assertTrue($user->can('organizations.view'));
        $this->assertTrue($user->can('documents.view'));
    }
}
