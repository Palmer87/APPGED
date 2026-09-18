<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;

    protected Organization $otherOrg;

    protected User $adminUser;

    protected User $regularUser;

    protected Role $adminRole;

    protected Role $regularRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::factory()->create(['name' => 'Acme Corp']);
        $this->otherOrg = Organization::factory()->create(['name' => 'Other Corp']);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->org->id);

        $permissions = [
            'users.view', 'users.create', 'users.update', 'users.delete',
            'roles.view', 'roles.create', 'roles.update', 'roles.delete',
            'documents.view', 'documents.create',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        $teamForeignKey = config('permission.column_names.team_foreign_key', 'organization_id');

        $this->adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
            $teamForeignKey => $this->org->id,
        ]);
        $this->adminRole->syncPermissions($permissions);

        $this->regularRole = Role::firstOrCreate([
            'name' => 'utilisateur',
            'guard_name' => 'web',
            $teamForeignKey => $this->org->id,
        ]);

        $this->adminUser = User::factory()->create([
            'organization_id' => $this->org->id,
            'first_name' => 'Admin',
            'last_name' => 'Boss',
            'email' => 'admin.boss@acme.test',
        ]);
        $this->adminUser->assignRole($this->adminRole);

        $this->regularUser = User::factory()->create([
            'organization_id' => $this->org->id,
            'first_name' => 'Regular',
            'last_name' => 'Guy',
            'email' => 'regular.guy@acme.test',
        ]);
        $this->regularUser->assignRole($this->regularRole);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/roles');
        $response->assertRedirect('/login');
    }

    public function test_unauthorized_user_cannot_access_roles_index(): void
    {
        $response = $this->actingAs($this->regularUser)->get('/roles');
        $response->assertForbidden();
    }

    public function test_admin_can_view_roles_index(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/roles');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Roles/Index')
            ->has('roles.data')
            ->has('filters')
            ->has('can')
        );
    }

    public function test_admin_can_view_create_role_form(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/roles/create');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Roles/Create')
            ->has('permissionsGrouped')
        );
    }

    public function test_admin_can_create_custom_role_with_permissions(): void
    {
        $payload = [
            'name' => 'auditeur',
            'permissions' => ['documents.view', 'users.view'],
        ];

        $response = $this->actingAs($this->adminUser)->post('/roles', $payload);

        $response->assertRedirect('/roles');
        $teamForeignKey = config('permission.column_names.team_foreign_key', 'organization_id');
        $this->assertDatabaseHas('roles', [
            'name' => 'auditeur',
            'guard_name' => 'web',
            $teamForeignKey => $this->org->id,
        ]);

        $createdRole = Role::where($teamForeignKey, $this->org->id)->where('name', 'auditeur')->first();
        $this->assertTrue($createdRole->hasPermissionTo('documents.view'));
        $this->assertTrue($createdRole->hasPermissionTo('users.view'));
        $this->assertFalse($createdRole->hasPermissionTo('documents.create'));
    }

    public function test_admin_can_view_edit_role_form(): void
    {
        $response = $this->actingAs($this->adminUser)->get("/roles/{$this->regularRole->id}/edit");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Roles/Edit')
            ->where('role.id', $this->regularRole->id)
            ->where('isSystemRole', false)
            ->has('permissionsGrouped')
        );
    }

    public function test_admin_can_update_role_permissions(): void
    {
        $payload = [
            'name' => 'utilisateur',
            'permissions' => ['documents.view'],
        ];

        $response = $this->actingAs($this->adminUser)->put("/roles/{$this->regularRole->id}", $payload);

        $response->assertRedirect('/roles');
        $this->regularRole->refresh();
        $this->assertTrue($this->regularRole->hasPermissionTo('documents.view'));
    }

    public function test_system_role_name_cannot_be_renamed(): void
    {
        $payload = [
            'name' => 'super-hacker',
            'permissions' => ['documents.view'],
        ];

        $response = $this->actingAs($this->adminUser)->put("/roles/{$this->adminRole->id}", $payload);

        $response->assertRedirect('/roles');
        $this->adminRole->refresh();
        $this->assertEquals('admin', $this->adminRole->name);
    }

    public function test_system_role_cannot_be_deleted(): void
    {
        $response = $this->actingAs($this->adminUser)->delete("/roles/{$this->adminRole->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('roles', ['id' => $this->adminRole->id]);
    }

    public function test_role_with_assigned_users_cannot_be_deleted(): void
    {
        $response = $this->actingAs($this->adminUser)->delete("/roles/{$this->regularRole->id}");

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('roles', ['id' => $this->regularRole->id]);
    }

    public function test_unassigned_custom_role_can_be_deleted(): void
    {
        $teamForeignKey = config('permission.column_names.team_foreign_key', 'organization_id');
        $customRole = Role::create([
            'name' => 'temporary-role',
            'guard_name' => 'web',
            $teamForeignKey => $this->org->id,
        ]);

        $response = $this->actingAs($this->adminUser)->delete("/roles/{$customRole->id}");

        $response->assertRedirect('/roles');
        $this->assertDatabaseMissing('roles', ['id' => $customRole->id]);
    }

    public function test_multi_tenant_isolation_prevents_editing_other_organization_role(): void
    {
        $teamForeignKey = config('permission.column_names.team_foreign_key', 'organization_id');
        $otherRole = Role::create([
            'name' => 'external-role',
            'guard_name' => 'web',
            $teamForeignKey => $this->otherOrg->id,
        ]);

        $response = $this->actingAs($this->adminUser)->get("/roles/{$otherRole->id}/edit");

        $response->assertForbidden();
    }
}
