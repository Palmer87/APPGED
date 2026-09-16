<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use App\Services\AccessControlService;

class DocumentPolicy
{
    public function __construct(
        protected AccessControlService $aclService
    ) {}

    /**
     * Determine whether the user can view the document.
     */
    public function view(User $user, Document $document): bool
    {
        if ($user->organization_id !== $document->organization_id) {
            return false;
        }

        // Access via active share or direct ACL
        if ($this->aclService->canAccessDocument($user, $document, 'view')) {
            return true;
        }

        if (! $user->can('documents.view')) {
            return false;
        }

        if ($this->aclService->hasAcl($document)) {
            return $this->aclService->canAccessDocument($user, $document, 'view');
        }

        return true;
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

        if (! $user->can('documents.update')) {
            return false;
        }

        if ($this->aclService->hasAcl($document)) {
            return $this->aclService->canAccessDocument($user, $document, 'update');
        }

        return true;
    }

    public function delete(User $user, Document $document): bool
    {
        if ($user->organization_id !== $document->organization_id) {
            return false;
        }

        if (! $user->can('documents.delete')) {
            return false;
        }

        if ($this->aclService->hasAcl($document)) {
            return $this->aclService->canAccessDocument($user, $document, 'delete');
        }

        return true;
    }

    public function download(User $user, Document $document): bool
    {
        if ($user->organization_id !== $document->organization_id) {
            return false;
        }

        // Access via active share with download permission or direct ACL
        if ($this->aclService->canAccessDocument($user, $document, 'download')) {
            return true;
        }

        if (! $user->can('documents.download')) {
            return false;
        }

        if ($this->aclService->hasAcl($document)) {
            return $this->aclService->canAccessDocument($user, $document, 'download');
        }

        return true;
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

        if (! $user->can('documents.archive')) {
            return false;
        }

        if ($this->aclService->hasAcl($document)) {
            return $this->aclService->canAccessDocument($user, $document, 'archive');
        }

        return true;
    }

    public function restore(User $user, Document $document): bool
    {
        if ($user->organization_id !== $document->organization_id) {
            return false;
        }

        if (! $user->can('documents.restore')) {
            return false;
        }

        if ($this->aclService->hasAcl($document)) {
            return $this->aclService->canAccessDocument($user, $document, 'restore');
        }

        return true;
    }

    public function forceDelete(User $user, Document $document): bool
    {
        if ($user->organization_id !== $document->organization_id) {
            return false;
        }

        if (! $user->can('documents.delete')) {
            return false;
        }

        if ($this->aclService->hasAcl($document)) {
            return $this->aclService->canAccessDocument($user, $document, 'delete');
        }

        return true;
    }
}
