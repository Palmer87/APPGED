<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workflow;

class WorkflowPolicy
{
    /**
     * Determine whether the user can view any workflows.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('workflows.view');
    }

    /**
     * Determine whether the user can view the workflow.
     */
    public function view(User $user, Workflow $workflow): bool
    {
        if ($user->organization_id !== $workflow->organization_id) {
            return false;
        }

        return $user->can('workflows.view');
    }

    /**
     * Determine whether the user can create workflows.
     */
    public function create(User $user): bool
    {
        return $user->can('workflows.create');
    }

    /**
     * Determine whether the user can update the workflow.
     */
    public function update(User $user, Workflow $workflow): bool
    {
        if ($user->organization_id !== $workflow->organization_id) {
            return false;
        }

        return $user->can('workflows.update');
    }

    /**
     * Determine whether the user can delete the workflow.
     */
    public function delete(User $user, Workflow $workflow): bool
    {
        if ($user->organization_id !== $workflow->organization_id) {
            return false;
        }

        return $user->can('workflows.delete');
    }

    /**
     * Determine whether the user can execute the workflow.
     */
    public function execute(User $user, Workflow $workflow): bool
    {
        if ($user->organization_id !== $workflow->organization_id) {
            return false;
        }

        return $user->can('workflows.execute');
    }
}
