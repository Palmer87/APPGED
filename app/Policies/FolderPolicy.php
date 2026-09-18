<?php

namespace App\Policies;

use App\Models\Folder;
use App\Models\User;
use App\Services\AccessControlService;

class FolderPolicy
{
    public function __construct(
        protected AccessControlService $aclService
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->can('folders.view') || $user->hasRole('admin') || $user->hasRole('super-admin');
    }

    public function view(User $user, Folder $folder): bool
    {
        if ($user->organization_id !== $folder->organization_id) {
            return false;
        }

        if (! $user->can('folders.view')) {
            return false;
        }

        if ($this->aclService->hasAcl($folder)) {
            return $this->aclService->canAccessFolder($user, $folder, 'view');
        }

        return true;
    }

    public function create(User $user): bool
    {
        // Folder will be created under user's organization automatically
        return $user->can('folders.create');
    }

    public function update(User $user, Folder $folder): bool
    {
        if ($user->organization_id !== $folder->organization_id) {
            return false;
        }

        if (! $user->can('folders.update')) {
            return false;
        }

        if ($this->aclService->hasAcl($folder)) {
            return $this->aclService->canAccessFolder($user, $folder, 'update');
        }

        return true;
    }

    public function delete(User $user, Folder $folder): bool
    {
        if ($user->organization_id !== $folder->organization_id) {
            return false;
        }

        if (! $user->can('folders.delete')) {
            return false;
        }

        if ($this->aclService->hasAcl($folder)) {
            return $this->aclService->canAccessFolder($user, $folder, 'delete');
        }

        return true;
    }

    public function share(User $user, Folder $folder): bool
    {
        if ($user->organization_id !== $folder->organization_id) {
            return false;
        }

        if (! $user->can('folders.share')) {
            return false;
        }

        if ($this->aclService->hasAcl($folder)) {
            return $this->aclService->canAccessFolder($user, $folder, 'share');
        }

        return true;
    }

    public function createDepartment(User $user): bool
    {
        return $user->can('folders.create') || $user->hasRole('admin') || $user->hasRole('super-admin');
    }

    public function createDocumentType(User $user): bool
    {
        return $user->can('folders.create') || $user->hasRole('admin') || $user->hasRole('super-admin');
    }

    public function manageMetadata(User $user, Folder $folder): bool
    {
        return $this->update($user, $folder);
    }
}
