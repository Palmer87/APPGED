<?php

namespace App\Policies;

use App\Models\Folder;
use App\Models\User;

class FolderPolicy
{
    public function view(User $user, Folder $folder): bool
    {
        if ($user->organization_id !== $folder->organization_id) {
            return false;
        }

        return $user->can('folders.view');
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

        return $user->can('folders.update');
    }

    public function delete(User $user, Folder $folder): bool
    {
        if ($user->organization_id !== $folder->organization_id) {
            return false;
        }

        return $user->can('folders.delete');
    }

    public function share(User $user, Folder $folder): bool
    {
        if ($user->organization_id !== $folder->organization_id) {
            return false;
        }

        return $user->can('folders.share');
    }
}
