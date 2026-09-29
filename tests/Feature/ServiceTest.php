<?php

namespace Tests\Feature;

use App\Models\Direction;
use App\Models\Folder;
use App\Models\Organization;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;

    protected User $admin;

    protected Direction $direction;

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

        $this->direction = Direction::factory()->create([
            'organization_id' => $this->org->id,
            'name' => 'Direction RH',
        ]);
    }

    public function test_can_list_services(): void
    {
        Service::factory()->count(2)->create([
            'organization_id' => $this->org->id,
            'direction_id' => $this->direction->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/services');
        $response->assertOk();
    }

    public function test_can_create_service_under_direction(): void
    {
        $response = $this->actingAs($this->admin)->post('/services', [
            'direction_id' => $this->direction->id,
            'name' => 'Recrutement',
            'code' => 'REC',
            'description' => 'Service de recrutement RH',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('services', [
            'organization_id' => $this->org->id,
            'direction_id' => $this->direction->id,
            'name' => 'Recrutement',
            'code' => 'REC',
        ]);

        // Asserts backing folder was created
        $this->assertDatabaseHas('folders', [
            'organization_id' => $this->org->id,
            'name' => 'Recrutement',
            'folder_type' => 'service',
        ]);
    }

    public function test_can_update_service(): void
    {
        $service = Service::factory()->create([
            'organization_id' => $this->org->id,
            'direction_id' => $this->direction->id,
            'name' => 'Ancien Service',
        ]);

        $response = $this->actingAs($this->admin)->put("/services/{$service->id}", [
            'direction_id' => $this->direction->id,
            'name' => 'Paie & Déclarations',
            'code' => 'PAIE',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'name' => 'Paie & Déclarations',
            'code' => 'PAIE',
        ]);
    }

    public function test_can_delete_empty_service(): void
    {
        $service = Service::factory()->create([
            'organization_id' => $this->org->id,
            'direction_id' => $this->direction->id,
            'name' => 'Service Inutile',
        ]);

        $response = $this->actingAs($this->admin)->delete("/services/{$service->id}");
        $response->assertRedirect();

        $this->assertSoftDeleted('services', ['id' => $service->id]);
    }
}
