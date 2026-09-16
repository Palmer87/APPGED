<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\DocumentComment;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class DocumentCommentPolicy
{
    /**
     * Determine whether the user can view comments on the document.
     */
    public function viewAny(User $user, Document $document): bool
    {
        if ($user->organization_id !== $document->organization_id) {
            return false;
        }

        if (! $user->can('comments.view')) {
            return false;
        }

        return Gate::forUser($user)->allows('view', $document);
    }

    /**
     * Determine whether the user can view the comment.
     */
    public function view(User $user, DocumentComment $comment): bool
    {
        if ($user->organization_id !== $comment->organization_id) {
            return false;
        }

        if (! $user->can('comments.view')) {
            return false;
        }

        $document = $comment->document;
        if (! $document || $document->trashed()) {
            return false;
        }

        return Gate::forUser($user)->allows('view', $document);
    }

    /**
     * Determine whether the user can create comments on the document.
     */
    public function create(User $user, Document $document): bool
    {
        if ($user->organization_id !== $document->organization_id) {
            return false;
        }

        if ($document->trashed() || $document->status !== 'active') {
            return false;
        }

        if (! $user->can('comments.create')) {
            return false;
        }

        return Gate::forUser($user)->allows('view', $document);
    }

    /**
     * Determine whether the user can update the comment.
     */
    public function update(User $user, DocumentComment $comment): bool
    {
        if ($user->organization_id !== $comment->organization_id) {
            return false;
        }

        $document = $comment->document;
        if (! $document || $document->trashed() || $document->status !== 'active') {
            return false;
        }

        if ($user->can('comments.moderate')) {
            return true;
        }

        return $comment->user_id === $user->id && $user->can('comments.update');
    }

    /**
     * Determine whether the user can delete the comment.
     */
    public function delete(User $user, DocumentComment $comment): bool
    {
        if ($user->organization_id !== $comment->organization_id) {
            return false;
        }

        if ($user->can('comments.moderate')) {
            return true;
        }

        return $comment->user_id === $user->id && $user->can('comments.delete');
    }

    /**
     * Determine whether the user can restore the comment.
     */
    public function restore(User $user, DocumentComment $comment): bool
    {
        if ($user->organization_id !== $comment->organization_id) {
            return false;
        }

        $document = $comment->document;
        if (! $document || $document->trashed()) {
            return false;
        }

        if ($user->can('comments.moderate')) {
            return true;
        }

        return $comment->user_id === $user->id && $user->can('comments.update');
    }

    /**
     * Determine whether the user can reply to the comment.
     */
    public function reply(User $user, DocumentComment $comment): bool
    {
        if ($user->organization_id !== $comment->organization_id) {
            return false;
        }

        $document = $comment->document;
        if (! $document || $document->trashed() || $document->status !== 'active') {
            return false;
        }

        if ($comment->trashed() || $comment->parent_id !== null) {
            return false;
        }

        if (! $user->can('comments.create')) {
            return false;
        }

        return Gate::forUser($user)->allows('view', $document);
    }
}
