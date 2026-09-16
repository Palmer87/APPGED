<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Organization;
use App\Models\User;
use App\Services\CategoryService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $orgA;

    protected Organization $orgB;

    protected User $userA;

    protected User $userB;

    protected CategoryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new CategoryService;

        $this->orgA = Organization::factory()->create();
        $this->orgB = Organization::factory()->create();

        $this->userA = User::factory()->for($this->orgA)->create();
        $this->userB = User::factory()->for($this->orgB)->create();

        Permission::findOrCreate('categories.view', 'web');
        Permission::findOrCreate('categories.create', 'web');
        Permission::findOrCreate('categories.update', 'web');
        Permission::findOrCreate('categories.delete', 'web');

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $roleA = Role::findOrCreate('admin', 'web');
        $roleA->givePermissionTo(['categories.view', 'categories.create', 'categories.update', 'categories.delete']);
        $this->userA->assignRole($roleA);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgB->id);
        $roleB = Role::findOrCreate('admin', 'web');
        $roleB->givePermissionTo(['categories.view', 'categories.create', 'categories.update', 'categories.delete']);
        $this->userB->assignRole($roleB);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->userA = $this->userA->fresh();
        $this->userB = $this->userB->fresh();
    }

    public function test_create_category(): void
    {
        $this->actingAs($this->userA);

        $category = $this->service->create([
            'name' => 'Factures',
            'description' => 'Factures 2026',
        ]);

        $this->assertEquals('Factures', $category->name);
        $this->assertEquals($this->orgA->id, $category->organization_id);
    }

    public function test_category_associated_with_correct_organization(): void
    {
        $this->actingAs($this->userA);

        $category = $this->service->create([
            'name' => 'Contrats',
        ]);

        $this->assertEquals($this->orgA->id, $category->organization_id);

        $this->actingAs($this->userB);

        $categoryB = $this->service->create([
            'name' => 'Contrats B',
        ]);

        $this->assertEquals($this->orgB->id, $categoryB->organization_id);
    }

    public function test_same_name_allowed_in_different_organizations(): void
    {
        $this->actingAs($this->userA);
        $this->service->create(['name' => 'Important']);

        $this->actingAs($this->userB);
        $categoryB = $this->service->create(['name' => 'Important']);

        $this->assertEquals('Important', $categoryB->name);
        $this->assertEquals($this->orgB->id, $categoryB->organization_id);
    }

    public function test_duplicate_name_denied_in_same_organization(): void
    {
        $this->actingAs($this->userA);
        $this->service->create(['name' => 'Important']);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('A category with this name already exists in your organization.');

        $this->service->create(['name' => 'Important']);
    }

    public function test_user_without_permission_cannot_create(): void
    {
        $userNoPerm = User::factory()->for($this->orgA)->create();
        $this->actingAs($userNoPerm);

        $this->expectException(AuthorizationException::class);

        $this->service->create(['name' => 'Test']);
    }

    public function test_cross_tenant_update_denied(): void
    {
        $this->actingAs($this->userA);
        $categoryA = $this->service->create(['name' => 'Test A']);

        $this->actingAs($this->userB);
        $this->expectException(AuthorizationException::class);

        $this->service->update($categoryA, ['name' => 'Hacked']);
    }

    public function test_cross_tenant_delete_denied(): void
    {
        $this->actingAs($this->userA);
        $categoryA = $this->service->create(['name' => 'Test A']);

        $this->actingAs($this->userB);
        $this->expectException(AuthorizationException::class);

        $this->service->delete($categoryA);
    }

    public function test_delete_category(): void
    {
        $this->actingAs($this->userA);
        $category = $this->service->create(['name' => 'Test']);

        $this->service->delete($category);

        $this->assertSoftDeleted($category);
    }

    public function test_restore_category(): void
    {
        $this->actingAs($this->userA);
        $category = $this->service->create(['name' => 'Test']);
        $this->service->delete($category);

        $this->service->restore($category);

        $this->assertNotSoftDeleted($category);
    }

    public function test_super_admin_has_global_access(): void
    {
        $super = User::factory()->for($this->orgA)->create();
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $role = Role::findOrCreate('super-admin', 'web');
        $super->assignRole($role);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $super = $super->fresh();

        $this->actingAs($super);

        $this->actingAs($this->userB);
        $categoryB = $this->service->create(['name' => 'Test B']);

        $this->actingAs($super);
        // Super admin can update category in org B even if they belong to org A
        $updated = $this->service->update($categoryB, ['name' => 'Updated by Super']);

        $this->assertEquals('Updated by Super', $updated->name);
    }

    public function test_database_unique_constraint(): void
    {
        $this->actingAs($this->userA);
        $category = Category::factory()->forOrganization($this->orgA)->create(['name' => 'UniqueName']);

        $this->expectException(QueryException::class);

        // Bypassing service validation to test DB constraint
        Category::factory()->forOrganization($this->orgA)->create(['name' => 'UniqueName']);
    }
}
