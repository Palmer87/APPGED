<?php

namespace Tests\Feature;

use App\Models\Direction;
use App\Models\Folder;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DirectionTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::factory()->create();

        $teamForeignKey = config('permission.column_names.team_foreign_key', 'organization_id');
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->org->id);

        $permissions = ['folders.view', 'folders.create', 'folders.update', 'folders.delete'];
        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'sanctum']);
        }

        $role = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
            $teamForeignKey => $this->org->id,
        ]);
        $role->givePermissionTo($permissions);

        $this->admin = User::factory()->create([
            'organization_id' => $this->org->id,
        ]);
        $this->admin->assignRole('admin');
    }

    public function test_can_list_directions(): void
    {
        Direction::factory()->count(3)->create(['organization_id' => $this->org->id]);

        $response = $this->actingAs($this->admin)->get('/directions');
        $response->assertOk();
    }

    public function test_can_create_direction_via_web(): void
    {
        $response = $this->actingAs($this->admin)->post('/directions', [
            'name' => 'Direction Administrative & Financière',
            'code' => 'DAF',
            'description' => 'Gestion financière',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('directions', [
            'organization_id' => $this->org->id,
            'name' => 'Direction Administrative & Financière',
            'code' => 'DAF',
        ]);

        // Asserts backing folder was created
        $this->assertDatabaseHas('folders', [
            'organization_id' => $this->org->id,
            'name' => 'Direction Administrative & Financière',
            'folder_type' => 'department',
        ]);
    }

    public function test_can_update_direction(): void
    {
        $direction = Direction::factory()->create([
            'organization_id' => $this->org->id,
            'name' => 'Ancien Nom',
        ]);

        $response = $this->actingAs($this->admin)->put("/directions/{$direction->id}", [
            'name' => 'Nouveau Nom Direction',
            'code' => 'NND',
            'description' => 'Description modifiée',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('directions', [
            'id' => $direction->id,
            'name' => 'Nouveau Nom Direction',
            'code' => 'NND',
        ]);
    }

    public function test_can_delete_empty_direction(): void
    {
        $direction = Direction::factory()->create([
            'organization_id' => $this->org->id,
            'name' => 'Direction Temporaire',
        ]);

        $response = $this->actingAs($this->admin)->delete("/directions/{$direction->id}");
        $response->assertRedirect();

        $this->assertSoftDeleted('directions', ['id' => $direction->id]);
    }
}
