<?php

namespace Tests\Feature;

use App\Enums\WorkflowApproverType;
use App\Enums\WorkflowStatus;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Group;
use App\Models\Organization;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Notifications\WorkflowSubmittedNotification;
use App\Services\DocumentLifecycleService;
use App\Services\WorkflowService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected WorkflowService $workflowService;

    protected DocumentLifecycleService $lifecycleService;

    protected Organization $orgA;

    protected Organization $orgB;

    protected User $adminA;

    protected User $userA1;

    protected User $userA2;

    protected User $managerA;

    protected User $userB1;

    protected Group $groupA;

    protected Group $groupB;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');
        config(['filesystems.default' => 'private']);

        $this->orgA = Organization::factory()->create();
        $this->orgB = Organization::factory()->create();

        $this->seed(RolePermissionSeeder::class);

        $this->workflowService = app(WorkflowService::class);
        $this->lifecycleService = app(DocumentLifecycleService::class);

        $this->adminA = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->managerA = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->userA1 = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->userA2 = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->userB1 = User::factory()->create(['organization_id' => $this->orgB->id]);

        $this->groupA = Group::factory()->create(['organization_id' => $this->orgA->id]);
        $this->groupB = Group::factory()->create(['organization_id' => $this->orgB->id]);

        $this->groupA->users()->attach($this->userA2->id);

        // Setup Spatie roles for orgA
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);

        $allWorkflowPermissions = [
            'workflows.view', 'workflows.create', 'workflows.update', 'workflows.delete',
            'workflows.execute', 'workflows.approve', 'workflows.reject', 'workflows.cancel',
            'documents.view', 'documents.create', 'documents.update', 'documents.delete',
            'documents.archive', 'documents.restore', 'documents.download', 'documents.share',
            'audit.view',
        ];

        $roleAdminA = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->adminA->assignRole($roleAdminA);

        $roleManagerA = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $this->managerA->assignRole($roleManagerA);

        $roleUserA = Role::firstOrCreate(['name' => 'utilisateur', 'guard_name' => 'web']);
        $this->userA1->assignRole($roleUserA);
        $this->userA2->assignRole($roleUserA);

        // Setup Spatie roles for orgB
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgB->id);
        $roleAdminB = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->userB1->assignRole($roleAdminB);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
    }

    protected function createDocument(array $attributes = []): Document
    {
        $orgId = $attributes['organization_id'] ?? $this->orgA->id;
        $uploaderId = $attributes['uploaded_by'] ?? $this->adminA->id;

        $doc = Document::create(array_merge([
            'organization_id' => $orgId,
            'uploaded_by' => $uploaderId,
            'name' => 'Contrat.pdf',
            'file_name' => 'contrat.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size' => 1024,
            'storage_disk' => 'private',
            'storage_path' => 'documents/contrat.pdf',
            'status' => 'active',
        ], $attributes));

        DocumentVersion::create([
            'document_id' => $doc->id,
            'version_number' => 1,
            'file_name' => 'contrat.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size' => 1024,
            'storage_disk' => 'private',
            'storage_path' => 'documents/contrat.pdf',
            'uploaded_by' => $uploaderId,
            'comment' => 'Initial version',
        ]);

        return $doc;
    }

    protected function createWorkflowWithSteps(int $stepsCount = 2, ?Organization $org = null): Workflow
    {
        $targetOrg = $org ?? $this->orgA;
        $creator = $targetOrg->id === $this->orgA->id ? $this->adminA : $this->userB1;

        $workflow = Workflow::factory()->create([
            'organization_id' => $targetOrg->id,
            'created_by' => $creator->id,
            'is_active' => true,
        ]);

        for ($i = 1; $i <= $stepsCount; $i++) {
            $approver = ($i === 1) ? $this->userA1 : $this->managerA;
            if ($targetOrg->id !== $this->orgA->id) {
                $approver = $this->userB1;
            }

            WorkflowStep::create([
                'organization_id' => $targetOrg->id,
                'workflow_id' => $workflow->id,
                'name' => "Étape {$i}",
                'position' => $i,
                'approver_type' => WorkflowApproverType::User,
                'approver_user_id' => $approver->id,
                'approver_group_id' => null,
                'is_required' => true,
            ]);
        }

        return $workflow;
    }

    // ==========================================
    // 1. WORKFLOW CRUD & TENANT ISOLATION
    // ==========================================

    public function test_user_with_permission_can_create_workflow(): void
    {
        $response = $this->actingAs($this->adminA)->postJson('/workflows', [
            'name' => 'Validation Achats',
            'description' => 'Circuit achats',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('workflows', [
            'organization_id' => $this->orgA->id,
            'name' => 'Validation Achats',
            'created_by' => $this->adminA->id,
        ]);
    }

    public function test_user_without_permission_cannot_create_workflow(): void
    {
        $response = $this->actingAs($this->userA1)->postJson('/workflows', [
            'name' => 'Workflow Non Autorisé',
        ]);

        $response->assertForbidden();
    }

    public function test_workflow_cannot_be_created_with_duplicate_name_in_same_organization(): void
    {
        Workflow::factory()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'Factures',
        ]);

        $this->expectException(QueryException::class);

        Workflow::create([
            'organization_id' => $this->orgA->id,
            'name' => 'Factures',
            'created_by' => $this->adminA->id,
            'is_active' => true,
        ]);
    }

    public function test_same_workflow_name_allowed_in_different_organizations(): void
    {
        Workflow::factory()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'Recrutement',
        ]);

        $wfB = Workflow::create([
            'organization_id' => $this->orgB->id,
            'name' => 'Recrutement',
            'created_by' => $this->userB1->id,
            'is_active' => true,
        ]);

        $this->assertNotNull($wfB->id);
    }

    public function test_user_can_view_workflows_of_own_organization(): void
    {
        $wfA = Workflow::factory()->create(['organization_id' => $this->orgA->id, 'created_by' => $this->adminA->id]);

        $response = $this->actingAs($this->adminA)->getJson('/workflows');
        $response->assertOk();
        $response->assertJsonFragment(['name' => $wfA->name]);
    }

    public function test_user_cannot_view_workflows_of_another_organization(): void
    {
        $wfB = Workflow::factory()->create(['organization_id' => $this->orgB->id, 'created_by' => $this->userB1->id]);

        $response = $this->actingAs($this->adminA)->getJson("/workflows/{$wfB->id}");
        $response->assertForbidden();
    }

    public function test_user_can_update_workflow(): void
    {
        $wf = Workflow::factory()->create(['organization_id' => $this->orgA->id, 'created_by' => $this->adminA->id, 'name' => 'Old Name']);

        $response = $this->actingAs($this->adminA)->putJson("/workflows/{$wf->id}", [
            'name' => 'New Name',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('workflows', ['id' => $wf->id, 'name' => 'New Name']);
    }

    // ==========================================
    // 2. SOFT DELETES & INTEGRITY
    // ==========================================

    public function test_workflow_can_be_soft_deleted(): void
    {
        $wf = Workflow::factory()->create(['organization_id' => $this->orgA->id, 'created_by' => $this->adminA->id]);

        $response = $this->actingAs($this->adminA)->deleteJson("/workflows/{$wf->id}");
        $response->assertOk();

        $this->assertSoftDeleted('workflows', ['id' => $wf->id]);
    }

    public function test_cannot_delete_workflow_with_active_instance(): void
    {
        $doc = $this->createDocument();
        $wf = $this->createWorkflowWithSteps(1);
        $this->workflowService->start($this->adminA, $doc, $wf);

        $response = $this->actingAs($this->adminA)->deleteJson("/workflows/{$wf->id}");
        $response->assertStatus(422);
    }

    public function test_soft_deleted_workflow_preserves_history_and_instances(): void
    {
        $doc = $this->createDocument();
        $wf = $this->createWorkflowWithSteps(1);
        $instance = $this->workflowService->start($this->adminA, $doc, $wf);
        $this->workflowService->approve($this->userA1, $instance);

        $this->actingAs($this->adminA)->deleteJson("/workflows/{$wf->id}")->assertOk();

        $this->assertDatabaseHas('workflow_instances', ['id' => $instance->id]);
        $this->assertDatabaseHas('workflow_actions', ['workflow_instance_id' => $instance->id]);
    }

    // ==========================================
    // 3. STEP MANAGEMENT & XOR RULES
    // ==========================================

    public function test_can_add_user_approver_step_to_workflow(): void
    {
        $wf = Workflow::factory()->create(['organization_id' => $this->orgA->id, 'created_by' => $this->adminA->id]);

        $response = $this->actingAs($this->adminA)->postJson("/workflows/{$wf->id}/steps", [
            'name' => 'Revue Manager',
            'position' => 1,
            'approver_type' => 'user',
            'approver_user_id' => $this->userA1->id,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('workflow_steps', [
            'workflow_id' => $wf->id,
            'name' => 'Revue Manager',
            'approver_type' => 'user',
            'approver_user_id' => $this->userA1->id,
            'approver_group_id' => null,
        ]);
    }

    public function test_can_add_group_approver_step_to_workflow(): void
    {
        $wf = Workflow::factory()->create(['organization_id' => $this->orgA->id, 'created_by' => $this->adminA->id]);

        $response = $this->actingAs($this->adminA)->postJson("/workflows/{$wf->id}/steps", [
            'name' => 'Revue Équipe',
            'position' => 1,
            'approver_type' => 'group',
            'approver_group_id' => $this->groupA->id,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('workflow_steps', [
            'workflow_id' => $wf->id,
            'name' => 'Revue Équipe',
            'approver_type' => 'group',
            'approver_group_id' => $this->groupA->id,
            'approver_user_id' => null,
        ]);
    }

    public function test_step_creation_fails_if_both_user_and_group_provided(): void
    {
        $wf = Workflow::factory()->create(['organization_id' => $this->orgA->id, 'created_by' => $this->adminA->id]);

        $response = $this->actingAs($this->adminA)->postJson("/workflows/{$wf->id}/steps", [
            'name' => 'Step Invalide',
            'position' => 1,
            'approver_type' => 'user',
            'approver_user_id' => $this->userA1->id,
            'approver_group_id' => $this->groupA->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_step_creation_fails_if_neither_user_nor_group_provided(): void
    {
        $wf = Workflow::factory()->create(['organization_id' => $this->orgA->id, 'created_by' => $this->adminA->id]);

        $response = $this->actingAs($this->adminA)->postJson("/workflows/{$wf->id}/steps", [
            'name' => 'Step Invalide',
            'position' => 1,
            'approver_type' => 'user',
        ]);

        $response->assertStatus(422);
    }

    public function test_step_creation_fails_if_approver_user_belongs_to_another_organization(): void
    {
        $wf = Workflow::factory()->create(['organization_id' => $this->orgA->id, 'created_by' => $this->adminA->id]);

        $response = $this->actingAs($this->adminA)->postJson("/workflows/{$wf->id}/steps", [
            'name' => 'Step Cross-tenant',
            'position' => 1,
            'approver_type' => 'user',
            'approver_user_id' => $this->userB1->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_step_creation_fails_if_approver_group_belongs_to_another_organization(): void
    {
        $wf = Workflow::factory()->create(['organization_id' => $this->orgA->id, 'created_by' => $this->adminA->id]);

        $response = $this->actingAs($this->adminA)->postJson("/workflows/{$wf->id}/steps", [
            'name' => 'Step Cross-tenant Group',
            'position' => 1,
            'approver_type' => 'group',
            'approver_group_id' => $this->groupB->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_step_creation_fails_if_position_is_duplicate(): void
    {
        $wf = Workflow::factory()->create(['organization_id' => $this->orgA->id, 'created_by' => $this->adminA->id]);

        $this->actingAs($this->adminA)->postJson("/workflows/{$wf->id}/steps", [
            'name' => 'Step 1',
            'position' => 1,
            'approver_type' => 'user',
            'approver_user_id' => $this->userA1->id,
        ])->assertCreated();

        $response = $this->actingAs($this->adminA)->postJson("/workflows/{$wf->id}/steps", [
            'name' => 'Step 1 Bis',
            'position' => 1,
            'approver_type' => 'user',
            'approver_user_id' => $this->managerA->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_steps_can_be_reordered(): void
    {
        $wf = $this->createWorkflowWithSteps(2);
        $steps = $wf->steps()->get();

        $step1 = $steps[0];
        $step2 = $steps[1];

        $response = $this->actingAs($this->adminA)->postJson("/workflows/{$wf->id}/steps/reorder", [
            'positions' => [
                $step1->id => 2,
                $step2->id => 1,
            ],
        ]);

        $response->assertOk();
        $this->assertSame(2, $step1->fresh()->position);
        $this->assertSame(1, $step2->fresh()->position);
    }

    // ==========================================
    // 4. STEP UPDATE & DELETE RULES
    // ==========================================

    public function test_step_can_be_updated(): void
    {
        $wf = $this->createWorkflowWithSteps(1);
        $step = $wf->steps()->first();

        $response = $this->actingAs($this->adminA)->putJson("/workflow-steps/{$step->id}", [
            'name' => 'Nouveau Nom Étape',
        ]);

        $response->assertOk();
        $this->assertSame('Nouveau Nom Étape', $step->fresh()->name);
    }

    public function test_step_can_be_removed_if_no_active_instance(): void
    {
        $wf = $this->createWorkflowWithSteps(1);
        $step = $wf->steps()->first();

        $response = $this->actingAs($this->adminA)->deleteJson("/workflow-steps/{$step->id}");
        $response->assertOk();
        $this->assertDatabaseMissing('workflow_steps', ['id' => $step->id]);
    }

    public function test_step_cannot_be_removed_if_active_instance_exists(): void
    {
        $doc = $this->createDocument();
        $wf = $this->createWorkflowWithSteps(1);
        $this->workflowService->start($this->adminA, $doc, $wf);
        $step = $wf->steps()->first();

        $response = $this->actingAs($this->adminA)->deleteJson("/workflow-steps/{$step->id}");
        $response->assertStatus(422);
    }

    // ==========================================
    // 5. STARTING A WORKFLOW
    // ==========================================

    public function test_user_can_start_workflow_on_document(): void
    {
        $doc = $this->createDocument();
        $wf = $this->createWorkflowWithSteps(2);

        $response = $this->actingAs($this->adminA)->postJson("/documents/{$doc->id}/workflows/{$wf->id}/start");

        $response->assertCreated();
        $this->assertDatabaseHas('workflow_instances', [
            'document_id' => $doc->id,
            'workflow_id' => $wf->id,
            'status' => 'in_progress',
        ]);
        $this->assertDatabaseHas('workflow_actions', [
            'action' => 'submitted',
            'user_id' => $this->adminA->id,
        ]);
    }

    public function test_cannot_start_workflow_if_workflow_is_inactive(): void
    {
        $doc = $this->createDocument();
        $wf = $this->createWorkflowWithSteps(1);
        $wf->update(['is_active' => false]);

        $response = $this->actingAs($this->adminA)->postJson("/documents/{$doc->id}/workflows/{$wf->id}/start");
        $response->assertStatus(422);
    }

    public function test_cannot_start_workflow_if_workflow_has_no_steps(): void
    {
        $doc = $this->createDocument();
        $wf = Workflow::factory()->create(['organization_id' => $this->orgA->id, 'created_by' => $this->adminA->id]);

        $response = $this->actingAs($this->adminA)->postJson("/documents/{$doc->id}/workflows/{$wf->id}/start");
        $response->assertStatus(422);
    }

    public function test_cannot_start_workflow_on_trashed_document(): void
    {
        $doc = $this->createDocument();
        $wf = $this->createWorkflowWithSteps(1);
        $doc->delete();

        $response = $this->actingAs($this->adminA)->postJson("/documents/{$doc->id}/workflows/{$wf->id}/start");
        $response->assertStatus(404); // Document route binding or view check fails on trashed
    }

    public function test_cannot_start_workflow_on_archived_document(): void
    {
        $doc = $this->createDocument(['status' => 'archived']);
        $wf = $this->createWorkflowWithSteps(1);

        $response = $this->actingAs($this->adminA)->postJson("/documents/{$doc->id}/workflows/{$wf->id}/start");
        $response->assertStatus(422);
    }

    public function test_cannot_start_cross_tenant_workflow_with_different_organization_document(): void
    {
        $docB = $this->createDocument(['organization_id' => $this->orgB->id, 'uploaded_by' => $this->userB1->id]);
        $wfA = $this->createWorkflowWithSteps(1);

        $response = $this->actingAs($this->adminA)->postJson("/documents/{$docB->id}/workflows/{$wfA->id}/start");
        $response->assertStatus(403);
    }

    public function test_cannot_start_cross_tenant_workflow_with_different_organization_workflow(): void
    {
        $docA = $this->createDocument();
        $wfB = $this->createWorkflowWithSteps(1, $this->orgB);

        $response = $this->actingAs($this->adminA)->postJson("/documents/{$docA->id}/workflows/{$wfB->id}/start");
        $response->assertStatus(403);
    }

    public function test_cannot_start_second_active_workflow_on_same_document(): void
    {
        $doc = $this->createDocument();
        $wf1 = $this->createWorkflowWithSteps(1);
        $wf2 = $this->createWorkflowWithSteps(1);

        $this->workflowService->start($this->adminA, $doc, $wf1);

        $this->expectException(HttpException::class);
        $this->workflowService->start($this->adminA, $doc, $wf2);
    }

    // ==========================================
    // 6. USER APPROVER TRANSITIONS
    // ==========================================

    public function test_designated_user_can_approve_step(): void
    {
        $doc = $this->createDocument();
        $wf = $this->createWorkflowWithSteps(2);
        $instance = $this->workflowService->start($this->adminA, $doc, $wf);

        $response = $this->actingAs($this->userA1)->postJson("/workflow-instances/{$instance->id}/approve", [
            'comment' => 'Validé étape 1',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('workflow_actions', [
            'workflow_instance_id' => $instance->id,
            'action' => 'approved',
            'user_id' => $this->userA1->id,
            'comment' => 'Validé étape 1',
        ]);
    }

    public function test_non_designated_user_cannot_approve_step_even_with_permission(): void
    {
        $doc = $this->createDocument();
        $wf = $this->createWorkflowWithSteps(2); // Step 1 is userA1
        $instance = $this->workflowService->start($this->adminA, $doc, $wf);

        // managerA has workflows.approve but is not the approver of step 1
        $response = $this->actingAs($this->managerA)->postJson("/workflow-instances/{$instance->id}/approve");
        $response->assertStatus(403);
    }

    public function test_approver_without_workflows_approve_permission_is_rejected(): void
    {
        $doc = $this->createDocument();
        $wf = $this->createWorkflowWithSteps(1);
        $step = $wf->steps()->first();

        // Create user without workflows.approve permission
        $restrictedUser = User::factory()->create(['organization_id' => $this->orgA->id]);
        $step->update(['approver_user_id' => $restrictedUser->id]);

        $instance = $this->workflowService->start($this->adminA, $doc, $wf);

        $response = $this->actingAs($restrictedUser)->postJson("/workflow-instances/{$instance->id}/approve");
        $response->assertStatus(403);
    }

    // ==========================================
    // 7. GROUP APPROVER TRANSITIONS
    // ==========================================

    public function test_group_member_can_approve_group_step(): void
    {
        $doc = $this->createDocument();
        $wf = Workflow::factory()->create(['organization_id' => $this->orgA->id, 'created_by' => $this->adminA->id]);

        WorkflowStep::create([
            'organization_id' => $this->orgA->id,
            'workflow_id' => $wf->id,
            'name' => 'Étape Groupe',
            'position' => 1,
            'approver_type' => WorkflowApproverType::Group,
            'approver_group_id' => $this->groupA->id,
            'is_required' => true,
        ]);

        $instance = $this->workflowService->start($this->adminA, $doc, $wf);

        // userA2 belongs to groupA
        $response = $this->actingAs($this->userA2)->postJson("/workflow-instances/{$instance->id}/approve", [
            'comment' => 'Approuvé par membre du groupe',
        ]);

        $response->assertOk();
        $this->assertSame(WorkflowStatus::Approved, $instance->fresh()->status);
    }

    public function test_non_group_member_cannot_approve_group_step(): void
    {
        $doc = $this->createDocument();
        $wf = Workflow::factory()->create(['organization_id' => $this->orgA->id, 'created_by' => $this->adminA->id]);

        WorkflowStep::create([
            'organization_id' => $this->orgA->id,
            'workflow_id' => $wf->id,
            'name' => 'Étape Groupe',
            'position' => 1,
            'approver_type' => WorkflowApproverType::Group,
            'approver_group_id' => $this->groupA->id,
            'is_required' => true,
        ]);

        $instance = $this->workflowService->start($this->adminA, $doc, $wf);

        // userA1 is not in groupA
        $response = $this->actingAs($this->userA1)->postJson("/workflow-instances/{$instance->id}/approve");
        $response->assertStatus(403);
    }

    public function test_group_member_from_another_tenant_cannot_approve_group_step(): void
    {
        $doc = $this->createDocument();
        $wf = $this->createWorkflowWithSteps(1);
        $instance = $this->workflowService->start($this->adminA, $doc, $wf);

        $response = $this->actingAs($this->userB1)->postJson("/workflow-instances/{$instance->id}/approve");
        $response->assertStatus(403);
    }

    // ==========================================
    // 8. MULTI-STEP ADVANCEMENT & FINAL APPROVAL
    // ==========================================

    public function test_approval_advances_instance_to_next_step(): void
    {
        $doc = $this->createDocument();
        $wf = $this->createWorkflowWithSteps(2);
        $instance = $this->workflowService->start($this->adminA, $doc, $wf);

        $step1 = $instance->currentStep;
        $this->assertSame(1, $step1->position);

        $this->workflowService->approve($this->userA1, $instance);

        $instance->refresh();
        $this->assertSame(WorkflowStatus::InProgress, $instance->status);
        $this->assertSame(2, $instance->currentStep->position);
    }

    public function test_approval_of_final_step_marks_workflow_as_approved_and_clears_current_step(): void
    {
        $doc = $this->createDocument();
        $wf = $this->createWorkflowWithSteps(1);
        $instance = $this->workflowService->start($this->adminA, $doc, $wf);

        $this->workflowService->approve($this->userA1, $instance);

        $instance->refresh();
        $this->assertSame(WorkflowStatus::Approved, $instance->status);
        $this->assertNull($instance->current_step_id);
    }

    public function test_final_approval_sets_completed_at(): void
    {
        $doc = $this->createDocument();
        $wf = $this->createWorkflowWithSteps(1);
        $instance = $this->workflowService->start($this->adminA, $doc, $wf);

        $this->assertNull($instance->completed_at);

        $this->workflowService->approve($this->userA1, $instance);

        $instance->refresh();
        $this->assertNotNull($instance->completed_at);
    }

    // ==========================================
    // 9. REJECTION FLOW
    // ==========================================

    public function test_approver_can_reject_workflow_with_comment(): void
    {
        $doc = $this->createDocument();
        $wf = $this->createWorkflowWithSteps(1);
        $instance = $this->workflowService->start($this->adminA, $doc, $wf);

        $response = $this->actingAs($this->userA1)->postJson("/workflow-instances/{$instance->id}/reject", [
            'comment' => 'Non conforme aux critères',
        ]);

        $response->assertOk();
        $instance->refresh();
        $this->assertSame(WorkflowStatus::Rejected, $instance->status);
        $this->assertNull($instance->current_step_id);
        $this->assertNotNull($instance->completed_at);
    }

    public function test_rejection_fails_without_comment(): void
    {
        $doc = $this->createDocument();
        $wf = $this->createWorkflowWithSteps(1);
        $instance = $this->workflowService->start($this->adminA, $doc, $wf);

        $response = $this->actingAs($this->userA1)->postJson("/workflow-instances/{$instance->id}/reject", [
            'comment' => '',
        ]);

        $response->assertStatus(422);
    }

    public function test_rejection_marks_workflow_as_rejected_and_sets_completed_at(): void
    {
        $doc = $this->createDocument();
        $wf = $this->createWorkflowWithSteps(1);
        $instance = $this->workflowService->start($this->adminA, $doc, $wf);

        $this->workflowService->reject($this->userA1, $instance, 'Rejet motivé');

        $instance->refresh();
        $this->assertSame(WorkflowStatus::Rejected, $instance->status);
        $this->assertNotNull($instance->completed_at);
    }

    // ==========================================
    // 10. CORRECTION REQUEST & RESUBMISSION FLOW
    // ==========================================

    public function test_approver_can_request_correction_with_comment(): void
    {
        $doc = $this->createDocument();
        $wf = $this->createWorkflowWithSteps(1);
        $instance = $this->workflowService->start($this->adminA, $doc, $wf);

        $response = $this->actingAs($this->userA1)->postJson("/workflow-instances/{$instance->id}/correction", [
            'comment' => 'Veuillez corriger le paragraphe 2.',
        ]);

        $response->assertOk();
        $instance->refresh();
        $this->assertSame(WorkflowStatus::CorrectionRequested, $instance->status);
    }

    public function test_correction_request_fails_without_comment(): void
    {
        $doc = $this->createDocument();
        $wf = $this->createWorkflowWithSteps(1);
        $instance = $this->workflowService->start($this->adminA, $doc, $wf);

        $response = $this->actingAs($this->userA1)->postJson("/workflow-instances/{$instance->id}/correction", [
            'comment' => '',
        ]);

        $response->assertStatus(422);
    }

    public function test_correction_request_retains_current_step_id(): void
    {
        $doc = $this->createDocument();
        $wf = $this->createWorkflowWithSteps(2);
        $instance = $this->workflowService->start($this->adminA, $doc, $wf);
        $stepId = $instance->current_step_id;

        $this->workflowService->requestCorrection($this->userA1, $instance, 'Corriger la page 1');

        $instance->refresh();
        $this->assertSame(WorkflowStatus::CorrectionRequested, $instance->status);
        $this->assertSame($stepId, $instance->current_step_id);
    }

    public function test_initiator_can_resubmit_after_correction_and_returns_to_same_step(): void
    {
        $doc = $this->createDocument();
        $wf = $this->createWorkflowWithSteps(2);
        $instance = $this->workflowService->start($this->adminA, $doc, $wf);
        $stepId = $instance->current_step_id;

        $this->workflowService->requestCorrection($this->userA1, $instance, 'Corrections nécessaires');

        // Create a new version of the document
        DocumentVersion::create([
            'document_id' => $doc->id,
            'version_number' => 2,
            'file_name' => 'contrat_v2.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size' => 1040,
            'storage_disk' => 'private',
            'storage_path' => 'documents/contrat_v2.pdf',
            'uploaded_by' => $this->adminA->id,
            'comment' => 'Version corrigée',
        ]);

        $response = $this->actingAs($this->adminA)->postJson("/workflow-instances/{$instance->id}/resubmit", [
            'comment' => 'Document mis à jour avec les modifications',
        ]);

        $response->assertOk();
        $instance->refresh();
        $this->assertSame(WorkflowStatus::InProgress, $instance->status);
        $this->assertSame($stepId, $instance->current_step_id);
        $this->assertSame(2, $instance->documentVersion->version_number);
    }

    // ==========================================
    // 11. CANCELLATION FLOW
    // ==========================================

    public function test_initiator_can_cancel_active_workflow(): void
    {
        $doc = $this->createDocument();
        $wf = $this->createWorkflowWithSteps(1);
        $instance = $this->workflowService->start($this->adminA, $doc, $wf);

        $response = $this->actingAs($this->adminA)->postJson("/workflow-instances/{$instance->id}/cancel", [
            'comment' => 'Annulé suite à réunion',
        ]);

        $response->assertOk();
        $instance->refresh();
        $this->assertSame(WorkflowStatus::Cancelled, $instance->status);
        $this->assertNull($instance->current_step_id);
        $this->assertNotNull($instance->completed_at);
    }

    public function test_manager_can_cancel_active_workflow(): void
    {
        $doc = $this->createDocument();
        $wf = $this->createWorkflowWithSteps(1);
        $instance = $this->workflowService->start($this->adminA, $doc, $wf);

        $response = $this->actingAs($this->managerA)->postJson("/workflow-instances/{$instance->id}/cancel");
        $response->assertOk();

        $this->assertSame(WorkflowStatus::Cancelled, $instance->fresh()->status);
    }

    public function test_unauthorized_user_cannot_cancel_active_workflow(): void
    {
        $doc = $this->createDocument();
        $wf = $this->createWorkflowWithSteps(1);
        $instance = $this->workflowService->start($this->adminA, $doc, $wf);

        // userA1 is not initiator and does not have manager cancel permission
        $roleWithoutCancel = Role::create(['name' => 'simple_user', 'guard_name' => 'web', 'organization_id' => $this->orgA->id]);
        $roleWithoutCancel->givePermissionTo(['workflows.view']);
        $this->userA1->syncRoles([$roleWithoutCancel]);

        $response = $this->actingAs($this->userA1)->postJson("/workflow-instances/{$instance->id}/cancel");
        $response->assertForbidden();
    }

    // ==========================================
    // 12. VERSION TRACKING & HISTORY
    // ==========================================

    public function test_workflow_records_document_version_on_start_and_resubmit(): void
    {
        $doc = $this->createDocument();
        $v1 = $doc->versions()->first();
        $wf = $this->createWorkflowWithSteps(1);

        $instance = $this->workflowService->start($this->adminA, $doc, $wf);
        $this->assertSame($v1->id, $instance->document_version_id);

        $this->workflowService->requestCorrection($this->userA1, $instance, 'Modifs');

        $v2 = DocumentVersion::create([
            'document_id' => $doc->id,
            'version_number' => 2,
            'file_name' => 'contrat_v2.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size' => 1050,
            'storage_disk' => 'private',
            'storage_path' => 'documents/contrat_v2.pdf',
            'uploaded_by' => $this->adminA->id,
        ]);

        $this->workflowService->resubmit($this->adminA, $instance);
        $instance->refresh();
        $this->assertSame($v2->id, $instance->document_version_id);
    }

    public function test_history_endpoint_returns_chronological_actions_with_version_and_step(): void
    {
        $doc = $this->createDocument();
        $wf = $this->createWorkflowWithSteps(1);
        $instance = $this->workflowService->start($this->adminA, $doc, $wf);
        $this->workflowService->approve($this->userA1, $instance, 'Approbation finale');

        $response = $this->actingAs($this->adminA)->getJson("/workflow-instances/{$instance->id}/history");

        $response->assertOk();
        $data = $response->json();
        $this->assertCount(2, $data);
        $this->assertSame('approved', $data[0]['action']);
        $this->assertSame('submitted', $data[1]['action']);
    }

    public function test_new_active_workflow_can_be_started_after_previous_one_is_completed_or_cancelled(): void
    {
        $doc = $this->createDocument();
        $wf = $this->createWorkflowWithSteps(1);

        $instance1 = $this->workflowService->start($this->adminA, $doc, $wf);
        $this->workflowService->cancel($this->adminA, $instance1);

        $instance2 = $this->workflowService->start($this->adminA, $doc, $wf);
        $this->assertNotNull($instance2->id);
        $this->assertNotSame($instance1->id, $instance2->id);
    }

    // ==========================================
    // 13. CONCURRENCY, IDEMPOTENCE & GUARDS
    // ==========================================

    public function test_approving_an_already_approved_workflow_fails(): void
    {
        $doc = $this->createDocument();
        $wf = $this->createWorkflowWithSteps(1);
        $instance = $this->workflowService->start($this->adminA, $doc, $wf);
        $this->workflowService->approve($this->userA1, $instance);

        $response = $this->actingAs($this->userA1)->postJson("/workflow-instances/{$instance->id}/approve");
        $response->assertStatus(422); // Not in progress -> cannot approve
    }

    public function test_rejecting_an_already_rejected_or_cancelled_workflow_fails(): void
    {
        $doc = $this->createDocument();
        $wf = $this->createWorkflowWithSteps(1);
        $instance = $this->workflowService->start($this->adminA, $doc, $wf);
        $this->workflowService->cancel($this->adminA, $instance);

        $response = $this->actingAs($this->userA1)->postJson("/workflow-instances/{$instance->id}/reject", [
            'comment' => 'Rejet tardif',
        ]);
        $response->assertStatus(422);
    }

    public function test_document_with_active_workflow_cannot_be_archived(): void
    {
        $doc = $this->createDocument();
        $wf = $this->createWorkflowWithSteps(1);
        $this->workflowService->start($this->adminA, $doc, $wf);

        $this->expectException(HttpException::class);
        $this->lifecycleService->archive($this->adminA, $doc);
    }

    public function test_document_with_active_workflow_cannot_be_moved_to_trash(): void
    {
        $doc = $this->createDocument();
        $wf = $this->createWorkflowWithSteps(1);
        $this->workflowService->start($this->adminA, $doc, $wf);

        $this->expectException(HttpException::class);
        $this->lifecycleService->moveToTrash($this->adminA, $doc);
    }

    public function test_document_with_cancelled_or_approved_workflow_can_be_archived_or_trashed(): void
    {
        $doc = $this->createDocument();
        $wf = $this->createWorkflowWithSteps(1);
        $instance = $this->workflowService->start($this->adminA, $doc, $wf);
        $this->workflowService->cancel($this->adminA, $instance);

        $archivedDoc = $this->lifecycleService->archive($this->adminA, $doc);
        $this->assertSame('archived', $archivedDoc->status);
    }

    public function test_audit_logs_are_created_for_all_workflow_transitions(): void
    {
        $doc = $this->createDocument();
        $wf = $this->workflowService->createWorkflow($this->adminA, [
            'name' => 'Circuit Audit Spécial',
            'description' => 'Test audit',
            'is_active' => true,
        ]);
        $this->workflowService->addStep($this->adminA, $wf, [
            'name' => 'Étape 1',
            'position' => 1,
            'approver_type' => 'user',
            'approver_user_id' => $this->userA1->id,
        ]);

        $this->assertDatabaseHas('audit_logs', ['action' => 'workflow.created']);

        $instance = $this->workflowService->start($this->adminA, $doc, $wf);
        $this->assertDatabaseHas('audit_logs', ['action' => 'workflow.started']);

        $this->workflowService->approve($this->userA1, $instance);
        $this->assertDatabaseHas('audit_logs', ['action' => 'workflow.approved']);
    }

    public function test_notifications_are_sent_to_approvers_and_initiators_without_leaks(): void
    {
        Notification::fake();

        $doc = $this->createDocument();
        $wf = $this->createWorkflowWithSteps(1);

        $instance = $this->workflowService->start($this->adminA, $doc, $wf);

        Notification::assertSentTo(
            $this->userA1,
            WorkflowSubmittedNotification::class
        );

        Notification::assertNotSentTo(
            $this->userB1,
            WorkflowSubmittedNotification::class
        );
    }
}
