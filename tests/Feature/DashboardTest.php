<?php

namespace Tests\Feature;

use App\Enums\WorkflowApproverType;
use App\Enums\WorkflowStatus;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentFavorite;
use App\Models\Folder;
use App\Models\Group;
use App\Models\Organization;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStep;
use App\Notifications\DocumentSharedNotification;
use App\Services\AccessControlService;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $orgA;

    protected Organization $orgB;

    protected User $userA;

    protected User $userB;

    protected User $superAdmin;

    protected AccessControlService $aclService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aclService = app(AccessControlService::class);

        $this->orgA = Organization::factory()->create(['name' => 'Organisation A']);
        $this->orgB = Organization::factory()->create(['name' => 'Organisation B']);

        $this->userA = User::factory()->create([
            'organization_id' => $this->orgA->id,
            'first_name' => 'Alice',
            'last_name' => 'Martin',
            'email' => 'alice@orga.test',
        ]);

        $this->userB = User::factory()->create([
            'organization_id' => $this->orgB->id,
            'first_name' => 'Bob',
            'last_name' => 'Durand',
            'email' => 'bob@orgb.test',
        ]);

        // Permissions for Org A
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $roleA = Role::findOrCreate('admin', 'web');
        $permissions = [
            'documents.view', 'documents.create', 'documents.update', 'documents.delete',
            'folders.view', 'folders.create', 'workflows.view',
        ];
        foreach ($permissions as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $roleA->syncPermissions($permissions);
        $this->userA->assignRole($roleA);

        // Permissions for Org B
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgB->id);
        $roleB = Role::findOrCreate('admin', 'web');
        foreach ($permissions as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $roleB->syncPermissions($permissions);
        $this->userB->assignRole($roleB);

        // Super Admin
        $this->superAdmin = User::factory()->create([
            'organization_id' => $this->orgA->id,
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'super@ged.test',
        ]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $superRole = Role::findOrCreate('super-admin', 'web');
        $this->superAdmin->assignRole($superRole);
    }

    protected function createAccessibleDocument(User $user, array $attributes = []): Document
    {
        $doc = Document::factory()->create(array_merge([
            'organization_id' => $user->organization_id,
            'uploaded_by' => $user->id,
            'status' => 'active',
        ], $attributes));

        $this->aclService->grantDocumentPermission($doc, $user, 'view');

        return $doc;
    }

    /*
    |--------------------------------------------------------------------------
    | 1. Accès & Authentification
    |--------------------------------------------------------------------------
    */

    public function test_guest_is_redirected_or_unauthorized(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');

        $jsonResponse = $this->getJson('/dashboard');
        $jsonResponse->assertStatus(401);
    }

    public function test_authenticated_user_can_access_dashboard_json(): void
    {
        $response = $this->actingAs($this->userA)->getJson('/dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'user' => ['id', 'first_name', 'last_name', 'full_name', 'email', 'role'],
                'organization' => ['id', 'name', 'storage_limit', 'storage_limit_formatted'],
                'period',
                'statistics' => [
                    'documents_count',
                    'documents_archived_count',
                    'documents_trashed_count',
                    'folders_count',
                    'favorites_count',
                    'workflows_pending_count',
                    'notifications_unread_count',
                    'storage_used',
                    'storage_used_formatted',
                ],
                'recent_documents',
                'favorites',
                'workflows' => ['pending_my_action', 'pending_count', 'in_progress_count'],
                'notifications' => ['unread_count', 'recent'],
                'recent_activity',
                'charts' => ['by_type', 'by_status', 'timeline'],
            ]);

        $this->assertSame('Alice Martin', $response->json('user.full_name'));
        $this->assertSame('Organisation A', $response->json('organization.name'));
    }

    public function test_authenticated_user_can_access_dashboard_view(): void
    {
        $response = $this->actingAs($this->userA)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Bonjour, Alice');
        $response->assertSee('Organisation A');
    }

    /*
    |--------------------------------------------------------------------------
    | 2. Tenant Isolation
    |--------------------------------------------------------------------------
    */

    public function test_tenant_isolation_in_statistics_and_lists(): void
    {
        // 3 documents in Org A
        for ($i = 0; $i < 3; $i++) {
            $this->createAccessibleDocument($this->userA, ['size' => 1024]);
        }

        // 5 documents in Org B
        for ($i = 0; $i < 5; $i++) {
            $this->createAccessibleDocument($this->userB, ['size' => 2048]);
        }

        // Folders
        Folder::factory()->count(2)->create(['organization_id' => $this->orgA->id]);
        Folder::factory()->count(4)->create(['organization_id' => $this->orgB->id]);

        // Dashboard User A
        $responseA = $this->actingAs($this->userA)->getJson('/dashboard');
        $responseA->assertOk();
        $this->assertSame(3, $responseA->json('statistics.documents_count'));
        $this->assertSame(2, $responseA->json('statistics.folders_count'));

        // Dashboard User B
        $responseB = $this->actingAs($this->userB)->getJson('/dashboard');
        $responseB->assertOk();
        $this->assertSame(5, $responseB->json('statistics.documents_count'));
        $this->assertSame(4, $responseB->json('statistics.folders_count'));
    }

    /*
    |--------------------------------------------------------------------------
    | 3. Permissions & ACL
    |--------------------------------------------------------------------------
    */

    public function test_documents_restricted_by_acl_are_not_counted_for_user_without_access(): void
    {
        $otherUser = User::factory()->create(['organization_id' => $this->orgA->id]);

        // Doc 1: accessible to userA
        $this->createAccessibleDocument($this->userA);

        // Doc 2: restricted with an ACL only to $otherUser
        $this->createAccessibleDocument($otherUser);

        // User A should only see 1 document because Doc 2 has an ACL excluding User A
        $response = $this->actingAs($this->userA)->getJson('/dashboard');
        $response->assertOk();
        $this->assertSame(1, $response->json('statistics.documents_count'));
    }

    /*
    |--------------------------------------------------------------------------
    | 4. Favoris
    |--------------------------------------------------------------------------
    */

    public function test_favorites_toggle_and_dashboard_display(): void
    {
        $doc1 = $this->createAccessibleDocument($this->userA, [
            'name' => 'Contrat_Important.pdf',
        ]);

        $doc2 = $this->createAccessibleDocument($this->userA, [
            'name' => 'Facture_2026.pdf',
        ]);

        // Toggle add favorite for doc1
        $favResponse = $this->actingAs($this->userA)->postJson("/documents/{$doc1->id}/favorite");
        $favResponse->assertOk()
            ->assertJson(['is_favorite' => true, 'favorites_count' => 1]);

        $this->assertDatabaseHas('document_favorites', [
            'organization_id' => $this->orgA->id,
            'user_id' => $this->userA->id,
            'document_id' => $doc1->id,
        ]);

        // Verify dashboard reflects favorite
        $dashResponse = $this->actingAs($this->userA)->getJson('/dashboard');
        $dashResponse->assertOk();
        $this->assertSame(1, $dashResponse->json('statistics.favorites_count'));
        $this->assertCount(1, $dashResponse->json('favorites'));
        $this->assertSame('Contrat_Important.pdf', $dashResponse->json('favorites.0.name'));

        // User B cannot see User A's favorite
        $dashResponseB = $this->actingAs($this->userB)->getJson('/dashboard');
        $this->assertSame(0, $dashResponseB->json('statistics.favorites_count'));
        $this->assertCount(0, $dashResponseB->json('favorites'));

        // Toggle remove favorite
        $unfavResponse = $this->actingAs($this->userA)->postJson("/documents/{$doc1->id}/favorite");
        $unfavResponse->assertOk()
            ->assertJson(['is_favorite' => false, 'favorites_count' => 0]);
        $this->assertDatabaseMissing('document_favorites', ['document_id' => $doc1->id]);
    }

    public function test_deleted_or_revoked_favorite_is_not_displayed(): void
    {
        $doc = $this->createAccessibleDocument($this->userA);

        DocumentFavorite::create([
            'organization_id' => $this->orgA->id,
            'user_id' => $this->userA->id,
            'document_id' => $doc->id,
        ]);

        // Soft delete the document
        $doc->delete();

        $response = $this->actingAs($this->userA)->getJson('/dashboard');
        $response->assertOk();
        $this->assertSame(0, $response->json('statistics.favorites_count'));
        $this->assertCount(0, $response->json('favorites'));
    }

    /*
    |--------------------------------------------------------------------------
    | 5. Documents Récents
    |--------------------------------------------------------------------------
    */

    public function test_recent_documents_via_audit_log_and_fallback(): void
    {
        $doc1 = $this->createAccessibleDocument($this->userA, [
            'name' => 'Fichier_A.pdf',
        ]);

        $doc2 = $this->createAccessibleDocument($this->userA, [
            'name' => 'Fichier_B.pdf',
        ]);

        // Simulate view of Doc 2 via AuditLog
        app(AuditService::class)->log(
            action: 'document.previewed',
            auditable: $doc2,
            user: $this->userA,
            organizationId: $this->orgA->id,
            description: "Document '{$doc2->name}' previewed."
        );

        $response = $this->actingAs($this->userA)->getJson('/dashboard');
        $response->assertOk();

        $recentDocs = $response->json('recent_documents');
        $this->assertNotEmpty($recentDocs);
        // Doc 2 was previewed, so it should appear first
        $this->assertSame($doc2->id, $recentDocs[0]['id']);
    }

    /*
    |--------------------------------------------------------------------------
    | 6. Workflows
    |--------------------------------------------------------------------------
    */

    public function test_workflows_pending_my_action_displayed_only_for_assigned_approver(): void
    {
        $doc = $this->createAccessibleDocument($this->userA, [
            'name' => 'Contrat_A_Valider.pdf',
        ]);

        $workflow = Workflow::create([
            'organization_id' => $this->orgA->id,
            'name' => 'Validation Contrat',
            'created_by' => $this->userA->id,
        ]);

        // Step 1 assigns User A
        $step1 = WorkflowStep::create([
            'organization_id' => $this->orgA->id,
            'workflow_id' => $workflow->id,
            'name' => 'Validation Direction',
            'position' => 1,
            'approver_type' => WorkflowApproverType::User,
            'approver_user_id' => $this->userA->id,
            'is_required' => true,
        ]);

        $instance = WorkflowInstance::create([
            'organization_id' => $this->orgA->id,
            'workflow_id' => $workflow->id,
            'document_id' => $doc->id,
            'started_by' => $this->userA->id,
            'status' => WorkflowStatus::InProgress,
            'current_step_id' => $step1->id,
            'started_at' => Carbon::now(),
        ]);

        // User A dashboard should show 1 pending workflow requiring action
        $responseA = $this->actingAs($this->userA)->getJson('/dashboard');
        $responseA->assertOk();
        $this->assertSame(1, $responseA->json('statistics.workflows_pending_count'));
        $this->assertCount(1, $responseA->json('workflows.pending_my_action'));
        $this->assertSame('Contrat_A_Valider.pdf', $responseA->json('workflows.pending_my_action.0.document_name'));

        // User B in Org B should see 0
        $responseB = $this->actingAs($this->userB)->getJson('/dashboard');
        $responseB->assertOk();
        $this->assertSame(0, $responseB->json('statistics.workflows_pending_count'));
        $this->assertCount(0, $responseB->json('workflows.pending_my_action'));
    }

    public function test_workflow_assigned_to_group_appears_for_group_members(): void
    {
        $group = Group::factory()->create(['organization_id' => $this->orgA->id]);
        $this->userA->groups()->attach($group->id);

        $doc = $this->createAccessibleDocument($this->userA);

        $workflow = Workflow::create([
            'organization_id' => $this->orgA->id,
            'name' => 'Workflow Groupe',
            'created_by' => $this->userA->id,
        ]);

        $step = WorkflowStep::create([
            'organization_id' => $this->orgA->id,
            'workflow_id' => $workflow->id,
            'name' => 'Validation Groupe',
            'position' => 1,
            'approver_type' => WorkflowApproverType::Group,
            'approver_group_id' => $group->id,
            'is_required' => true,
        ]);

        WorkflowInstance::create([
            'organization_id' => $this->orgA->id,
            'workflow_id' => $workflow->id,
            'document_id' => $doc->id,
            'started_by' => $this->userA->id,
            'status' => WorkflowStatus::InProgress,
            'current_step_id' => $step->id,
            'started_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->userA)->getJson('/dashboard');
        $response->assertOk();
        $this->assertSame(1, $response->json('statistics.workflows_pending_count'));
    }

    /*
    |--------------------------------------------------------------------------
    | 7. Notifications & Audit
    |--------------------------------------------------------------------------
    */

    public function test_notifications_and_audit_activity_are_isolated(): void
    {
        $docA = $this->createAccessibleDocument($this->userA);
        $docB = $this->createAccessibleDocument($this->userB);

        // Notification for User A
        $this->userA->notify(new DocumentSharedNotification($docA, $this->userA, 'view'));

        // Audit Log in Org A
        app(AuditService::class)->log(
            action: 'document.created',
            auditable: $docA,
            user: $this->userA,
            organizationId: $this->orgA->id,
            description: "Document '{$docA->name}' créé."
        );

        // Audit Log in Org B
        app(AuditService::class)->log(
            action: 'document.created',
            auditable: $docB,
            user: $this->userB,
            organizationId: $this->orgB->id,
            description: "Document '{$docB->name}' créé."
        );

        $responseA = $this->actingAs($this->userA)->getJson('/dashboard');
        $responseA->assertOk();

        // 1 unread notification for User A
        $this->assertSame(1, $responseA->json('statistics.notifications_unread_count'));
        $this->assertCount(1, $responseA->json('notifications.recent'));

        // Activity contains Org A logs, but never Org B logs
        $activityA = $responseA->json('recent_activity');
        $this->assertNotEmpty($activityA);
        foreach ($activityA as $act) {
            $this->assertStringNotContainsString($docB->name, $act['description']);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 8. Cycle de vie : Actif, Archivé, Corbeille
    |--------------------------------------------------------------------------
    */

    public function test_document_lifecycle_counts_are_accurate(): void
    {
        // 2 active
        $this->createAccessibleDocument($this->userA);
        $this->createAccessibleDocument($this->userA);

        // 1 archived
        $this->createAccessibleDocument($this->userA, [
            'status' => 'archived',
        ]);

        // 1 in trash
        $trashDoc = $this->createAccessibleDocument($this->userA);
        $trashDoc->delete();

        $response = $this->actingAs($this->userA)->getJson('/dashboard');
        $response->assertOk();

        $this->assertSame(2, $response->json('statistics.documents_count'));
        $this->assertSame(1, $response->json('statistics.documents_archived_count'));
        $this->assertSame(1, $response->json('statistics.documents_trashed_count'));
        $this->assertSame(2, $response->json('charts.by_status.active'));
        $this->assertSame(1, $response->json('charts.by_status.archived'));
        $this->assertSame(1, $response->json('charts.by_status.trash'));
    }

    /*
    |--------------------------------------------------------------------------
    | 9. Périodes & Filtrage
    |--------------------------------------------------------------------------
    */

    public function test_period_filtering_accepts_valid_and_defaults_invalid(): void
    {
        $res7d = $this->actingAs($this->userA)->getJson('/dashboard?period=7d');
        $res7d->assertOk();
        $this->assertSame('7d', $res7d->json('period'));

        $res90d = $this->actingAs($this->userA)->getJson('/dashboard?period=90d');
        $res90d->assertOk();
        $this->assertSame('90d', $res90d->json('period'));

        $res12m = $this->actingAs($this->userA)->getJson('/dashboard?period=12m');
        $res12m->assertOk();
        $this->assertSame('12m', $res12m->json('period'));

        // Invalid period falls back to 30d
        $resInvalid = $this->actingAs($this->userA)->getJson('/dashboard?period=invalid_malicious');
        $resInvalid->assertOk();
        $this->assertSame('30d', $resInvalid->json('period'));
    }

    /*
    |--------------------------------------------------------------------------
    | 10. Sécurité Anti-IDOR & Super-Admin
    |--------------------------------------------------------------------------
    */

    public function test_user_cannot_favorite_document_from_other_organization(): void
    {
        $docB = Document::factory()->create([
            'organization_id' => $this->orgB->id,
            'uploaded_by' => $this->userB->id,
        ]);

        $response = $this->actingAs($this->userA)->postJson("/documents/{$docB->id}/favorite");
        $response->assertForbidden();
    }

    public function test_super_admin_can_access_dashboard(): void
    {
        Document::factory()->count(2)->create([
            'organization_id' => $this->orgA->id,
            'uploaded_by' => $this->userA->id,
        ]);
        Document::factory()->count(3)->create([
            'organization_id' => $this->orgB->id,
            'uploaded_by' => $this->userB->id,
        ]);

        $response = $this->actingAs($this->superAdmin)->getJson('/dashboard');
        $response->assertOk();

        // Super-admin sees global total (2 + 3 = 5 documents)
        $this->assertSame(5, $response->json('statistics.documents_count'));
    }

    public function test_empty_dashboard_state_returns_zeroes_and_empty_lists(): void
    {
        $emptyOrg = Organization::factory()->create();
        $emptyUser = User::factory()->create(['organization_id' => $emptyOrg->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($emptyOrg->id);
        $role = Role::findOrCreate('utilisateur', 'web');
        $role->givePermissionTo('documents.view', 'folders.view');
        $emptyUser->assignRole($role);

        $response = $this->actingAs($emptyUser)->getJson('/dashboard');
        $response->assertOk();

        $this->assertSame(0, $response->json('statistics.documents_count'));
        $this->assertSame(0, $response->json('statistics.folders_count'));
        $this->assertSame(0, $response->json('statistics.favorites_count'));
        $this->assertSame(0, $response->json('statistics.workflows_pending_count'));
        $this->assertSame(0, $response->json('statistics.notifications_unread_count'));
        $this->assertSame('0 o', $response->json('statistics.storage_used_formatted'));
        $this->assertEmpty($response->json('recent_documents'));
        $this->assertEmpty($response->json('favorites'));
        $this->assertEmpty($response->json('workflows.pending_my_action'));
    }
}
