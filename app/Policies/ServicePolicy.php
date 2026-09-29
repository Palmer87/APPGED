<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('services.view') || $user->can('folders.view') || $user->hasRole('admin') || $user->hasRole('super-admin');
    }

    public function view(User $user, Service $service): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $service->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->can('services.create') || $user->can('folders.create') || $user->hasRole('admin') || $user->hasRole('super-admin');
    }

    public function update(User $user, Service $service): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ($user->organization_id !== $service->organization_id) {
            return false;
        }

        return $user->can('services.update') || $user->can('folders.update') || $user->hasRole('admin');
    }

    public function delete(User $user, Service $service): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ($user->organization_id !== $service->organization_id) {
            return false;
        }

        return $user->can('services.delete') || $user->can('folders.delete') || $user->hasRole('admin');
    }

    public function manageUsers(User $user, Service $service): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ($user->organization_id !== $service->organization_id) {
            return false;
        }

        return $user->can('users.update') || $user->hasRole('admin');
    }
}
