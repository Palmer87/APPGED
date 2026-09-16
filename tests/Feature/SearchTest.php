<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Document;
use App\Models\DocumentMetadata;
use App\Models\Folder;
use App\Models\Group;
use App\Models\MetadataDefinition;
use App\Models\Organization;
use App\Models\Tag;
use App\Models\User;
use App\Services\AccessControlService;
use App\Services\SearchService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    protected SearchService $searchService;

    protected AccessControlService $aclService;

    protected Organization $orgA;

    protected Organization $orgB;

    protected User $userA;

    protected User $userB;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aclService = app(AccessControlService::class);
        $this->searchService = app(SearchService::class);

        $this->orgA = Organization::factory()->create();
        $this->orgB = Organization::factory()->create();

        $this->userA = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->userB = User::factory()->create(['organization_id' => $this->orgB->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $memberRoleA = Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);
        $perms = ['documents.view', 'documents.create', 'documents.update', 'documents.delete', 'documents.download'];
        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        $memberRoleA->givePermissionTo($perms);
        $this->userA->assignRole($memberRoleA);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgB->id);
        $memberRoleB = Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);
        $memberRoleB->givePermissionTo($perms);
        $this->userB->assignRole($memberRoleB);

        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->superAdmin = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->superAdmin->assignRole($superAdminRole);
    }

    protected function createDocument(array $attributes = [], ?User $aclUser = null): Document
    {
        $orgId = $attributes['organization_id'] ?? $this->orgA->id;
        $uploaderId = $attributes['uploaded_by'] ?? $this->userA->id;

        $doc = Document::factory()->create(array_merge([
            'organization_id' => $orgId,
            'uploaded_by' => $uploaderId,
            'status' => 'active',
            'extension' => 'pdf',
        ], $attributes));

        if ($aclUser) {
            $this->aclService->grantDocumentPermission($doc, $aclUser, 'view');
        }

        return $doc;
    }

    // ==========================================
    // 1. RECHERCHE TEXTUELLE (Item 38)
    // ==========================================

    public function test_search_by_name(): void
    {
        $doc1 = $this->createDocument(['name' => 'Contrat Fournisseur 2026'], $this->userA);
        $doc2 = $this->createDocument(['name' => 'Facture Client'], $this->userA);

        $results = $this->searchService->search($this->userA, ['q' => 'Contrat']);

        $this->assertCount(1, $results);
        $this->assertEquals($doc1->id, $results->first()->id);
    }

    public function test_search_by_description(): void
    {
        $doc1 = $this->createDocument(['description' => 'Document hautement stratégique'], $this->userA);
        $doc2 = $this->createDocument(['description' => 'Simple note interne'], $this->userA);

        $results = $this->searchService->search($this->userA, ['q' => 'stratégique']);

        $this->assertCount(1, $results);
        $this->assertEquals($doc1->id, $results->first()->id);
    }

    public function test_search_by_file_name(): void
    {
        $doc1 = $this->createDocument(['file_name' => 'rapport_audit_q1.pdf'], $this->userA);
        $doc2 = $this->createDocument(['file_name' => 'budget_2026.xlsx'], $this->userA);

        $results = $this->searchService->search($this->userA, ['q' => 'audit']);

        $this->assertCount(1, $results);
        $this->assertEquals($doc1->id, $results->first()->id);
    }

    public function test_search_is_case_insensitive(): void
    {
        $doc = $this->createDocument(['name' => 'Contrat Fournisseur'], $this->userA);

        $resultsUpper = $this->searchService->search($this->userA, ['q' => 'CONTRAT']);
        $resultsLower = $this->searchService->search($this->userA, ['q' => 'contrat']);

        $this->assertCount(1, $resultsUpper);
        $this->assertCount(1, $resultsLower);
        $this->assertEquals($doc->id, $resultsUpper->first()->id);
        $this->assertEquals($doc->id, $resultsLower->first()->id);
    }

    public function test_search_partial_matching(): void
    {
        $doc1 = $this->createDocument(['name' => 'Contrat Fournisseur.pdf'], $this->userA);
        $doc2 = $this->createDocument(['name' => 'contrat-client.pdf'], $this->userA);
        $doc3 = $this->createDocument(['name' => 'Autre fichier.pdf'], $this->userA);

        $results = $this->searchService->search($this->userA, ['q' => 'contr']);

        $this->assertCount(2, $results);
        $ids = $results->pluck('id')->all();
        $this->assertContains($doc1->id, $ids);
        $this->assertContains($doc2->id, $ids);
        $this->assertNotContains($doc3->id, $ids);
    }

    public function test_empty_q_with_filters(): void
    {
        $doc1 = $this->createDocument(['status' => 'archived'], $this->userA);
        $doc2 = $this->createDocument(['status' => 'active'], $this->userA);

        $results = $this->searchService->search($this->userA, ['q' => '', 'status' => 'archived']);

        $this->assertCount(1, $results);
        $this->assertEquals($doc1->id, $results->first()->id);
    }

    public function test_no_filters_returns_accessible_documents_sorted_desc(): void
    {
        $doc1 = $this->createDocument(['created_at' => Carbon::now()->subDays(2)], $this->userA);
        $doc2 = $this->createDocument(['created_at' => Carbon::now()->subDay()], $this->userA);
        $doc3 = $this->createDocument(['created_at' => Carbon::now()], $this->userA);

        $results = $this->searchService->search($this->userA);

        $this->assertCount(3, $results);
        $this->assertEquals($doc3->id, $results[0]->id);
        $this->assertEquals($doc2->id, $results[1]->id);
        $this->assertEquals($doc1->id, $results[2]->id);
    }

    // ==========================================
    // 2. TENANT & SUPER-ADMIN (Item 39 & 49)
    // ==========================================

    public function test_tenant_isolation_user_a_never_sees_user_b_documents(): void
    {
        $docA = $this->createDocument(['organization_id' => $this->orgA->id, 'name' => 'Commun.pdf'], $this->userA);
        $docB = $this->createDocument(['organization_id' => $this->orgB->id, 'name' => 'Commun.pdf'], $this->userB);

        $resultsA = $this->searchService->search($this->userA, ['q' => 'Commun']);
        $this->assertCount(1, $resultsA);
        $this->assertEquals($docA->id, $resultsA->first()->id);

        $resultsB = $this->searchService->search($this->userB, ['q' => 'Commun']);
        $this->assertCount(1, $resultsB);
        $this->assertEquals($docB->id, $resultsB->first()->id);
    }

    public function test_super_admin_can_see_documents_across_all_organizations(): void
    {
        $docA = $this->createDocument(['organization_id' => $this->orgA->id, 'name' => 'Facture Global A']);
        $docB = $this->createDocument(['organization_id' => $this->orgB->id, 'name' => 'Facture Global B']);

        $results = $this->searchService->search($this->superAdmin, ['q' => 'Facture Global']);

        $this->assertCount(2, $results);
    }

    public function test_super_admin_can_filter_by_organization(): void
    {
        $docA = $this->createDocument(['organization_id' => $this->orgA->id, 'name' => 'Doc Specifique']);
        $docB = $this->createDocument(['organization_id' => $this->orgB->id, 'name' => 'Doc Specifique']);

        $results = $this->searchService->search($this->superAdmin, [
            'q' => 'Doc Specifique',
            'organization_id' => $this->orgA->id,
        ]);

        $this->assertCount(1, $results);
        $this->assertEquals($docA->id, $results->first()->id);
    }

    // ==========================================
    // 3. ACL TESTS (Item 40)
    // ==========================================

    public function test_document_without_acl_is_absent(): void
    {
        $this->createDocument(['name' => 'SecretSansAcl.pdf']);

        $results = $this->searchService->search($this->userA, ['q' => 'SecretSansAcl']);

        $this->assertCount(0, $results);
    }

    public function test_document_with_direct_user_acl_is_present(): void
    {
        $doc = $this->createDocument(['name' => 'DirectAcl.pdf']);
        $this->aclService->grantDocumentPermission($doc, $this->userA, 'view');

        $results = $this->searchService->search($this->userA, ['q' => 'DirectAcl']);

        $this->assertCount(1, $results);
        $this->assertEquals($doc->id, $results->first()->id);
    }

    public function test_document_with_group_acl_is_present(): void
    {
        $group = Group::factory()->create(['organization_id' => $this->orgA->id]);
        $this->userA->groups()->attach($group->id);

        $doc = $this->createDocument(['name' => 'GroupAcl.pdf']);
        $this->aclService->grantDocumentPermissionToGroup($doc, $group, 'view');

        $results = $this->searchService->search($this->userA, ['q' => 'GroupAcl']);

        $this->assertCount(1, $results);
        $this->assertEquals($doc->id, $results->first()->id);
    }

    public function test_document_with_parent_folder_acl_is_present(): void
    {
        $folder = Folder::factory()->create(['organization_id' => $this->orgA->id]);
        $this->aclService->grantFolderPermission($folder, $this->userA, 'view');

        $doc = $this->createDocument([
            'name' => 'FolderAcl.pdf',
            'folder_id' => $folder->id,
        ]);

        $results = $this->searchService->search($this->userA, ['q' => 'FolderAcl']);

        $this->assertCount(1, $results);
        $this->assertEquals($doc->id, $results->first()->id);
    }

    public function test_document_after_acl_revocation_is_absent(): void
    {
        $doc = $this->createDocument(['name' => 'RevokeAcl.pdf']);
        $this->aclService->grantDocumentPermission($doc, $this->userA, 'view');

        $this->assertCount(1, $this->searchService->search($this->userA, ['q' => 'RevokeAcl']));

        $this->aclService->revokeDocumentPermission($doc, $this->userA, 'view');

        $this->assertCount(0, $this->searchService->search($this->userA, ['q' => 'RevokeAcl']));
    }

    // ==========================================
    // 4. VIEW VS DOWNLOAD (Item 41)
    // ==========================================

    public function test_user_with_view_can_search_document_but_cannot_download_it(): void
    {
        $doc = $this->createDocument(['name' => 'ViewOnly.pdf']);
        $this->aclService->grantDocumentPermission($doc, $this->userA, 'view');

        // Document is visible in search
        $results = $this->searchService->search($this->userA, ['q' => 'ViewOnly']);
        $this->assertCount(1, $results);

        // Document download Gate rejects
        $this->assertFalse(Gate::forUser($this->userA)->allows('download', $doc));
    }

    // ==========================================
    // 5. CATÉGORIE (Item 42)
    // ==========================================

    public function test_filter_by_category_returns_matching_documents(): void
    {
        $cat = Category::factory()->create(['organization_id' => $this->orgA->id]);
        $doc1 = $this->createDocument(['name' => 'DocCat1.pdf'], $this->userA);
        $doc2 = $this->createDocument(['name' => 'DocCat2.pdf'], $this->userA);
        $doc1->categories()->attach($cat->id);

        $results = $this->searchService->search($this->userA, ['category_id' => $cat->id]);

        $this->assertCount(1, $results);
        $this->assertEquals($doc1->id, $results->first()->id);
    }

    public function test_filter_by_wrong_category_returns_no_results(): void
    {
        $cat1 = Category::factory()->create(['organization_id' => $this->orgA->id]);
        $cat2 = Category::factory()->create(['organization_id' => $this->orgA->id]);
        $doc = $this->createDocument(['name' => 'DocCat.pdf'], $this->userA);
        $doc->categories()->attach($cat1->id);

        $results = $this->searchService->search($this->userA, ['category_id' => $cat2->id]);

        $this->assertCount(0, $results);
    }

    public function test_filter_by_cross_tenant_category_returns_no_results(): void
    {
        $catB = Category::factory()->create(['organization_id' => $this->orgB->id]);
        $this->createDocument(['name' => 'DocA.pdf'], $this->userA);

        $results = $this->searchService->search($this->userA, ['category_id' => $catB->id]);

        $this->assertCount(0, $results);
    }

    public function test_filter_by_deleted_category_returns_no_results(): void
    {
        $cat = Category::factory()->create(['organization_id' => $this->orgA->id]);
        $doc = $this->createDocument(['name' => 'DocDeletedCat.pdf'], $this->userA);
        $doc->categories()->attach($cat->id);
        $cat->delete();

        $results = $this->searchService->search($this->userA, ['category_id' => $cat->id]);

        $this->assertCount(0, $results);
    }

    // ==========================================
    // 6. TAGS (Item 43)
    // ==========================================

    public function test_filter_by_single_tag(): void
    {
        $tag = Tag::factory()->create(['organization_id' => $this->orgA->id]);
        $doc1 = $this->createDocument(['name' => 'Tag1.pdf'], $this->userA);
        $doc2 = $this->createDocument(['name' => 'Tag2.pdf'], $this->userA);
        $doc1->tags()->attach($tag->id);

        $results = $this->searchService->search($this->userA, ['tag_id' => $tag->id]);

        $this->assertCount(1, $results);
        $this->assertEquals($doc1->id, $results->first()->id);
    }

    public function test_filter_by_multiple_tags_requires_all_tags_and_semantics(): void
    {
        $tag1 = Tag::factory()->create(['organization_id' => $this->orgA->id, 'name' => 'urgent']);
        $tag2 = Tag::factory()->create(['organization_id' => $this->orgA->id, 'name' => 'juridique']);

        $docBoth = $this->createDocument(['name' => 'DocBoth.pdf'], $this->userA);
        $docOnly1 = $this->createDocument(['name' => 'DocOnly1.pdf'], $this->userA);

        $docBoth->tags()->attach([$tag1->id, $tag2->id]);
        $docOnly1->tags()->attach([$tag1->id]);

        $results = $this->searchService->search($this->userA, ['tag_ids' => [$tag1->id, $tag2->id]]);

        $this->assertCount(1, $results);
        $this->assertEquals($docBoth->id, $results->first()->id);
    }

    public function test_filter_by_cross_tenant_tag_returns_no_results(): void
    {
        $tagB = Tag::factory()->create(['organization_id' => $this->orgB->id]);
        $this->createDocument(['name' => 'DocTagA.pdf'], $this->userA);

        $results = $this->searchService->search($this->userA, ['tag_id' => $tagB->id]);

        $this->assertCount(0, $results);
    }

    public function test_filter_by_deleted_tag_returns_no_results(): void
    {
        $tag = Tag::factory()->create(['organization_id' => $this->orgA->id]);
        $doc = $this->createDocument(['name' => 'DocDeletedTag.pdf'], $this->userA);
        $doc->tags()->attach($tag->id);
        $tag->delete();

        $results = $this->searchService->search($this->userA, ['tag_id' => $tag->id]);

        $this->assertCount(0, $results);
    }

    // ==========================================
    // 7. MÉTADONNÉES (Item 44)
    // ==========================================

    public function test_filter_by_metadata_string(): void
    {
        $def = MetadataDefinition::factory()->create([
            'organization_id' => $this->orgA->id,
            'key' => 'numero_contrat',
            'type' => 'string',
        ]);
        $doc = $this->createDocument(['name' => 'DocMetaStr.pdf'], $this->userA);
        DocumentMetadata::create([
            'document_id' => $doc->id,
            'metadata_definition_id' => $def->id,
            'value_string' => 'CTR-2026-001',
        ]);

        $results = $this->searchService->search($this->userA, [
            'metadata_key' => 'numero_contrat',
            'metadata_value' => 'CTR-2026-001',
        ]);

        $this->assertCount(1, $results);
        $this->assertEquals($doc->id, $results->first()->id);
    }

    public function test_filter_by_metadata_integer(): void
    {
        $def = MetadataDefinition::factory()->create([
            'organization_id' => $this->orgA->id,
            'key' => 'nb_pages',
            'type' => 'integer',
        ]);
        $doc1 = $this->createDocument(['name' => 'Doc10Pages.pdf'], $this->userA);
        $doc2 = $this->createDocument(['name' => 'Doc20Pages.pdf'], $this->userA);

        DocumentMetadata::create([
            'document_id' => $doc1->id,
            'metadata_definition_id' => $def->id,
            'value_integer' => 10,
        ]);
        DocumentMetadata::create([
            'document_id' => $doc2->id,
            'metadata_definition_id' => $def->id,
            'value_integer' => 20,
        ]);

        $results = $this->searchService->search($this->userA, [
            'metadata_key' => 'nb_pages',
            'metadata_value' => 10,
        ]);

        $this->assertCount(1, $results);
        $this->assertEquals($doc1->id, $results->first()->id);
    }

    public function test_filter_by_metadata_decimal(): void
    {
        $def = MetadataDefinition::factory()->create([
            'organization_id' => $this->orgA->id,
            'key' => 'montant_total',
            'type' => 'decimal',
        ]);
        $doc = $this->createDocument(['name' => 'DocDecimal.pdf'], $this->userA);
        DocumentMetadata::create([
            'document_id' => $doc->id,
            'metadata_definition_id' => $def->id,
            'value_decimal' => 500000.50,
        ]);

        $results = $this->searchService->search($this->userA, [
            'metadata_key' => 'montant_total',
            'metadata_value' => 500000.50,
        ]);

        $this->assertCount(1, $results);
        $this->assertEquals($doc->id, $results->first()->id);
    }

    public function test_filter_by_metadata_boolean(): void
    {
        $def = MetadataDefinition::factory()->create([
            'organization_id' => $this->orgA->id,
            'key' => 'est_signe',
            'type' => 'boolean',
        ]);
        $doc = $this->createDocument(['name' => 'DocSigne.pdf'], $this->userA);
        DocumentMetadata::create([
            'document_id' => $doc->id,
            'metadata_definition_id' => $def->id,
            'value_boolean' => true,
        ]);

        $results = $this->searchService->search($this->userA, [
            'metadata_key' => 'est_signe',
            'metadata_value' => true,
        ]);

        $this->assertCount(1, $results);
        $this->assertEquals($doc->id, $results->first()->id);
    }

    public function test_filter_by_metadata_date(): void
    {
        $def = MetadataDefinition::factory()->create([
            'organization_id' => $this->orgA->id,
            'key' => 'date_echeance',
            'type' => 'date',
        ]);
        $doc = $this->createDocument(['name' => 'DocDate.pdf'], $this->userA);
        DocumentMetadata::create([
            'document_id' => $doc->id,
            'metadata_definition_id' => $def->id,
            'value_date' => '2026-12-31',
        ]);

        $results = $this->searchService->search($this->userA, [
            'metadata_key' => 'date_echeance',
            'metadata_value' => '2026-12-31',
        ]);

        $this->assertCount(1, $results);
        $this->assertEquals($doc->id, $results->first()->id);
    }

    public function test_filter_by_metadata_datetime(): void
    {
        $def = MetadataDefinition::factory()->create([
            'organization_id' => $this->orgA->id,
            'key' => 'horodatage',
            'type' => 'datetime',
        ]);
        $doc = $this->createDocument(['name' => 'DocDatetime.pdf'], $this->userA);
        DocumentMetadata::create([
            'document_id' => $doc->id,
            'metadata_definition_id' => $def->id,
            'value_datetime' => '2026-09-15 10:00:00',
        ]);

        $results = $this->searchService->search($this->userA, [
            'metadata_key' => 'horodatage',
            'metadata_value' => '2026-09-15 10:00:00',
        ]);

        $this->assertCount(1, $results);
        $this->assertEquals($doc->id, $results->first()->id);
    }

    public function test_filter_by_cross_tenant_metadata_returns_no_results(): void
    {
        $defB = MetadataDefinition::factory()->create([
            'organization_id' => $this->orgB->id,
            'key' => 'cle_externe',
            'type' => 'string',
        ]);
        $this->createDocument(['name' => 'DocMetaCross.pdf'], $this->userA);

        $results = $this->searchService->search($this->userA, [
            'metadata_key' => 'cle_externe',
            'metadata_value' => 'test',
        ]);

        $this->assertCount(0, $results);
    }

    // ==========================================
    // 8. DATES & EXTENSION & STATUT (Item 45, 16, 17)
    // ==========================================

    public function test_filter_by_created_date_range(): void
    {
        $doc1 = $this->createDocument(['name' => 'DocJanvier.pdf', 'created_at' => '2026-01-15 12:00:00'], $this->userA);
        $doc2 = $this->createDocument(['name' => 'DocMars.pdf', 'created_at' => '2026-03-20 12:00:00'], $this->userA);
        $doc3 = $this->createDocument(['name' => 'DocSeptembre.pdf', 'created_at' => '2026-09-10 12:00:00'], $this->userA);

        $results = $this->searchService->search($this->userA, [
            'created_from' => '2026-02-01',
            'created_to' => '2026-08-31',
        ]);

        $this->assertCount(1, $results);
        $this->assertEquals($doc2->id, $results->first()->id);
    }

    public function test_filter_by_updated_date_range(): void
    {
        $doc1 = $this->createDocument(['name' => 'DocOld.pdf', 'updated_at' => '2026-01-10 12:00:00'], $this->userA);
        $doc2 = $this->createDocument(['name' => 'DocNew.pdf', 'updated_at' => '2026-09-15 12:00:00'], $this->userA);

        $results = $this->searchService->search($this->userA, [
            'updated_from' => '2026-09-01',
        ]);

        $this->assertCount(1, $results);
        $this->assertEquals($doc2->id, $results->first()->id);
    }

    public function test_filter_by_extension_normalized(): void
    {
        $docPdf = $this->createDocument(['extension' => 'pdf'], $this->userA);
        $docPng = $this->createDocument(['extension' => 'png'], $this->userA);

        $results = $this->searchService->search($this->userA, ['extension' => '.PDF']);

        $this->assertCount(1, $results);
        $this->assertEquals($docPdf->id, $results->first()->id);
    }

    public function test_filter_by_status(): void
    {
        $docActive = $this->createDocument(['status' => 'active'], $this->userA);
        $docArchived = $this->createDocument(['status' => 'archived'], $this->userA);

        $results = $this->searchService->search($this->userA, ['status' => 'archived']);

        $this->assertCount(1, $results);
        $this->assertEquals($docArchived->id, $results->first()->id);
    }

    // ==========================================
    // 9. TRI & VALIDATION (Item 46)
    // ==========================================

    public function test_sort_by_name_asc_and_desc(): void
    {
        $docA = $this->createDocument(['name' => 'Alpha'], $this->userA);
        $docZ = $this->createDocument(['name' => 'Zoulou'], $this->userA);

        $asc = $this->searchService->search($this->userA, ['sort' => 'name', 'direction' => 'asc']);
        $desc = $this->searchService->search($this->userA, ['sort' => 'name', 'direction' => 'desc']);

        $this->assertEquals($docA->id, $asc[0]->id);
        $this->assertEquals($docZ->id, $desc[0]->id);
    }

    public function test_sort_by_size_asc_and_desc(): void
    {
        $small = $this->createDocument(['size' => 100], $this->userA);
        $large = $this->createDocument(['size' => 10000], $this->userA);

        $asc = $this->searchService->search($this->userA, ['sort' => 'size', 'direction' => 'asc']);
        $desc = $this->searchService->search($this->userA, ['sort' => 'size', 'direction' => 'desc']);

        $this->assertEquals($small->id, $asc[0]->id);
        $this->assertEquals($large->id, $desc[0]->id);
    }

    public function test_invalid_sort_column_throws_validation_exception(): void
    {
        $this->expectException(ValidationException::class);

        $this->searchService->search($this->userA, ['sort' => 'injection_column; DROP TABLE--']);
    }

    // ==========================================
    // 10. PAGINATION (Item 47)
    // ==========================================

    public function test_pagination_with_per_page(): void
    {
        for ($i = 1; $i <= 7; $i++) {
            $this->createDocument(['name' => "DocPaginate_{$i}.pdf"], $this->userA);
        }

        $results = $this->searchService->search($this->userA, ['per_page' => 5]);

        $this->assertEquals(5, $results->perPage());
        $this->assertEquals(7, $results->total());
        $this->assertCount(5, $results);
    }

    public function test_per_page_exceeding_100_throws_validation_exception(): void
    {
        $this->expectException(ValidationException::class);

        $this->searchService->search($this->userA, ['per_page' => 1000000]);
    }

    // ==========================================
    // 11. SOFT DELETE (Item 48)
    // ==========================================

    public function test_soft_deleted_document_is_excluded_and_reappears_on_restore(): void
    {
        $doc = $this->createDocument(['name' => 'DocSoftDelete.pdf'], $this->userA);

        $this->assertCount(1, $this->searchService->search($this->userA, ['q' => 'DocSoftDelete']));

        $doc->delete();
        $this->assertCount(0, $this->searchService->search($this->userA, ['q' => 'DocSoftDelete']));

        $doc->restore();
        $this->assertCount(1, $this->searchService->search($this->userA, ['q' => 'DocSoftDelete']));
    }

    // ==========================================
    // 12. TEST ANTI-FUITE CRITIQUE (Item 50)
    // ==========================================

    public function test_anti_leak_cross_tenant_confidential_document_never_found_even_with_exact_keyword_and_metadata(): void
    {
        $catA = Category::factory()->create(['organization_id' => $this->orgA->id]);
        $tagA = Tag::factory()->create(['organization_id' => $this->orgA->id]);
        $defA = MetadataDefinition::factory()->create([
            'organization_id' => $this->orgA->id,
            'key' => 'secret_code',
            'type' => 'string',
        ]);

        $confidentialDoc = $this->createDocument([
            'organization_id' => $this->orgA->id,
            'name' => 'Contrat Secret ABC',
            'file_name' => 'secret_defense.pdf',
            'description' => 'Informations hautement confidentielles',
        ], $this->userA);

        $confidentialDoc->categories()->attach($catA->id);
        $confidentialDoc->tags()->attach($tagA->id);
        DocumentMetadata::create([
            'document_id' => $confidentialDoc->id,
            'metadata_definition_id' => $defA->id,
            'value_string' => 'TOP_SECRET_CODE',
        ]);

        // User B searches exact keywords and filters
        $resultsKeyword = $this->searchService->search($this->userB, ['q' => 'Secret']);
        $this->assertCount(0, $resultsKeyword);

        $resultsExactName = $this->searchService->search($this->userB, ['q' => 'Contrat Secret ABC']);
        $this->assertCount(0, $resultsExactName);

        $resultsFileName = $this->searchService->search($this->userB, ['q' => 'secret_defense']);
        $this->assertCount(0, $resultsFileName);

        $resultsDescription = $this->searchService->search($this->userB, ['q' => 'confidentielles']);
        $this->assertCount(0, $resultsDescription);
    }

    // ==========================================
    // 13. TEST ANTI-N+1 (Item 51)
    // ==========================================

    public function test_search_eager_loads_relations_without_n_plus_one_queries(): void
    {
        $cat = Category::factory()->create(['organization_id' => $this->orgA->id]);
        $tag = Tag::factory()->create(['organization_id' => $this->orgA->id]);
        $def = MetadataDefinition::factory()->create(['organization_id' => $this->orgA->id, 'key' => 'nplusone_key']);

        for ($i = 1; $i <= 5; $i++) {
            $doc = $this->createDocument(['name' => "DocN1_{$i}.pdf"], $this->userA);
            $doc->categories()->attach($cat->id);
            $doc->tags()->attach($tag->id);
            DocumentMetadata::create([
                'document_id' => $doc->id,
                'metadata_definition_id' => $def->id,
                'value_string' => "Val_{$i}",
            ]);
        }

        $queryCount = 0;
        DB::listen(function () use (&$queryCount) {
            $queryCount++;
        });

        $results = $this->searchService->search($this->userA, ['per_page' => 10]);

        // Access eager-loaded relationships
        foreach ($results as $doc) {
            $this->assertNotEmpty($doc->categories);
            $this->assertNotEmpty($doc->tags);
            $this->assertNotEmpty($doc->metadataValues);
        }

        // 1 count query + 1 select documents query + 1 select categories + 1 select tags + 1 select metadataValues + 1 select definitions + 1 select folder <= 10 queries
        $this->assertLessThanOrEqual(10, $queryCount);
    }
}
