<?php

namespace App\Services;

use App\Enums\WorkflowActionType;
use App\Enums\WorkflowApproverType;
use App\Enums\WorkflowStatus;
use App\Models\Document;
use App\Models\Group;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowAction;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStep;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;

class WorkflowService
{
    public function __construct(
        protected ?AuditService $auditService = null,
        protected ?NotificationService $notificationService = null
    ) {
        $this->auditService = $this->auditService ?? app(AuditService::class);
        $this->notificationService = $this->notificationService ?? app(NotificationService::class);
    }

    /**
     * Create a new workflow.
     *
     * @param  array<string, mixed>  $data
     */
    public function createWorkflow(User $actor, array $data): Workflow
    {
        Gate::forUser($actor)->authorize('create', Workflow::class);

        $data['organization_id'] = $actor->organization_id;
        $data['created_by'] = $actor->id;
        $data['is_active'] = $data['is_active'] ?? true;

        $workflow = Workflow::create($data);

        $this->auditService->success(
            action: 'workflow.created',
            auditable: $workflow,
            newValues: $workflow->toArray(),
            user: $actor,
            description: "Workflow '{$workflow->name}' was created."
        );

        return $workflow;
    }

    /**
     * Update an existing workflow.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateWorkflow(User $actor, Workflow $workflow, array $data): Workflow
    {
        Gate::forUser($actor)->authorize('update', $workflow);

        $oldValues = $workflow->only(['name', 'description', 'is_active']);
        unset($data['organization_id'], $data['created_by']);

        $workflow->update($data);

        $this->auditService->success(
            action: 'workflow.updated',
            auditable: $workflow,
            oldValues: $oldValues,
            newValues: $workflow->only(['name', 'description', 'is_active']),
            user: $actor,
            description: "Workflow '{$workflow->name}' was updated."
        );

        return $workflow;
    }

    /**
     * Soft delete a workflow.
     */
    public function deleteWorkflow(User $actor, Workflow $workflow): void
    {
        Gate::forUser($actor)->authorize('delete', $workflow);

        // Prevent deletion if an active instance is running
        $hasActive = $workflow->instances()
            ->whereIn('status', [WorkflowStatus::Pending, WorkflowStatus::InProgress, WorkflowStatus::CorrectionRequested])
            ->exists();

        if ($hasActive) {
            throw new HttpException(422, 'Cannot delete a workflow with active instances.');
        }

        $workflow->delete();

        $this->auditService->success(
            action: 'workflow.deleted',
            auditable: $workflow,
            user: $actor,
            description: "Workflow '{$workflow->name}' was deleted."
        );
    }

    /**
     * Add a step to a workflow.
     *
     * @param  array<string, mixed>  $data
     */
    public function addStep(User $actor, Workflow $workflow, array $data): WorkflowStep
    {
        Gate::forUser($actor)->authorize('update', $workflow);

        $this->validateStepApproverAndPosition($workflow, $data);

        $data['organization_id'] = $workflow->organization_id;
        $data['workflow_id'] = $workflow->id;

        $step = WorkflowStep::create($data);

        $this->auditService->success(
            action: 'workflow.step_created',
            auditable: $step,
            newValues: $step->toArray(),
            user: $actor,
            description: "Step '{$step->name}' was added to workflow '{$workflow->name}' at position {$step->position}."
        );

        return $step;
    }

    /**
     * Update an existing step.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateStep(User $actor, WorkflowStep $step, array $data): WorkflowStep
    {
        Gate::forUser($actor)->authorize('update', $step->workflow);

        $mergedData = array_merge([
            'approver_type' => $step->approver_type,
            'approver_user_id' => $step->approver_user_id,
            'approver_group_id' => $step->approver_group_id,
            'position' => $step->position,
        ], $data);

        $this->validateStepApproverAndPosition($step->workflow, $mergedData, $step->id);

        $oldValues = $step->toArray();
        unset($data['organization_id'], $data['workflow_id']);

        $step->update($data);

        $this->auditService->success(
            action: 'workflow.step_updated',
            auditable: $step,
            oldValues: $oldValues,
            newValues: $step->toArray(),
            user: $actor,
            description: "Step '{$step->name}' of workflow '{$step->workflow->name}' was updated."
        );

        return $step;
    }

    /**
     * Remove a step from a workflow.
     */
    public function removeStep(User $actor, WorkflowStep $step): void
    {
        Gate::forUser($actor)->authorize('update', $step->workflow);

        $hasActive = $step->workflow->instances()
            ->whereIn('status', [WorkflowStatus::Pending, WorkflowStatus::InProgress, WorkflowStatus::CorrectionRequested])
            ->exists();

        if ($hasActive) {
            throw new HttpException(422, 'Cannot remove steps from a workflow with active instances.');
        }

        $stepName = $step->name;
        $workflowName = $step->workflow->name;

        $step->delete();

        $this->auditService->success(
            action: 'workflow.step_deleted',
            auditable: $step,
            user: $actor,
            description: "Step '{$stepName}' of workflow '{$workflowName}' was removed."
        );
    }

    /**
     * Reorder steps in a workflow.
     *
     * @param  array<int, int>  $positions  [step_id => position]
     */
    public function reorderSteps(User $actor, Workflow $workflow, array $positions): void
    {
        Gate::forUser($actor)->authorize('update', $workflow);

        DB::transaction(function () use ($workflow, $positions) {
            // First apply a temporary offset to prevent unique constraint collisions
            foreach ($positions as $stepId => $pos) {
                WorkflowStep::where('id', $stepId)
                    ->where('workflow_id', $workflow->id)
                    ->update(['position' => $pos + 10000]);
            }

            foreach ($positions as $stepId => $pos) {
                WorkflowStep::where('id', $stepId)
                    ->where('workflow_id', $workflow->id)
                    ->update(['position' => $pos]);
            }
        });

        $this->auditService->success(
            action: 'workflow.steps_reordered',
            auditable: $workflow,
            user: $actor,
            description: "Steps of workflow '{$workflow->name}' were reordered."
        );
    }

    /**
     * Check if a given user can approve the current step of an instance.
     */
    public function canUserApproveStep(User $user, WorkflowInstance $instance): bool
    {
        if ($user->organization_id !== $instance->organization_id) {
            return false;
        }

        if ($instance->status !== WorkflowStatus::InProgress) {
            return false;
        }

        $step = $instance->currentStep;
        if (! $step) {
            return false;
        }

        if ($step->approver_type === WorkflowApproverType::User) {
            return $step->approver_user_id === $user->id;
        }

        if ($step->approver_type === WorkflowApproverType::Group && $step->approver_group_id) {
            return $user->groups()->where('groups.id', $step->approver_group_id)->exists();
        }

        return false;
    }

    /**
     * Start a new workflow instance for a document.
     */
    public function start(User $actor, Document $document, Workflow $workflow): WorkflowInstance
    {
        // 1. Tenant safety check
        if ($actor->organization_id !== $document->organization_id || $actor->organization_id !== $workflow->organization_id) {
            throw new HttpException(403, 'Cross-tenant workflow operations are prohibited.');
        }

        // 2. Document & Workflow status check
        if ($document->status !== 'active' || $document->trashed()) {
            throw new HttpException(422, 'Document must be active and not trashed to start a workflow.');
        }

        if (! $workflow->is_active) {
            throw new HttpException(422, 'Workflow is inactive.');
        }

        // 3. Document accessibility check
        Gate::forUser($actor)->authorize('view', $document);

        // 4. Actor permission check
        Gate::forUser($actor)->authorize('execute', $workflow);

        // 5. Workflow must have at least one step
        $firstStep = $workflow->steps()->orderBy('position')->first();
        if (! $firstStep) {
            throw new HttpException(422, 'Workflow has no configured steps.');
        }

        // 6. Check for active workflow instance on this document
        $activeInstanceExists = WorkflowInstance::where('document_id', $document->id)
            ->whereIn('status', [WorkflowStatus::Pending, WorkflowStatus::InProgress, WorkflowStatus::CorrectionRequested])
            ->exists();

        if ($activeInstanceExists) {
            throw new HttpException(422, 'A workflow is already active for this document.');
        }

        // 7. Resolve current document version
        $currentVersion = $document->versions()->latest('version_number')->first();

        return DB::transaction(function () use ($actor, $document, $workflow, $firstStep, $currentVersion) {
            $instance = WorkflowInstance::create([
                'organization_id' => $workflow->organization_id,
                'workflow_id' => $workflow->id,
                'document_id' => $document->id,
                'document_version_id' => $currentVersion?->id,
                'started_by' => $actor->id,
                'current_step_id' => $firstStep->id,
                'status' => WorkflowStatus::InProgress,
                'started_at' => now(),
            ]);

            WorkflowAction::create([
                'organization_id' => $instance->organization_id,
                'workflow_instance_id' => $instance->id,
                'workflow_step_id' => $firstStep->id,
                'user_id' => $actor->id,
                'document_version_id' => $currentVersion?->id,
                'action' => WorkflowActionType::Submitted,
                'comment' => 'Workflow démarré',
            ]);

            $this->auditService->success(
                action: 'workflow.started',
                auditable: $instance,
                metadata: [
                    'workflow_id' => $workflow->id,
                    'document_id' => $document->id,
                    'document_version_id' => $currentVersion?->id,
                    'first_step_id' => $firstStep->id,
                ],
                user: $actor,
                description: "Workflow '{$workflow->name}' started on document '{$document->name}'."
            );

            $this->notificationService->notifyWorkflowSubmitted($instance, $actor, $firstStep);

            return $instance;
        });
    }

    /**
     * Approve the current step of an instance.
     */
    public function approve(User $actor, WorkflowInstance $instance, ?string $comment = null): WorkflowAction
    {
        return DB::transaction(function () use ($actor, $instance, $comment) {
            $lockedInstance = WorkflowInstance::where('id', $instance->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedInstance->organization_id !== $actor->organization_id) {
                throw new HttpException(403, 'Cross-tenant workflow operations are prohibited.');
            }

            if ($lockedInstance->status !== WorkflowStatus::InProgress) {
                throw new HttpException(422, 'Workflow instance is not in progress.');
            }

            if (! $this->canUserApproveStep($actor, $lockedInstance)) {
                throw new HttpException(403, 'You are not the designated approver for this step.');
            }

            Gate::forUser($actor)->authorize('approve', $lockedInstance);

            $currentStep = $lockedInstance->currentStep;
            $versionId = $lockedInstance->document_version_id;

            $action = WorkflowAction::create([
                'organization_id' => $lockedInstance->organization_id,
                'workflow_instance_id' => $lockedInstance->id,
                'workflow_step_id' => $currentStep?->id,
                'user_id' => $actor->id,
                'document_version_id' => $versionId,
                'action' => WorkflowActionType::Approved,
                'comment' => $comment,
            ]);

            // Find next step
            $nextStep = WorkflowStep::where('workflow_id', $lockedInstance->workflow_id)
                ->where('position', '>', $currentStep->position)
                ->orderBy('position')
                ->first();

            if ($nextStep) {
                $lockedInstance->update([
                    'current_step_id' => $nextStep->id,
                    'status' => WorkflowStatus::InProgress,
                ]);

                $this->notificationService->notifyWorkflowStepAssigned($lockedInstance, $actor, $nextStep);
            } else {
                $lockedInstance->update([
                    'current_step_id' => null,
                    'status' => WorkflowStatus::Approved,
                    'completed_at' => now(),
                ]);

                $this->notificationService->notifyWorkflowApproved($lockedInstance, $actor, $currentStep, true);
            }

            $this->auditService->success(
                action: 'workflow.approved',
                auditable: $lockedInstance,
                metadata: [
                    'workflow_id' => $lockedInstance->workflow_id,
                    'document_id' => $lockedInstance->document_id,
                    'step_id' => $currentStep?->id,
                    'next_step_id' => $nextStep?->id,
                    'is_final' => $nextStep === null,
                    'comment' => $comment,
                ],
                user: $actor,
                description: $nextStep ? "Step '{$currentStep->name}' approved." : "Workflow '{$lockedInstance->workflow->name}' fully approved."
            );

            return $action;
        });
    }

    /**
     * Reject a workflow instance.
     */
    public function reject(User $actor, WorkflowInstance $instance, string $comment): WorkflowAction
    {
        if (trim($comment) === '') {
            throw new HttpException(422, 'A comment is required to reject a workflow.');
        }

        return DB::transaction(function () use ($actor, $instance, $comment) {
            $lockedInstance = WorkflowInstance::where('id', $instance->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedInstance->organization_id !== $actor->organization_id) {
                throw new HttpException(403, 'Cross-tenant workflow operations are prohibited.');
            }

            if ($lockedInstance->status !== WorkflowStatus::InProgress) {
                throw new HttpException(422, 'Workflow instance is not in progress.');
            }

            if (! $this->canUserApproveStep($actor, $lockedInstance)) {
                throw new HttpException(403, 'You are not the designated approver for this step.');
            }

            Gate::forUser($actor)->authorize('reject', $lockedInstance);

            $currentStep = $lockedInstance->currentStep;
            $versionId = $lockedInstance->document_version_id;

            $action = WorkflowAction::create([
                'organization_id' => $lockedInstance->organization_id,
                'workflow_instance_id' => $lockedInstance->id,
                'workflow_step_id' => $currentStep?->id,
                'user_id' => $actor->id,
                'document_version_id' => $versionId,
                'action' => WorkflowActionType::Rejected,
                'comment' => $comment,
            ]);

            $lockedInstance->update([
                'current_step_id' => null,
                'status' => WorkflowStatus::Rejected,
                'completed_at' => now(),
            ]);

            $this->auditService->success(
                action: 'workflow.rejected',
                auditable: $lockedInstance,
                metadata: [
                    'workflow_id' => $lockedInstance->workflow_id,
                    'document_id' => $lockedInstance->document_id,
                    'step_id' => $currentStep?->id,
                    'comment' => $comment,
                ],
                user: $actor,
                description: "Workflow '{$lockedInstance->workflow->name}' rejected at step '{$currentStep?->name}'."
            );

            $this->notificationService->notifyWorkflowRejected($lockedInstance, $actor, $comment, $currentStep);

            return $action;
        });
    }

    /**
     * Request corrections on a workflow instance.
     */
    public function requestCorrection(User $actor, WorkflowInstance $instance, string $comment): WorkflowAction
    {
        if (trim($comment) === '') {
            throw new HttpException(422, 'A comment is required to request a correction.');
        }

        return DB::transaction(function () use ($actor, $instance, $comment) {
            $lockedInstance = WorkflowInstance::where('id', $instance->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedInstance->organization_id !== $actor->organization_id) {
                throw new HttpException(403, 'Cross-tenant workflow operations are prohibited.');
            }

            if ($lockedInstance->status !== WorkflowStatus::InProgress) {
                throw new HttpException(422, 'Workflow instance is not in progress.');
            }

            if (! $this->canUserApproveStep($actor, $lockedInstance)) {
                throw new HttpException(403, 'You are not the designated approver for this step.');
            }

            Gate::forUser($actor)->authorize('requestCorrection', $lockedInstance);

            $currentStep = $lockedInstance->currentStep;
            $versionId = $lockedInstance->document_version_id;

            $action = WorkflowAction::create([
                'organization_id' => $lockedInstance->organization_id,
                'workflow_instance_id' => $lockedInstance->id,
                'workflow_step_id' => $currentStep?->id,
                'user_id' => $actor->id,
                'document_version_id' => $versionId,
                'action' => WorkflowActionType::CorrectionRequested,
                'comment' => $comment,
            ]);

            // Keep current_step_id so resubmission returns to this step
            $lockedInstance->update([
                'status' => WorkflowStatus::CorrectionRequested,
            ]);

            $this->auditService->success(
                action: 'workflow.correction_requested',
                auditable: $lockedInstance,
                metadata: [
                    'workflow_id' => $lockedInstance->workflow_id,
                    'document_id' => $lockedInstance->document_id,
                    'step_id' => $currentStep?->id,
                    'comment' => $comment,
                ],
                user: $actor,
                description: "Correction requested on workflow '{$lockedInstance->workflow->name}' at step '{$currentStep?->name}'."
            );

            $this->notificationService->notifyWorkflowCorrectionRequested($lockedInstance, $actor, $comment, $currentStep);

            return $action;
        });
    }

    /**
     * Resubmit a workflow instance after corrections.
     */
    public function resubmit(User $actor, WorkflowInstance $instance, ?string $comment = null): WorkflowAction
    {
        return DB::transaction(function () use ($actor, $instance, $comment) {
            $lockedInstance = WorkflowInstance::where('id', $instance->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedInstance->organization_id !== $actor->organization_id) {
                throw new HttpException(403, 'Cross-tenant workflow operations are prohibited.');
            }

            if ($lockedInstance->status !== WorkflowStatus::CorrectionRequested) {
                throw new HttpException(422, 'Workflow instance is not in correction_requested status.');
            }

            Gate::forUser($actor)->authorize('resubmit', $lockedInstance);

            $document = $lockedInstance->document;
            if ($document->status !== 'active' || $document->trashed()) {
                throw new HttpException(422, 'Document must be active to resubmit workflow.');
            }

            // Associate newly uploaded version if available
            $latestVersion = $document->versions()->latest('version_number')->first();
            $currentStep = $lockedInstance->currentStep;

            $action = WorkflowAction::create([
                'organization_id' => $lockedInstance->organization_id,
                'workflow_instance_id' => $lockedInstance->id,
                'workflow_step_id' => $currentStep?->id,
                'user_id' => $actor->id,
                'document_version_id' => $latestVersion?->id,
                'action' => WorkflowActionType::Submitted,
                'comment' => $comment ?? 'Document resoumis après correction',
            ]);

            $lockedInstance->update([
                'status' => WorkflowStatus::InProgress,
                'document_version_id' => $latestVersion?->id,
            ]);

            $this->auditService->success(
                action: 'workflow.resubmitted',
                auditable: $lockedInstance,
                metadata: [
                    'workflow_id' => $lockedInstance->workflow_id,
                    'document_id' => $lockedInstance->document_id,
                    'step_id' => $currentStep?->id,
                    'document_version_id' => $latestVersion?->id,
                    'comment' => $comment,
                ],
                user: $actor,
                description: "Workflow '{$lockedInstance->workflow->name}' resubmitted after correction."
            );

            if ($currentStep) {
                $this->notificationService->notifyWorkflowStepAssigned($lockedInstance, $actor, $currentStep);
            }

            return $action;
        });
    }

    /**
     * Cancel an active workflow instance.
     */
    public function cancel(User $actor, WorkflowInstance $instance, ?string $comment = null): WorkflowAction
    {
        return DB::transaction(function () use ($actor, $instance, $comment) {
            $lockedInstance = WorkflowInstance::where('id', $instance->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedInstance->organization_id !== $actor->organization_id) {
                throw new HttpException(403, 'Cross-tenant workflow operations are prohibited.');
            }

            if (! $lockedInstance->status->isActive()) {
                throw new HttpException(422, 'Only active workflow instances can be cancelled.');
            }

            Gate::forUser($actor)->authorize('cancel', $lockedInstance);

            $currentStep = $lockedInstance->currentStep;
            $versionId = $lockedInstance->document_version_id;

            $action = WorkflowAction::create([
                'organization_id' => $lockedInstance->organization_id,
                'workflow_instance_id' => $lockedInstance->id,
                'workflow_step_id' => $currentStep?->id,
                'user_id' => $actor->id,
                'document_version_id' => $versionId,
                'action' => WorkflowActionType::Cancelled,
                'comment' => $comment,
            ]);

            $lockedInstance->update([
                'current_step_id' => null,
                'status' => WorkflowStatus::Cancelled,
                'completed_at' => now(),
            ]);

            $this->auditService->success(
                action: 'workflow.cancelled',
                auditable: $lockedInstance,
                metadata: [
                    'workflow_id' => $lockedInstance->workflow_id,
                    'document_id' => $lockedInstance->document_id,
                    'comment' => $comment,
                ],
                user: $actor,
                description: "Workflow '{$lockedInstance->workflow->name}' was cancelled."
            );

            return $action;
        });
    }

    /**
     * Get the current step of an instance.
     */
    public function getCurrentStep(WorkflowInstance $instance): ?WorkflowStep
    {
        return $instance->currentStep;
    }

    /**
     * Get the complete action history for a workflow instance.
     */
    public function getInstanceHistory(WorkflowInstance $instance): Collection
    {
        return $instance->actions()
            ->with(['user', 'step', 'version'])
            ->reorder('id', 'desc')
            ->get();
    }

    /**
     * Validate step approver XOR rule, tenant isolation, and position uniqueness.
     *
     * @param  array<string, mixed>  $data
     */
    protected function validateStepApproverAndPosition(Workflow $workflow, array $data, ?int $excludeStepId = null): void
    {
        $approverType = $data['approver_type'] ?? null;
        if ($approverType instanceof WorkflowApproverType) {
            $approverType = $approverType->value;
        }

        $userId = $data['approver_user_id'] ?? null;
        $groupId = $data['approver_group_id'] ?? null;

        // XOR validation
        if ($approverType === 'user') {
            if (empty($userId) || ! empty($groupId)) {
                throw new HttpException(422, 'A user approver step must have approver_user_id set and approver_group_id null.');
            }
            $user = User::find($userId);
            if (! $user || $user->organization_id !== $workflow->organization_id) {
                throw new HttpException(422, 'Designated approver user does not exist in this organization.');
            }
        } elseif ($approverType === 'group') {
            if (empty($groupId) || ! empty($userId)) {
                throw new HttpException(422, 'A group approver step must have approver_group_id set and approver_user_id null.');
            }
            $group = Group::find($groupId);
            if (! $group || $group->organization_id !== $workflow->organization_id) {
                throw new HttpException(422, 'Designated approver group does not exist in this organization.');
            }
        } else {
            throw new HttpException(422, 'Invalid approver_type.');
        }

        // Position uniqueness within workflow
        if (isset($data['position'])) {
            $posQuery = $workflow->steps()->where('position', $data['position']);
            if ($excludeStepId) {
                $posQuery->where('id', '!=', $excludeStepId);
            }
            if ($posQuery->exists()) {
                throw new HttpException(422, "A step with position {$data['position']} already exists in this workflow.");
            }
        }
    }
}
