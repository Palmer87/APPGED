<?php

namespace App\Policies;

use App\Models\Direction;
use App\Models\User;

class DirectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('directions.view') || $user->can('folders.view') || $user->hasRole('admin') || $user->hasRole('super-admin');
    }

    public function view(User $user, Direction $direction): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $direction->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->can('directions.create') || $user->can('folders.create') || $user->hasRole('admin') || $user->hasRole('super-admin');
    }

    public function update(User $user, Direction $direction): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ($user->organization_id !== $direction->organization_id) {
            return false;
        }

        return $user->can('directions.update') || $user->can('folders.update') || $user->hasRole('admin');
    }

    public function delete(User $user, Direction $direction): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ($user->organization_id !== $direction->organization_id) {
            return false;
        }

        return $user->can('directions.delete') || $user->can('folders.delete') || $user->hasRole('admin');
    }
}
