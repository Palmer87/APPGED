<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserManagementTest extends TestCase
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

        // Set team ID for Acme Corp
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->org->id);

        $permissions = [
            'users.view', 'users.create', 'users.update', 'users.delete',
            'roles.view', 'roles.create', 'roles.update', 'roles.delete',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        $this->adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
            'team_id' => $this->org->id,
        ]);
        $this->adminRole->givePermissionTo($permissions);

        $this->regularRole = Role::firstOrCreate([
            'name' => 'utilisateur',
            'guard_name' => 'web',
            'team_id' => $this->org->id,
        ]);

        $this->adminUser = User::factory()->create([
            'organization_id' => $this->org->id,
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@acme.test',
            'status' => 'active',
        ]);
        $this->adminUser->assignRole($this->adminRole);

        $this->regularUser = User::factory()->create([
            'organization_id' => $this->org->id,
            'first_name' => 'Normal',
            'last_name' => 'Employee',
            'email' => 'employee@acme.test',
            'status' => 'active',
        ]);
        $this->regularUser->assignRole($this->regularRole);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/users');
        $response->assertRedirect('/login');
    }

    public function test_unauthorized_user_cannot_access_users_index(): void
    {
        $response = $this->actingAs($this->regularUser)->get('/users');
        $response->assertForbidden();
    }

    public function test_admin_can_view_users_index(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/users');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Users/Index')
            ->has('users.data')
            ->has('roles')
            ->has('groups')
            ->has('filters')
            ->has('can')
        );
    }

    public function test_admin_can_search_and_filter_users(): void
    {
        $userSearchTarget = User::factory()->create([
            'organization_id' => $this->org->id,
            'first_name' => 'UniqueTarget',
            'last_name' => 'SpecialPerson',
            'email' => 'uniquetarget@acme.test',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)->get('/users?search=UniqueTarget');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Users/Index')
            ->has('users.data', 1)
            ->where('users.data.0.email', 'uniquetarget@acme.test')
        );
    }

    public function test_admin_can_view_create_user_form(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/users/create');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Users/Create')
            ->has('roles')
            ->has('groups')
        );
    }

    public function test_admin_can_store_new_user(): void
    {
        $group = Group::factory()->create(['organization_id' => $this->org->id]);

        $payload = [
            'first_name' => 'Paul',
            'last_name' => 'Durand',
            'email' => 'paul.durand@acme.test',
            'phone' => '0601020304',
            'job_title' => 'Juriste',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'utilisateur',
            'status' => 'active',
            'group_ids' => [$group->id],
        ];

        $response = $this->actingAs($this->adminUser)->post('/users', $payload);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', [
            'email' => 'paul.durand@acme.test',
            'first_name' => 'Paul',
            'last_name' => 'Durand',
            'job_title' => 'Juriste',
            'organization_id' => $this->org->id,
            'status' => 'active',
        ]);

        $created = User::where('email', 'paul.durand@acme.test')->first();
        $this->assertTrue(Hash::check('Password123!', $created->password));
        $this->assertTrue($created->hasRole('utilisateur'));
        $this->assertTrue($created->groups->contains($group->id));
    }

    public function test_validation_fails_on_duplicate_email(): void
    {
        $payload = [
            'first_name' => 'Duplicate',
            'last_name' => 'Email',
            'email' => $this->adminUser->email,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'utilisateur',
            'status' => 'active',
        ];

        $response = $this->actingAs($this->adminUser)->post('/users', $payload);

        $response->assertSessionHasErrors('email');
    }

    public function test_admin_can_view_user_details(): void
    {
        $response = $this->actingAs($this->adminUser)->get("/users/{$this->regularUser->id}");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Users/Show')
            ->where('user.id', $this->regularUser->id)
            ->has('auditLogs')
            ->has('can')
        );
    }

    public function test_admin_can_update_user(): void
    {
        $payload = [
            'first_name' => 'UpdatedFirst',
            'last_name' => 'UpdatedLast',
            'email' => 'employee.updated@acme.test',
            'job_title' => 'Senior Developer',
            'role' => 'admin',
            'status' => 'active',
        ];

        $response = $this->actingAs($this->adminUser)->put("/users/{$this->regularUser->id}", $payload);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', [
            'id' => $this->regularUser->id,
            'first_name' => 'UpdatedFirst',
            'last_name' => 'UpdatedLast',
            'email' => 'employee.updated@acme.test',
            'job_title' => 'Senior Developer',
        ]);

        $this->regularUser->refresh();
        $this->assertTrue($this->regularUser->hasRole('admin'));
    }

    public function test_admin_can_toggle_user_status(): void
    {
        $this->assertEquals('active', $this->regularUser->status);

        $response = $this->actingAs($this->adminUser)->post("/users/{$this->regularUser->id}/toggle-status");

        $response->assertRedirect();
        $this->regularUser->refresh();
        $this->assertEquals('inactive', $this->regularUser->status);
    }

    public function test_user_cannot_toggle_own_status(): void
    {
        $response = $this->actingAs($this->adminUser)->post("/users/{$this->adminUser->id}/toggle-status");

        $response->assertSessionHas('error');
        $this->adminUser->refresh();
        $this->assertEquals('active', $this->adminUser->status);
    }

    public function test_user_cannot_delete_own_account(): void
    {
        $response = $this->actingAs($this->adminUser)->delete("/users/{$this->adminUser->id}");

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->adminUser->id]);
    }

    public function test_admin_can_delete_other_user(): void
    {
        $userToDelete = User::factory()->create([
            'organization_id' => $this->org->id,
            'first_name' => 'Temp',
            'last_name' => 'User',
            'email' => 'temp@acme.test',
        ]);

        $response = $this->actingAs($this->adminUser)->delete("/users/{$userToDelete->id}");

        $response->assertRedirect('/users');
        $this->assertDatabaseMissing('users', ['id' => $userToDelete->id]);
    }

    public function test_multi_tenant_isolation_prevents_viewing_other_organization_users(): void
    {
        $otherUser = User::factory()->create([
            'organization_id' => $this->otherOrg->id,
            'first_name' => 'External',
            'last_name' => 'User',
            'email' => 'ext@other.test',
        ]);

        $response = $this->actingAs($this->adminUser)->get("/users/{$otherUser->id}");

        $response->assertForbidden();
    }
}
