<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    /**
     * Determine whether the user can view any roles.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('roles.view') || $user->can('users.view') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view the role.
     */
    public function view(User $user, Role $role): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ($role->name === 'super-admin') {
            return false;
        }

        $teamId = $role->organization_id ?? $role->team_id;
        if ($teamId && (int) $teamId !== (int) $user->organization_id) {
            return false;
        }

        return $user->can('roles.view') || $user->can('users.view') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can create roles.
     */
    public function create(User $user): bool
    {
        return $user->can('roles.create') || $user->can('users.create') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the role.
     */
    public function update(User $user, Role $role): bool
    {
        if ($role->name === 'super-admin') {
            return false;
        }

        $teamId = $role->organization_id ?? $role->team_id;
        if ($teamId && (int) $teamId !== (int) $user->organization_id) {
            return false;
        }

        return $user->can('roles.update') || $user->can('users.update') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the role.
     */
    public function delete(User $user, Role $role): bool
    {
        // System roles cannot be deleted
        if (in_array($role->name, ['super-admin', 'admin'], true)) {
            return false;
        }

        $teamId = $role->organization_id ?? $role->team_id;
        if ($teamId && (int) $teamId !== (int) $user->organization_id) {
            return false;
        }

        return $user->can('roles.delete') || $user->can('users.delete') || $user->hasRole('admin');
    }
}
