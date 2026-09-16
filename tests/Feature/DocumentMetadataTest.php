<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentMetadata;
use App\Models\MetadataDefinition;
use App\Models\Organization;
use App\Models\User;
use App\Services\DocumentMetadataService;
use App\Services\DocumentService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class DocumentMetadataTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $orgA;

    protected Organization $orgB;

    protected User $userA;

    protected User $userB;

    protected Document $documentA;

    protected Document $documentB;

    protected DocumentService $docService;

    protected DocumentMetadataService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');

        $this->docService = new DocumentService;
        $this->service = new DocumentMetadataService;

        $this->orgA = Organization::factory()->create();
        $this->orgB = Organization::factory()->create();

        $this->userA = User::factory()->for($this->orgA)->create();
        $this->userB = User::factory()->for($this->orgB)->create();

        $fileA = UploadedFile::fake()->create('docA.pdf', 100, 'application/pdf');
        $this->documentA = $this->docService->upload([
            'organization_id' => $this->orgA->id,
            'uploaded_by' => $this->userA->id,
            'storage_disk' => 'private',
        ], $fileA);

        $fileB = UploadedFile::fake()->create('docB.pdf', 100, 'application/pdf');
        $this->documentB = $this->docService->upload([
            'organization_id' => $this->orgB->id,
            'uploaded_by' => $this->userB->id,
            'storage_disk' => 'private',
        ], $fileB);
    }

    public function test_create_string_value(): void
    {
        $def = MetadataDefinition::factory()->forOrganization($this->orgA)->withType('string')->create(['key' => 'ref_str']);

        $meta = $this->service->setValue($this->documentA, $def, 'CTR-2026-001');

        $this->assertEquals('CTR-2026-001', $meta->value_string);
        $this->assertNull($meta->value_text);
        $this->assertNull($meta->value_integer);
        $this->assertEquals('CTR-2026-001', $meta->getTypedValue());
    }

    public function test_create_text_value(): void
    {
        $def = MetadataDefinition::factory()->forOrganization($this->orgA)->withType('text')->create(['key' => 'commentaires']);

        $meta = $this->service->setValue($this->documentA, $def, 'Long texte explicatif pour le contrat.');

        $this->assertEquals('Long texte explicatif pour le contrat.', $meta->value_text);
        $this->assertNull($meta->value_string);
        $this->assertEquals('Long texte explicatif pour le contrat.', $meta->getTypedValue());
    }

    public function test_create_integer_value(): void
    {
        $def = MetadataDefinition::factory()->forOrganization($this->orgA)->withType('integer')->create(['key' => 'nb_pages']);

        $meta = $this->service->setValue($this->documentA, $def, 42);

        $this->assertEquals(42, $meta->value_integer);
        $this->assertNull($meta->value_string);
        $this->assertSame(42, $meta->getTypedValue());
    }

    public function test_create_decimal_value(): void
    {
        $def = MetadataDefinition::factory()->forOrganization($this->orgA)->withType('decimal')->create(['key' => 'montant_total']);

        $meta = $this->service->setValue($this->documentA, $def, 1500.75);

        $this->assertEquals(1500.75, $meta->value_decimal);
        $this->assertSame(1500.75, $meta->getTypedValue());
    }

    public function test_create_boolean_value(): void
    {
        $def = MetadataDefinition::factory()->forOrganization($this->orgA)->withType('boolean')->create(['key' => 'est_valide']);

        $meta = $this->service->setValue($this->documentA, $def, true);

        $this->assertTrue($meta->value_boolean);
        $this->assertTrue($meta->getTypedValue());
    }

    public function test_create_date_value(): void
    {
        $def = MetadataDefinition::factory()->forOrganization($this->orgA)->withType('date')->create(['key' => 'date_signature']);

        $meta = $this->service->setValue($this->documentA, $def, '2026-09-15');

        $this->assertEquals('2026-09-15', $meta->value_date->format('Y-m-d'));
        $this->assertEquals('2026-09-15', $meta->getTypedValue());
    }

    public function test_create_datetime_value(): void
    {
        $def = MetadataDefinition::factory()->forOrganization($this->orgA)->withType('datetime')->create(['key' => 'horodatage']);

        $meta = $this->service->setValue($this->documentA, $def, '2026-09-15 10:30:00');

        $this->assertEquals('2026-09-15 10:30:00', $meta->value_datetime->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-09-15 10:30:00', $meta->getTypedValue());
    }

    public function test_update_existing_value(): void
    {
        $def = MetadataDefinition::factory()->forOrganization($this->orgA)->withType('string')->create(['key' => 'num_contrat']);

        $this->service->setValue($this->documentA, $def, 'V1');
        $this->assertCount(1, $this->documentA->fresh()->metadataValues);

        $updated = $this->service->setValue($this->documentA, $def, 'V2');

        $this->assertEquals('V2', $updated->value_string);
        $this->assertCount(1, $this->documentA->fresh()->metadataValues);
    }

    public function test_no_duplicate_values(): void
    {
        $def = MetadataDefinition::factory()->forOrganization($this->orgA)->withType('string')->create(['key' => 'reference']);

        $this->service->setValue($this->documentA, $def, 'REF1');
        $this->service->setValue($this->documentA, $def, 'REF2');

        $this->assertCount(1, DocumentMetadata::where('document_id', $this->documentA->id)->where('metadata_definition_id', $def->id)->get());
    }

    public function test_remove_single_value(): void
    {
        $def = MetadataDefinition::factory()->forOrganization($this->orgA)->withType('string')->create(['key' => 'reference']);
        $this->service->setValue($this->documentA, $def, 'REF1');

        $this->service->removeValue($this->documentA, $def);

        $this->assertCount(0, $this->documentA->fresh()->metadataValues);
    }

    public function test_remove_all_values(): void
    {
        $def1 = MetadataDefinition::factory()->forOrganization($this->orgA)->withType('string')->create(['key' => 'ref1']);
        $def2 = MetadataDefinition::factory()->forOrganization($this->orgA)->withType('integer')->create(['key' => 'ref2']);

        $this->service->setValue($this->documentA, $def1, 'Val1');
        $this->service->setValue($this->documentA, $def2, 123);

        $this->assertCount(2, $this->documentA->fresh()->metadataValues);

        $this->service->removeAll($this->documentA);

        $this->assertCount(0, $this->documentA->fresh()->metadataValues);
    }

    public function test_inactive_definition_refuses_new_value(): void
    {
        $def = MetadataDefinition::factory()->forOrganization($this->orgA)->inactive()->create(['key' => 'inactif_field']);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage("Cannot set value for inactive metadata definition 'inactif_field'");

        $this->service->setValue($this->documentA, $def, 'test');
    }

    public function test_reading_existing_value_of_inactive_definition_is_allowed(): void
    {
        $def = MetadataDefinition::factory()->forOrganization($this->orgA)->withType('string')->create(['key' => 'historique']);
        $this->service->setValue($this->documentA, $def, 'Valeur initiale');

        // Deactivate definition
        $def->update(['is_active' => false]);

        $metadataList = $this->service->getDocumentMetadata($this->documentA);

        $this->assertCount(1, $metadataList);
        $this->assertEquals('historique', $metadataList[0]['key']);
        $this->assertEquals('Valeur initiale', $metadataList[0]['value']);
    }

    public function test_required_field_missing_is_refused(): void
    {
        $def = MetadataDefinition::factory()->forOrganization($this->orgA)->required()->create(['key' => 'champ_requis']);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage("Metadata field 'champ_requis' is required");

        $this->service->setValue($this->documentA, $def, null);
    }

    public function test_required_field_present_is_accepted(): void
    {
        $def = MetadataDefinition::factory()->forOrganization($this->orgA)->withType('string')->required()->create(['key' => 'champ_requis']);

        $meta = $this->service->setValue($this->documentA, $def, 'Présent');

        $this->assertEquals('Présent', $meta->value_string);
    }

    public function test_invalid_type_is_refused(): void
    {
        $def = MetadataDefinition::factory()->forOrganization($this->orgA)->withType('integer')->create(['key' => 'nombre']);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage("Invalid integer value for metadata 'nombre'");

        $this->service->setValue($this->documentA, $def, 'abc_not_a_number');
    }

    public function test_invalid_date_type_is_refused(): void
    {
        $def = MetadataDefinition::factory()->forOrganization($this->orgA)->withType('date')->create(['key' => 'date_doc']);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage("Invalid date format for metadata 'date_doc', expected Y-m-d");

        $this->service->setValue($this->documentA, $def, '31/12/2026');
    }

    public function test_document_cross_tenant_is_refused(): void
    {
        // def from Org A, Document from Org B
        $defA = MetadataDefinition::factory()->forOrganization($this->orgA)->create(['key' => 'cle_a']);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Metadata definition belongs to a different organization');

        $this->service->setValue($this->documentB, $defA, 'valeur');
    }

    public function test_definition_cross_tenant_is_refused(): void
    {
        // def from Org B, Document from Org A
        $defB = MetadataDefinition::factory()->forOrganization($this->orgB)->create(['key' => 'cle_b']);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Metadata definition belongs to a different organization');

        $this->service->setValue($this->documentA, $defB, 'valeur');
    }

    public function test_set_values_atomic_success(): void
    {
        $def1 = MetadataDefinition::factory()->forOrganization($this->orgA)->withType('string')->create(['key' => 'contrat_ref']);
        $def2 = MetadataDefinition::factory()->forOrganization($this->orgA)->withType('integer')->create(['key' => 'nb_pages']);
        $def3 = MetadataDefinition::factory()->forOrganization($this->orgA)->withType('decimal')->create(['key' => 'montant']);

        $this->service->setValues($this->documentA, [
            'contrat_ref' => 'CTR-2026-99',
            'nb_pages' => 12,
            'montant' => 4500.50,
        ]);

        $this->assertCount(3, $this->documentA->fresh()->metadataValues);

        $metadataList = $this->service->getDocumentMetadata($this->documentA);
        $indexed = collect($metadataList)->keyBy('key');

        $this->assertEquals('CTR-2026-99', $indexed['contrat_ref']['value']);
        $this->assertSame(12, $indexed['nb_pages']['value']);
        $this->assertSame(4500.5, $indexed['montant']['value']);
    }

    public function test_set_values_atomic_rollback_on_error(): void
    {
        $def1 = MetadataDefinition::factory()->forOrganization($this->orgA)->withType('string')->create(['key' => 'contrat_ref']);
        $def2 = MetadataDefinition::factory()->forOrganization($this->orgA)->withType('integer')->create(['key' => 'nb_pages']);

        try {
            $this->service->setValues($this->documentA, [
                'contrat_ref' => 'CTR-2026-99',
                'nb_pages' => 'invalid_integer_string', // Should fail validation!
            ]);
            $this->fail('Should have thrown an HttpException');
        } catch (HttpException $e) {
            // Expected
        }

        // Verify that NO records were saved (atomic rollback)
        $this->assertCount(0, $this->documentA->fresh()->metadataValues);
    }

    public function test_set_values_refuses_when_required_definition_missing(): void
    {
        MetadataDefinition::factory()->forOrganization($this->orgA)->withType('string')->required()->create(['key' => 'requis_obligatoire']);
        $def2 = MetadataDefinition::factory()->forOrganization($this->orgA)->withType('string')->create(['key' => 'optionnel']);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage("Required metadata field 'requis_obligatoire' is missing");

        $this->service->setValues($this->documentA, [
            'optionnel' => 'Quelque chose',
        ]);
    }

    public function test_soft_deleted_document_refuses_metadata_write(): void
    {
        $def = MetadataDefinition::factory()->forOrganization($this->orgA)->withType('string')->create(['key' => 'reference']);
        $this->documentA->delete();

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Document is deleted');

        $this->service->setValue($this->documentA, $def, 'val');
    }

    public function test_soft_deleted_definition_refuses_metadata_write(): void
    {
        $def = MetadataDefinition::factory()->forOrganization($this->orgA)->withType('string')->create(['key' => 'reference']);
        $def->delete();

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Metadata definition is deleted');

        $this->service->setValue($this->documentA, $def, 'val');
    }

    public function test_restore_definition_makes_historical_values_available(): void
    {
        $def = MetadataDefinition::factory()->forOrganization($this->orgA)->withType('string')->create(['key' => 'reference']);
        $this->service->setValue($this->documentA, $def, 'Valeur de test');

        $def->delete();
        // Definition is soft deleted, document still has the row in DB
        $this->assertDatabaseHas('document_metadata', [
            'document_id' => $this->documentA->id,
            'metadata_definition_id' => $def->id,
        ]);

        $def->restore();

        $metadata = $this->service->getDocumentMetadata($this->documentA);
        $this->assertCount(1, $metadata);
        $this->assertEquals('Valeur de test', $metadata[0]['value']);
    }

    public function test_super_admin_can_access_any_organization_metadata(): void
    {
        $super = User::factory()->for($this->orgA)->create();
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $role = Role::findOrCreate('super-admin', 'web');
        $super->assignRole($role);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $super = $super->fresh();

        $this->actingAs($super);

        $defB = MetadataDefinition::factory()->forOrganization($this->orgB)->withType('string')->create(['key' => 'secret_b']);
        $this->service->setValue($this->documentB, $defB, 'SuperAdminAccessible');

        $metadataB = $this->service->getDocumentMetadata($this->documentB);
        $this->assertEquals('SuperAdminAccessible', $metadataB[0]['value']);
    }

    public function test_database_unique_constraint_on_document_and_definition(): void
    {
        $def = MetadataDefinition::factory()->forOrganization($this->orgA)->withType('string')->create(['key' => 'unique_def']);

        $this->service->setValue($this->documentA, $def, 'Premiere valeur');

        $this->expectException(QueryException::class);

        // Bypass service updateOrCreate to directly test database unique constraint
        DB::table('document_metadata')->insert([
            'document_id' => $this->documentA->id,
            'metadata_definition_id' => $def->id,
            'value_string' => 'Doublon direct en base',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
