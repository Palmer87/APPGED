<?php

namespace Tests\Feature;

use App\Models\MetadataDefinition;
use App\Models\Organization;
use App\Models\User;
use App\Services\MetadataDefinitionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class MetadataDefinitionTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $orgA;

    protected Organization $orgB;

    protected User $userA;

    protected User $userB;

    protected MetadataDefinitionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new MetadataDefinitionService;

        $this->orgA = Organization::factory()->create();
        $this->orgB = Organization::factory()->create();

        $this->userA = User::factory()->for($this->orgA)->create();
        $this->userB = User::factory()->for($this->orgB)->create();

        Permission::findOrCreate('metadata.view', 'web');
        Permission::findOrCreate('metadata.create', 'web');
        Permission::findOrCreate('metadata.update', 'web');
        Permission::findOrCreate('metadata.delete', 'web');

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $roleA = Role::findOrCreate('admin', 'web');
        $roleA->givePermissionTo(['metadata.view', 'metadata.create', 'metadata.update', 'metadata.delete']);
        $this->userA->assignRole($roleA);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgB->id);
        $roleB = Role::findOrCreate('admin', 'web');
        $roleB->givePermissionTo(['metadata.view', 'metadata.create', 'metadata.update', 'metadata.delete']);
        $this->userB->assignRole($roleB);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->userA = $this->userA->fresh();
        $this->userB = $this->userB->fresh();
    }

    public function test_create_metadata_definition(): void
    {
        $this->actingAs($this->userA);

        $definition = $this->service->create([
            'name' => 'Numéro de contrat',
            'key' => 'numero_contrat',
            'type' => 'string',
            'description' => 'Numéro du contrat client',
            'is_required' => true,
            'is_active' => true,
        ]);

        $this->assertEquals('Numéro de contrat', $definition->name);
        $this->assertEquals('numero_contrat', $definition->key);
        $this->assertEquals('string', $definition->type);
        $this->assertTrue($definition->is_required);
        $this->assertTrue($definition->is_active);
        $this->assertEquals($this->orgA->id, $definition->organization_id);
    }

    public function test_metadata_definition_associated_with_correct_organization(): void
    {
        $this->actingAs($this->userA);
        $defA = $this->service->create([
            'name' => 'Montant HT',
            'key' => 'montant_ht',
            'type' => 'decimal',
        ]);
        $this->assertEquals($this->orgA->id, $defA->organization_id);

        $this->actingAs($this->userB);
        $defB = $this->service->create([
            'name' => 'Montant TTC',
            'key' => 'montant_ttc',
            'type' => 'decimal',
        ]);
        $this->assertEquals($this->orgB->id, $defB->organization_id);
    }

    public function test_same_key_allowed_in_different_organizations(): void
    {
        $this->actingAs($this->userA);
        $defA = $this->service->create([
            'name' => 'Référence',
            'key' => 'reference_doc',
            'type' => 'string',
        ]);

        $this->actingAs($this->userB);
        $defB = $this->service->create([
            'name' => 'Référence',
            'key' => 'reference_doc',
            'type' => 'string',
        ]);

        $this->assertEquals('reference_doc', $defA->key);
        $this->assertEquals('reference_doc', $defB->key);
        $this->assertEquals($this->orgA->id, $defA->organization_id);
        $this->assertEquals($this->orgB->id, $defB->organization_id);
    }

    public function test_duplicate_key_denied_in_same_organization(): void
    {
        $this->actingAs($this->userA);
        $this->service->create([
            'name' => 'Référence',
            'key' => 'reference_doc',
            'type' => 'string',
        ]);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('A metadata definition with this key already exists in your organization.');

        $this->service->create([
            'name' => 'Autre Référence',
            'key' => 'reference_doc',
            'type' => 'text',
        ]);
    }

    public function test_user_without_permission_cannot_create(): void
    {
        $userNoPerm = User::factory()->for($this->orgA)->create();
        $this->actingAs($userNoPerm);

        $this->expectException(AuthorizationException::class);

        $this->service->create([
            'name' => 'Test',
            'key' => 'test_key',
            'type' => 'string',
        ]);
    }

    public function test_cross_tenant_update_denied(): void
    {
        $this->actingAs($this->userA);
        $defA = $this->service->create([
            'name' => 'Fournisseur',
            'key' => 'fournisseur',
            'type' => 'string',
        ]);

        $this->actingAs($this->userB);
        $this->expectException(AuthorizationException::class);

        $this->service->update($defA, ['name' => 'Hacked']);
    }

    public function test_cross_tenant_delete_denied(): void
    {
        $this->actingAs($this->userA);
        $defA = $this->service->create([
            'name' => 'Fournisseur',
            'key' => 'fournisseur',
            'type' => 'string',
        ]);

        $this->actingAs($this->userB);
        $this->expectException(AuthorizationException::class);

        $this->service->delete($defA);
    }

    public function test_soft_delete_metadata_definition(): void
    {
        $this->actingAs($this->userA);
        $definition = $this->service->create([
            'name' => 'Supprimable',
            'key' => 'supprimable',
            'type' => 'string',
        ]);

        $this->service->delete($definition);

        $this->assertSoftDeleted($definition);
    }

    public function test_restore_metadata_definition(): void
    {
        $this->actingAs($this->userA);
        $definition = $this->service->create([
            'name' => 'Restaurable',
            'key' => 'restaurable',
            'type' => 'string',
        ]);
        $this->service->delete($definition);
        $this->assertSoftDeleted($definition);

        $this->service->restore($definition);

        $this->assertNotSoftDeleted($definition);
    }

    public function test_super_admin_has_global_access(): void
    {
        $super = User::factory()->for($this->orgA)->create();
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $role = Role::findOrCreate('super-admin', 'web');
        $super->assignRole($role);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $super = $super->fresh();

        $this->actingAs($this->userB);
        $defB = $this->service->create([
            'name' => 'Définition Org B',
            'key' => 'def_org_b',
            'type' => 'string',
        ]);

        $this->actingAs($super);
        $updated = $this->service->update($defB, ['name' => 'Updated by Super']);

        $this->assertEquals('Updated by Super', $updated->name);
    }

    public function test_key_format_validation(): void
    {
        $this->actingAs($this->userA);

        $invalidKeys = [
            'Numéro Contrat', // Espaces et majuscules
            '123contrat',     // Commence par un chiffre
            'numero-contrat', // Tiret au lieu d'underscore
            'numero contrat', // Espace
            'NUMERO_CONTRAT', // Majuscules
        ];

        foreach ($invalidKeys as $invalidKey) {
            try {
                $this->service->create([
                    'name' => 'Test',
                    'key' => $invalidKey,
                    'type' => 'string',
                ]);
                $this->fail("Key '{$invalidKey}' should have failed validation.");
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('key', $e->errors());
            }
        }
    }

    public function test_type_validation(): void
    {
        $this->actingAs($this->userA);

        $this->expectException(ValidationException::class);

        $this->service->create([
            'name' => 'Test',
            'key' => 'test_invalid_type',
            'type' => 'invalid_type_xyz',
        ]);
    }

    public function test_database_unique_constraint(): void
    {
        $this->actingAs($this->userA);
        MetadataDefinition::factory()->forOrganization($this->orgA)->create(['key' => 'unique_db_key']);

        $this->expectException(QueryException::class);

        // Bypass service validation to test database level constraint
        MetadataDefinition::factory()->forOrganization($this->orgA)->create(['key' => 'unique_db_key']);
    }
}
