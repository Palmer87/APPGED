<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Tag;
use App\Models\User;
use App\Services\TagService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $orgA;

    protected Organization $orgB;

    protected User $userA;

    protected User $userB;

    protected TagService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new TagService;

        $this->orgA = Organization::factory()->create();
        $this->orgB = Organization::factory()->create();

        $this->userA = User::factory()->for($this->orgA)->create();
        $this->userB = User::factory()->for($this->orgB)->create();

        Permission::findOrCreate('tags.view', 'web');
        Permission::findOrCreate('tags.create', 'web');
        Permission::findOrCreate('tags.update', 'web');
        Permission::findOrCreate('tags.delete', 'web');

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $roleA = Role::findOrCreate('admin', 'web');
        $roleA->givePermissionTo(['tags.view', 'tags.create', 'tags.update', 'tags.delete']);
        $this->userA->assignRole($roleA);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgB->id);
        $roleB = Role::findOrCreate('admin', 'web');
        $roleB->givePermissionTo(['tags.view', 'tags.create', 'tags.update', 'tags.delete']);
        $this->userB->assignRole($roleB);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->userA = $this->userA->fresh();
        $this->userB = $this->userB->fresh();
    }

    public function test_create_tag(): void
    {
        $this->actingAs($this->userA);

        $tag = $this->service->create([
            'name' => 'Important',
            'description' => 'Urgent',
        ]);

        $this->assertEquals('Important', $tag->name);
        $this->assertEquals($this->orgA->id, $tag->organization_id);
    }

    public function test_tag_associated_with_correct_organization(): void
    {
        $this->actingAs($this->userA);

        $tag = $this->service->create([
            'name' => 'Contrats',
        ]);

        $this->assertEquals($this->orgA->id, $tag->organization_id);

        $this->actingAs($this->userB);

        $tagB = $this->service->create([
            'name' => 'Contrats B',
        ]);

        $this->assertEquals($this->orgB->id, $tagB->organization_id);
    }

    public function test_same_name_allowed_in_different_organizations(): void
    {
        $this->actingAs($this->userA);
        $this->service->create(['name' => 'Important']);

        $this->actingAs($this->userB);
        $tagB = $this->service->create(['name' => 'Important']);

        $this->assertEquals('Important', $tagB->name);
        $this->assertEquals($this->orgB->id, $tagB->organization_id);
    }

    public function test_duplicate_name_denied_in_same_organization(): void
    {
        $this->actingAs($this->userA);
        $this->service->create(['name' => 'Important']);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('A tag with this name already exists in your organization.');

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
        $tagA = $this->service->create(['name' => 'Test A']);

        $this->actingAs($this->userB);
        $this->expectException(AuthorizationException::class);

        $this->service->update($tagA, ['name' => 'Hacked']);
    }

    public function test_cross_tenant_delete_denied(): void
    {
        $this->actingAs($this->userA);
        $tagA = $this->service->create(['name' => 'Test A']);

        $this->actingAs($this->userB);
        $this->expectException(AuthorizationException::class);

        $this->service->delete($tagA);
    }

    public function test_delete_tag(): void
    {
        $this->actingAs($this->userA);
        $tag = $this->service->create(['name' => 'Test']);

        $this->service->delete($tag);

        $this->assertSoftDeleted($tag);
    }

    public function test_restore_tag(): void
    {
        $this->actingAs($this->userA);
        $tag = $this->service->create(['name' => 'Test']);
        $this->service->delete($tag);

        $this->service->restore($tag);

        $this->assertNotSoftDeleted($tag);
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
        $tagB = $this->service->create(['name' => 'Test B']);

        $this->actingAs($super);
        // Super admin can update tag in org B even if they belong to org A
        $updated = $this->service->update($tagB, ['name' => 'Updated by Super']);

        $this->assertEquals('Updated by Super', $updated->name);
    }

    public function test_database_unique_constraint(): void
    {
        $this->actingAs($this->userA);
        $tag = Tag::factory()->forOrganization($this->orgA)->create(['name' => 'UniqueName']);

        $this->expectException(QueryException::class);

        // Bypassing service validation to test DB constraint
        Tag::factory()->forOrganization($this->orgA)->create(['name' => 'UniqueName']);
    }
}
