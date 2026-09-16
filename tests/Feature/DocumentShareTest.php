<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentShare;
use App\Models\Group;
use App\Models\Organization;
use App\Models\User;
use App\Services\AccessControlService;
use App\Services\DocumentShareService;
use App\Services\SearchService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DocumentShareTest extends TestCase
{
    use RefreshDatabase;

    protected DocumentShareService $shareService;

    protected AccessControlService $aclService;

    protected SearchService $searchService;

    protected Organization $orgA;

    protected Organization $orgB;

    protected User $managerA;

    protected User $userA1;

    protected User $userA2;

    protected User $userB;

    protected User $superAdmin;

    protected Group $groupA;

    protected Group $groupB;

    protected Document $documentA;

    protected Document $documentB;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');

        $this->shareService = app(DocumentShareService::class);
        $this->aclService = app(AccessControlService::class);
        $this->searchService = app(SearchService::class);

        $this->orgA = Organization::factory()->create();
        $this->orgB = Organization::factory()->create();

        $this->managerA = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->userA1 = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->userA2 = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->userB = User::factory()->create(['organization_id' => $this->orgB->id]);

        $this->groupA = Group::factory()->create(['organization_id' => $this->orgA->id]);
        $this->groupB = Group::factory()->create(['organization_id' => $this->orgB->id]);

        // Permissions
        $perms = [
            'documents.view', 'documents.download', 'documents.create', 'documents.update', 'documents.delete', 'documents.share',
        ];
        foreach ($perms as $p) {
            Permission::findOrCreate($p, 'web');
        }

        // Roles Org A
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $managerRoleA = Role::create(['name' => 'manager_a', 'guard_name' => 'web']);
        $managerRoleA->givePermissionTo($perms);
        $this->managerA->assignRole($managerRoleA);

        $userRoleA = Role::create(['name' => 'user_a', 'guard_name' => 'web']);
        $userRoleA->givePermissionTo(['documents.view', 'documents.download']);
        $this->userA1->assignRole($userRoleA);
        $this->userA2->assignRole($userRoleA);

        // Roles Org B
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgB->id);
        $userRoleB = Role::create(['name' => 'user_b', 'guard_name' => 'web']);
        $userRoleB->givePermissionTo(['documents.view', 'documents.download']);
        $this->userB->assignRole($userRoleB);

        // Super Admin
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $superAdminRole = Role::findOrCreate('super-admin', 'web');
        $this->superAdmin = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->superAdmin->assignRole($superAdminRole);

        // Create documents
        $pathA = "organizations/{$this->orgA->id}/documents/test_a.pdf";
        Storage::disk('private')->put($pathA, '%PDF-1.4 Org A document');
        $this->documentA = Document::factory()->create([
            'organization_id' => $this->orgA->id,
            'uploaded_by' => $this->managerA->id,
            'extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'storage_disk' => 'private',
            'storage_path' => $pathA,
            'file_name' => 'test_a.pdf',
            'status' => 'active',
        ]);

        $pathB = "organizations/{$this->orgB->id}/documents/test_b.pdf";
        Storage::disk('private')->put($pathB, '%PDF-1.4 Org B document');
        $this->documentB = Document::factory()->create([
            'organization_id' => $this->orgB->id,
            'uploaded_by' => $this->userB->id,
            'extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'storage_disk' => 'private',
            'storage_path' => $pathB,
            'file_name' => 'test_b.pdf',
            'status' => 'active',
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
    }

    // ==========================================
    // 1. PARTAGE AVEC UN UTILISATEUR
    // ==========================================

    public function test_manager_can_share_document_with_user_view_permission(): void
    {
        $response = $this->actingAs($this->managerA)->postJson(route('documents.shares.user.store', $this->documentA), [
            'user_id' => $this->userA1->id,
            'permission' => 'view',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('document_shares', [
            'document_id' => $this->documentA->id,
            'user_id' => $this->userA1->id,
            'permission' => 'view',
            'shared_by' => $this->managerA->id,
            'revoked_at' => null,
        ]);
    }

    public function test_manager_can_share_document_with_user_download_permission(): void
    {
        $response = $this->actingAs($this->managerA)->postJson(route('documents.shares.user.store', $this->documentA), [
            'user_id' => $this->userA1->id,
            'permission' => 'download',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('document_shares', [
            'document_id' => $this->documentA->id,
            'user_id' => $this->userA1->id,
            'permission' => 'download',
        ]);
    }

    public function test_manager_can_share_document_with_expiration(): void
    {
        $futureDate = Carbon::now()->addDays(5)->toDateTimeString();

        $response = $this->actingAs($this->managerA)->postJson(route('documents.shares.user.store', $this->documentA), [
            'user_id' => $this->userA1->id,
            'permission' => 'view',
            'expires_at' => $futureDate,
        ]);

        $response->assertCreated();
        $share = DocumentShare::where('user_id', $this->userA1->id)->first();
        $this->assertNotNull($share->expires_at);
        $this->assertTrue($share->isActive());
    }

    public function test_share_with_user_from_different_organization_is_rejected(): void
    {
        $response = $this->actingAs($this->managerA)->postJson(route('documents.shares.user.store', $this->documentA), [
            'user_id' => $this->userB->id,
            'permission' => 'view',
        ]);

        $response->assertForbidden();
    }

    public function test_share_document_of_different_organization_is_rejected(): void
    {
        $response = $this->actingAs($this->managerA)->postJson(route('documents.shares.user.store', $this->documentB), [
            'user_id' => $this->userA1->id,
            'permission' => 'view',
        ]);

        $response->assertForbidden();
    }

    public function test_user_without_share_permission_cannot_share(): void
    {
        // userA1 does not have documents.share
        $response = $this->actingAs($this->userA1)->postJson(route('documents.shares.user.store', $this->documentA), [
            'user_id' => $this->userA2->id,
            'permission' => 'view',
        ]);

        $response->assertForbidden();
    }

    public function test_share_soft_deleted_document_is_rejected(): void
    {
        $this->documentA->delete();

        $response = $this->actingAs($this->managerA)->postJson(route('documents.shares.user.store', $this->documentA), [
            'user_id' => $this->userA1->id,
            'permission' => 'view',
        ]);

        $response->assertNotFound();
    }

    // ==========================================
    // 2. PARTAGE AVEC UN GROUPE
    // ==========================================

    public function test_manager_can_share_document_with_group(): void
    {
        $response = $this->actingAs($this->managerA)->postJson(route('documents.shares.group.store', $this->documentA), [
            'group_id' => $this->groupA->id,
            'permission' => 'download',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('document_shares', [
            'document_id' => $this->documentA->id,
            'group_id' => $this->groupA->id,
            'user_id' => null,
            'permission' => 'download',
        ]);
    }

    public function test_share_with_group_from_different_organization_is_rejected(): void
    {
        $response = $this->actingAs($this->managerA)->postJson(route('documents.shares.group.store', $this->documentA), [
            'group_id' => $this->groupB->id,
            'permission' => 'view',
        ]);

        $response->assertForbidden();
    }

    public function test_user_without_share_permission_cannot_share_with_group(): void
    {
        $response = $this->actingAs($this->userA1)->postJson(route('documents.shares.group.store', $this->documentA), [
            'group_id' => $this->groupA->id,
            'permission' => 'view',
        ]);

        $response->assertForbidden();
    }

    // ==========================================
    // 3. DOUBLONS & IDEMPOTENCE
    // ==========================================

    public function test_duplicate_user_share_updates_existing_share_without_duplication(): void
    {
        $this->shareService->shareWithUser($this->managerA, $this->documentA, $this->userA1, 'view');
        $this->assertEquals(1, DocumentShare::where('document_id', $this->documentA->id)->where('user_id', $this->userA1->id)->count());

        // Update to download
        $this->shareService->shareWithUser($this->managerA, $this->documentA, $this->userA1, 'download');

        $this->assertEquals(1, DocumentShare::where('document_id', $this->documentA->id)->where('user_id', $this->userA1->id)->count());
        $share = DocumentShare::where('document_id', $this->documentA->id)->where('user_id', $this->userA1->id)->first();
        $this->assertEquals('download', $share->permission);
    }

    public function test_duplicate_group_share_updates_existing_share_idempotently(): void
    {
        $this->shareService->shareWithGroup($this->managerA, $this->documentA, $this->groupA, 'view');
        $this->assertEquals(1, DocumentShare::where('document_id', $this->documentA->id)->where('group_id', $this->groupA->id)->count());

        $this->shareService->shareWithGroup($this->managerA, $this->documentA, $this->groupA, 'download');
        $this->assertEquals(1, DocumentShare::where('document_id', $this->documentA->id)->where('group_id', $this->groupA->id)->count());
        $this->assertEquals('download', DocumentShare::first()->permission);
    }

    // ==========================================
    // 4. RÉVOCATION
    // ==========================================

    public function test_manager_can_revoke_document_share(): void
    {
        $share = $this->shareService->shareWithUser($this->managerA, $this->documentA, $this->userA1, 'view');

        $response = $this->actingAs($this->managerA)->deleteJson(route('documents.shares.destroy', [
            'document' => $this->documentA,
            'share' => $share,
        ]));

        $response->assertOk();
        $share->refresh();
        $this->assertNotNull($share->revoked_at);
        $this->assertEquals($this->managerA->id, $share->revoked_by);
        $this->assertFalse($share->isActive());
    }

    public function test_user_without_share_permission_cannot_revoke(): void
    {
        $share = $this->shareService->shareWithUser($this->managerA, $this->documentA, $this->userA1, 'view');

        $response = $this->actingAs($this->userA2)->deleteJson(route('documents.shares.destroy', [
            'document' => $this->documentA,
            'share' => $share,
        ]));

        $response->assertForbidden();
    }

    public function test_cannot_revoke_share_of_another_organization(): void
    {
        $shareB = DocumentShare::create([
            'organization_id' => $this->orgB->id,
            'document_id' => $this->documentB->id,
            'user_id' => $this->userB->id,
            'permission' => 'view',
            'shared_by' => $this->userB->id,
        ]);

        $response = $this->actingAs($this->managerA)->deleteJson(route('documents.shares.destroy', [
            'document' => $this->documentB,
            'share' => $shareB,
        ]));

        $response->assertForbidden();
    }

    // ==========================================
    // 5. ACCÈS VIEW VS DOWNLOAD & EXPIRATION
    // ==========================================

    public function test_user_with_view_share_can_preview_document(): void
    {
        $this->shareService->shareWithUser($this->managerA, $this->documentA, $this->userA1, 'view');

        $response = $this->actingAs($this->userA1)->get(route('documents.preview', $this->documentA));
        $response->assertOk();
    }

    public function test_user_with_view_share_cannot_download_document(): void
    {
        $this->shareService->shareWithUser($this->managerA, $this->documentA, $this->userA1, 'view');

        // DocumentPolicy::download should deny
        $this->assertFalse($this->shareService->canDownloadViaShare($this->userA1, $this->documentA));
        $this->assertFalse($this->aclService->canAccessDocument($this->userA1, $this->documentA, 'download'));
    }

    public function test_user_with_download_share_can_preview_and_download(): void
    {
        $this->shareService->shareWithUser($this->managerA, $this->documentA, $this->userA1, 'download');

        $previewResponse = $this->actingAs($this->userA1)->get(route('documents.preview', $this->documentA));
        $previewResponse->assertOk();

        $this->assertTrue($this->shareService->canDownloadViaShare($this->userA1, $this->documentA));
        $this->assertTrue($this->aclService->canAccessDocument($this->userA1, $this->documentA, 'download'));
        $this->assertTrue(Gate::forUser($this->userA1)->allows('download', $this->documentA));
    }

    public function test_expired_user_share_denies_view_and_download(): void
    {
        // Expired yesterday
        $this->shareService->shareWithUser(
            $this->managerA,
            $this->documentA,
            $this->userA1,
            'download',
            Carbon::now()->subDay()
        );

        $this->assertFalse($this->shareService->canViewViaShare($this->userA1, $this->documentA));
        $this->assertFalse($this->shareService->canDownloadViaShare($this->userA1, $this->documentA));
        $this->assertFalse($this->aclService->canAccessDocument($this->userA1, $this->documentA, 'view'));
    }

    public function test_revoked_share_denies_view_and_download(): void
    {
        $share = $this->shareService->shareWithUser($this->managerA, $this->documentA, $this->userA1, 'download');
        $this->shareService->revoke($this->managerA, $share);

        $this->assertFalse($this->shareService->canViewViaShare($this->userA1, $this->documentA));
        $this->assertFalse($this->shareService->canDownloadViaShare($this->userA1, $this->documentA));
    }

    public function test_group_share_grants_access_to_group_members(): void
    {
        $this->userA1->groups()->attach($this->groupA->id);

        $this->shareService->shareWithGroup($this->managerA, $this->documentA, $this->groupA, 'download');

        $this->assertTrue($this->shareService->canViewViaShare($this->userA1, $this->documentA));
        $this->assertTrue($this->shareService->canDownloadViaShare($this->userA1, $this->documentA));
        $this->assertTrue($this->aclService->canAccessDocument($this->userA1, $this->documentA, 'view'));
        $this->assertTrue($this->aclService->canAccessDocument($this->userA1, $this->documentA, 'download'));
    }

    public function test_group_share_expired_denies_access_to_group_members(): void
    {
        $this->userA1->groups()->attach($this->groupA->id);

        $this->shareService->shareWithGroup(
            $this->managerA,
            $this->documentA,
            $this->groupA,
            'download',
            Carbon::now()->subHour()
        );

        $this->assertFalse($this->shareService->canViewViaShare($this->userA1, $this->documentA));
        $this->assertFalse($this->shareService->canDownloadViaShare($this->userA1, $this->documentA));
    }

    // ==========================================
    // 6. RECHERCHE DOCUMENTAIRE (SearchService)
    // ==========================================

    public function test_shared_document_becomes_visible_in_search(): void
    {
        // Initially, userA1 has no direct ACL on documentA
        $resultsBefore = $this->searchService->search($this->userA1, ['q' => 'test_a']);
        $this->assertCount(0, $resultsBefore);

        // Share document with userA1
        $this->shareService->shareWithUser($this->managerA, $this->documentA, $this->userA1, 'view');

        $resultsAfter = $this->searchService->search($this->userA1, ['q' => 'test_a']);
        $this->assertCount(1, $resultsAfter);
        $this->assertEquals($this->documentA->id, $resultsAfter->first()->id);
    }

    public function test_expired_shared_document_disappears_from_search(): void
    {
        $this->shareService->shareWithUser(
            $this->managerA,
            $this->documentA,
            $this->userA1,
            'view',
            Carbon::now()->subMinute()
        );

        $results = $this->searchService->search($this->userA1, ['q' => 'test_a']);
        $this->assertCount(0, $results);
    }

    public function test_revoked_shared_document_disappears_from_search(): void
    {
        $share = $this->shareService->shareWithUser($this->managerA, $this->documentA, $this->userA1, 'view');
        $this->assertCount(1, $this->searchService->search($this->userA1, ['q' => 'test_a']));

        $this->shareService->revoke($this->managerA, $share);

        $results = $this->searchService->search($this->userA1, ['q' => 'test_a']);
        $this->assertCount(0, $results);
    }

    public function test_group_shared_document_visible_in_search_for_group_members(): void
    {
        $this->userA1->groups()->attach($this->groupA->id);
        $this->shareService->shareWithGroup($this->managerA, $this->documentA, $this->groupA, 'view');

        $results = $this->searchService->search($this->userA1, ['q' => 'test_a']);
        $this->assertCount(1, $results);
        $this->assertEquals($this->documentA->id, $results->first()->id);
    }

    // ==========================================
    // 7. ANTI-LEAK & SÉCURITÉ MULTI-TENANT
    // ==========================================

    public function test_anti_leak_listing_shares_of_other_tenant_document_is_forbidden(): void
    {
        $response = $this->actingAs($this->managerA)->getJson(route('documents.shares.index', $this->documentB));
        $response->assertForbidden();
    }

    public function test_listing_shares_returns_active_shares(): void
    {
        $this->shareService->shareWithUser($this->managerA, $this->documentA, $this->userA1, 'view');
        $this->shareService->shareWithGroup($this->managerA, $this->documentA, $this->groupA, 'download');

        $response = $this->actingAs($this->managerA)->getJson(route('documents.shares.index', $this->documentA));
        $response->assertOk();
        $this->assertCount(2, $response->json());
    }

    public function test_super_admin_can_share_document_within_its_organization(): void
    {
        $response = $this->actingAs($this->superAdmin)->postJson(route('documents.shares.user.store', $this->documentA), [
            'user_id' => $this->userA1->id,
            'permission' => 'download',
        ]);

        $response->assertCreated();
    }

    public function test_super_admin_cannot_share_document_with_user_of_another_organization(): void
    {
        $response = $this->actingAs($this->superAdmin)->postJson(route('documents.shares.user.store', $this->documentA), [
            'user_id' => $this->userB->id,
            'permission' => 'download',
        ]);

        $response->assertForbidden();
    }
}
