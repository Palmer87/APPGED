<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    /**
     * Determine whether the user can view any audit logs.
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->can('audit.view');
    }

    /**
     * Determine whether the user can view a specific audit log.
     */
    public function view(User $user, AuditLog $auditLog): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ($user->organization_id !== $auditLog->organization_id) {
            return false;
        }

        return $user->can('audit.view');
    }

    /**
     * Audit logs cannot be created manually by users.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Audit logs are immutable and cannot be updated.
     */
    public function update(User $user, AuditLog $auditLog): bool
    {
        return false;
    }

    /**
     * Audit logs cannot be deleted from user endpoints.
     */
    public function delete(User $user, AuditLog $auditLog): bool
    {
        return false;
    }
}
