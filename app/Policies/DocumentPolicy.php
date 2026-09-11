<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    /**
     * Determine whether the user can view the document.
     */
    public function view(User $user, Document $document): bool
    {
        if ($user->organization_id !== $document->organization_id) {
            return false;
        }

        return $user->can('documents.view');
    }

    public function create(User $user): bool
    {
        // Creating a document will automatically be linked to the user's organisation
        return $user->can('documents.create');
    }

    public function update(User $user, Document $document): bool
    {
        if ($user->organization_id !== $document->organization_id) {
            return false;
        }

        return $user->can('documents.update');
    }

    public function delete(User $user, Document $document): bool
    {
        if ($user->organization_id !== $document->organization_id) {
            return false;
        }

        return $user->can('documents.delete');
    }

    public function download(User $user, Document $document): bool
    {
        if ($user->organization_id !== $document->organization_id) {
            return false;
        }

        return $user->can('documents.download');
    }

    public function share(User $user, Document $document): bool
    {
        if ($user->organization_id !== $document->organization_id) {
            return false;
        }

        return $user->can('documents.share');
    }

    public function archive(User $user, Document $document): bool
    {
        if ($user->organization_id !== $document->organization_id) {
            return false;
        }

        return $user->can('documents.archive');
    }

    public function restore(User $user, Document $document): bool
    {
        if ($user->organization_id !== $document->organization_id) {
            return false;
        }

        return $user->can('documents.restore');
    }
}
