<?php

namespace App\Policies;

use App\Models\AccessScope;
use App\Models\User;

class AccessScopePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('access_scopes.view') || $user->can('users.view') || $user->hasRole('admin') || $user->hasRole('super-admin');
    }

    public function view(User $user, AccessScope $scope): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $scope->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->can('access_scopes.create') || $user->can('users.update') || $user->hasRole('admin') || $user->hasRole('super-admin');
    }

    public function delete(User $user, AccessScope $scope): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ($user->organization_id !== $scope->organization_id) {
            return false;
        }

        return $user->can('access_scopes.delete') || $user->can('users.update') || $user->hasRole('admin');
    }
}
