<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkflowInstance;
use App\Services\WorkflowService;

class WorkflowInstancePolicy
{
    /**
     * Determine whether the user can view the workflow instance.
     */
    public function view(User $user, WorkflowInstance $instance): bool
    {
        if ($user->organization_id !== $instance->organization_id) {
            return false;
        }

        return $user->can('workflows.view') || $user->id === $instance->started_by;
    }

    /**
     * Determine whether the user can approve the current step of the instance.
     */
    public function approve(User $user, WorkflowInstance $instance): bool
    {
        if ($user->organization_id !== $instance->organization_id) {
            return false;
        }

        if (! $user->can('workflows.approve')) {
            return false;
        }

        return app(WorkflowService::class)->canUserApproveStep($user, $instance);
    }

    /**
     * Determine whether the user can reject the instance.
     */
    public function reject(User $user, WorkflowInstance $instance): bool
    {
        if ($user->organization_id !== $instance->organization_id) {
            return false;
        }

        if (! $user->can('workflows.reject')) {
            return false;
        }

        return app(WorkflowService::class)->canUserApproveStep($user, $instance);
    }

    /**
     * Determine whether the user can request corrections on the instance.
     */
    public function requestCorrection(User $user, WorkflowInstance $instance): bool
    {
        if ($user->organization_id !== $instance->organization_id) {
            return false;
        }

        if (! $user->can('workflows.approve')) {
            return false;
        }

        return app(WorkflowService::class)->canUserApproveStep($user, $instance);
    }

    /**
     * Determine whether the user can resubmit the instance.
     */
    public function resubmit(User $user, WorkflowInstance $instance): bool
    {
        if ($user->organization_id !== $instance->organization_id) {
            return false;
        }

        return $user->id === $instance->started_by || $user->can('workflows.execute');
    }

    /**
     * Determine whether the user can cancel the instance.
     */
    public function cancel(User $user, WorkflowInstance $instance): bool
    {
        if ($user->organization_id !== $instance->organization_id) {
            return false;
        }

        return $user->id === $instance->started_by || $user->can('workflows.cancel');
    }
}
