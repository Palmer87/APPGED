<?php

namespace Tests\Feature;

use App\Enums\AccessScopeType;
use App\Enums\FolderType;
use App\Models\AccessScope;
use App\Models\Direction;
use App\Models\Folder;
use App\Models\Organization;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminUserAndRoleBackOfficeTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $orgA;

    protected Organization $orgB;

    protected User $adminA;

    protected User $adminB;

    protected User $superAdmin;

    protected Role $roleAdminA;

    protected Role $roleCustomA;

    protected Role $roleCustomB;

    protected Direction $dirA;

    protected Direction $dirB;

    protected Service $servA1;

    protected Service $servA2;

    protected Service $servB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orgA = Organization::factory()->create(['name' => 'Organisation A']);
        $this->orgB = Organization::factory()->create(['name' => 'Organisation B']);

        // Setup permissions
        $permissions = [
            'users.view', 'users.create', 'users.update', 'users.delete',
            'roles.view', 'roles.create', 'roles.update', 'roles.delete',
            'folders.view', 'folders.create', 'folders.update', 'folders.delete',
            'documents.view', 'documents.create', 'documents.update', 'documents.delete', 'documents.download',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        // Roles in Org A
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $this->roleAdminA = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
            'organization_id' => $this->orgA->id,
        ]);
        $this->roleAdminA->syncPermissions($permissions);

        $this->roleCustomA = Role::firstOrCreate([
            'name' => 'gestionnaire-rh',
            'guard_name' => 'web',
            'organization_id' => $this->orgA->id,
        ]);
        $this->roleCustomA->syncPermissions(['documents.view', 'documents.create', 'folders.view']);

        // Roles in Org B
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgB->id);
        $roleAdminB = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
            'organization_id' => $this->orgB->id,
        ]);
        $roleAdminB->syncPermissions($permissions);

        $this->roleCustomB = Role::firstOrCreate([
            'name' => 'comptable-b',
            'guard_name' => 'web',
            'organization_id' => $this->orgB->id,
        ]);

        // Admin Users
        $this->adminA = User::factory()->create([
            'organization_id' => $this->orgA->id,
            'first_name' => 'Alice',
            'last_name' => 'AdminA',
            'email' => 'alice@orga.test',
            'status' => 'active',
        ]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $this->adminA->assignRole($this->roleAdminA);

        $this->adminB = User::factory()->create([
            'organization_id' => $this->orgB->id,
            'first_name' => 'Bob',
            'last_name' => 'AdminB',
            'email' => 'bob@orgb.test',
            'status' => 'active',
        ]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgB->id);
        $this->adminB->assignRole($roleAdminB);

        // Super-admin role
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $superAdminRole = Role::findOrCreate('super-admin', 'web');

        $this->superAdmin = User::factory()->create([
            'organization_id' => $this->orgA->id,
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'superadmin@gedapp.test',
            'status' => 'active',
        ]);
        $this->superAdmin->assignRole($superAdminRole);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);

        // Directions & Services Org A
        $this->dirA = Direction::factory()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'Direction Financière',
            'code' => 'DF',
            'is_active' => true,
        ]);

        $this->servA1 = Service::factory()->create([
            'organization_id' => $this->orgA->id,
            'direction_id' => $this->dirA->id,
            'name' => 'Comptabilité Générale',
            'code' => 'COMPTA',
            'is_active' => true,
        ]);

        $this->servA2 = Service::factory()->create([
            'organization_id' => $this->orgA->id,
            'direction_id' => $this->dirA->id,
            'name' => 'Contrôle de Gestion',
            'code' => 'CDG',
            'is_active' => true,
        ]);

        // Directions & Services Org B
        $this->dirB = Direction::factory()->create([
            'organization_id' => $this->orgB->id,
            'name' => 'Direction RH Org B',
            'code' => 'DRH-B',
            'is_active' => true,
        ]);

        $this->servB = Service::factory()->create([
            'organization_id' => $this->orgB->id,
            'direction_id' => $this->dirB->id,
            'name' => 'Paie B',
            'code' => 'PAIE-B',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_access_admin_user_routes_alias(): void
    {
        $response = $this->actingAs($this->adminA)->get('/admin/users');
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Users/Index')
            ->has('users.data')
            ->has('directions')
            ->has('services')
        );

        $createResponse = $this->actingAs($this->adminA)->get('/admin/users/create');
        $createResponse->assertOk();
        $createResponse->assertInertia(fn (Assert $page) => $page
            ->component('Users/Create')
            ->has('directions')
            ->has('services')
            ->has('roles')
        );
    }

    public function test_admin_can_create_user_with_direction_and_services(): void
    {
        $payload = [
            'first_name' => 'Marc',
            'last_name' => 'Comptable',
            'email' => 'marc.comptable@orga.test',
            'phone' => '+33611223344',
            'job_title' => 'Comptable Fournisseurs',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'direction_id' => $this->dirA->id,
            'primary_service_id' => $this->servA1->id,
            'associated_service_ids' => [$this->servA2->id],
            'role' => $this->roleCustomA->name,
            'status' => 'active',
        ];

        $response = $this->actingAs($this->adminA)->post('/admin/users', $payload);
        $response->assertRedirect('/users');

        $user = User::where('email', 'marc.comptable@orga.test')->first();
        $this->assertNotNull($user);
        $this->assertEquals($this->orgA->id, $user->organization_id);
        $this->assertEquals($this->servA1->id, $user->primary_service_id);

        // Check associated services in pivot
        $this->assertTrue($user->services->contains($this->servA2->id));

        // Check role assigned in Spatie team context
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $this->assertTrue($user->hasRole($this->roleCustomA->name));

        // Check audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.created',
            'auditable_id' => $user->id,
            'auditable_type' => User::class,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.role_assigned',
            'auditable_id' => $user->id,
        ]);
    }

    public function test_user_creation_fails_when_assigning_service_from_another_organization(): void
    {
        $payload = [
            'first_name' => 'Attacker',
            'last_name' => 'Test',
            'email' => 'attacker@orga.test',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'direction_id' => $this->dirA->id,
            'primary_service_id' => $this->servB->id, // Belongs to Org B!
            'role' => $this->roleCustomA->name,
            'status' => 'active',
        ];

        $response = $this->actingAs($this->adminA)->post('/admin/users', $payload);
        $response->assertSessionHasErrors(['primary_service_id']);
        $this->assertDatabaseMissing('users', ['email' => 'attacker@orga.test']);
    }

    public function test_user_creation_fails_when_service_does_not_belong_to_selected_direction(): void
    {
        $otherDirA = Direction::factory()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'Direction Juridique',
            'code' => 'DJ',
            'is_active' => true,
        ]);

        $payload = [
            'first_name' => 'Inconsistent',
            'last_name' => 'User',
            'email' => 'inconsistent@orga.test',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'direction_id' => $otherDirA->id,
            'primary_service_id' => $this->servA1->id, // Belongs to dirA, not otherDirA!
            'role' => $this->roleCustomA->name,
            'status' => 'active',
        ];

        $response = $this->actingAs($this->adminA)->post('/admin/users', $payload);
        $response->assertSessionHasErrors(['primary_service_id']);
    }

    public function test_user_show_provides_effective_rights_breakdown(): void
    {
        $user = User::factory()->create([
            'organization_id' => $this->orgA->id,
            'first_name' => 'Claire',
            'last_name' => 'Testeur',
            'email' => 'claire@orga.test',
            'primary_service_id' => $this->servA1->id,
            'status' => 'active',
        ]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $user->assignRole($this->roleCustomA);

        // Add an access scope
        AccessScope::create([
            'organization_id' => $this->orgA->id,
            'user_id' => $user->id,
            'scope_type' => AccessScopeType::Service,
            'service_id' => $this->servA1->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminA)->get("/admin/users/{$user->id}");
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Users/Show')
            ->where('user.id', $user->id)
            ->has('effectiveRights', fn (Assert $rights) => $rights
                ->where('is_super_admin', false)
                ->where('is_admin', false)
                ->has('roles')
                ->has('grouped_permissions')
                ->has('all_permissions')
                ->has('service_access')
                ->has('scopes')
                ->has('direct_acls')
                ->etc()
            )
        );
    }

    public function test_admin_cannot_view_or_modify_user_from_another_organization(): void
    {
        $userB = User::factory()->create([
            'organization_id' => $this->orgB->id,
            'email' => 'userb@orgb.test',
            'status' => 'active',
        ]);

        // Attempt viewing user from Org B as Admin of Org A
        $response = $this->actingAs($this->adminA)->get("/admin/users/{$userB->id}");
        $response->assertForbidden();

        // Attempt modifying user from Org B
        $updateResponse = $this->actingAs($this->adminA)->put("/admin/users/{$userB->id}", [
            'first_name' => 'Hacked',
            'last_name' => 'Hacked',
            'email' => 'hacked@orgb.test',
            'status' => 'active',
        ]);
        $updateResponse->assertForbidden();
    }

    public function test_toggle_user_status_emits_audit_and_prevents_self_toggle(): void
    {
        $targetUser = User::factory()->create([
            'organization_id' => $this->orgA->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminA)->post("/admin/users/{$targetUser->id}/toggle-status");
        $response->assertRedirect();

        $this->assertEquals('inactive', $targetUser->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.disabled',
            'auditable_id' => $targetUser->id,
        ]);

        // Self-toggle prevention
        $selfResponse = $this->actingAs($this->adminA)->post("/admin/users/{$this->adminA->id}/toggle-status");
        $selfResponse->assertSessionHas('error');
        $this->assertEquals('active', $this->adminA->fresh()->status);
    }

    public function test_roles_admin_routes_and_show(): void
    {
        $response = $this->actingAs($this->adminA)->get('/admin/roles');
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Roles/Index')
            ->has('roles.data')
        );

        $showResponse = $this->actingAs($this->adminA)->get("/admin/roles/{$this->roleCustomA->id}");
        $showResponse->assertOk();
        $showResponse->assertInertia(fn (Assert $page) => $page
            ->component('Roles/Show')
            ->where('role.name', 'gestionnaire-rh')
            ->has('permissionsGrouped')
        );
    }

    public function test_admin_cannot_view_or_modify_role_from_another_organization(): void
    {
        $response = $this->actingAs($this->adminA)->get("/admin/roles/{$this->roleCustomB->id}");
        $response->assertForbidden();

        $updateResponse = $this->actingAs($this->adminA)->put("/admin/roles/{$this->roleCustomB->id}", [
            'name' => 'role-hacked',
            'permissions' => ['documents.view'],
        ]);
        $updateResponse->assertForbidden();
    }

    public function test_super_admin_has_global_access(): void
    {
        $responseUser = $this->actingAs($this->superAdmin)->get("/admin/users/{$this->adminB->id}");
        $responseUser->assertOk();

        $responseRole = $this->actingAs($this->superAdmin)->get("/admin/roles/{$this->roleCustomB->id}");
        $responseRole->assertOk();
    }

    public function test_document_types_can_be_filtered_by_direction_and_service(): void
    {
        $docTypeFolder = Folder::factory()->create([
            'organization_id' => $this->orgA->id,
            'direction_id' => $this->dirA->id,
            'service_id' => $this->servA1->id,
            'folder_type' => FolderType::DocumentType,
            'name' => 'Facture Fournisseur',
        ]);

        $response = $this->actingAs($this->adminA)->get('/admin/document-types?direction_id='.$this->dirA->id);
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('DocumentTypes/Index')
            ->has('documentTypes')
            ->has('directions')
            ->has('services')
        );
    }
}
