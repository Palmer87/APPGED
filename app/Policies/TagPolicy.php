<?php

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;

class TagPolicy
{
    /**
     * Determine whether the user can view the tag.
     */
    public function view(User $user, Tag $tag): bool
    {
        if ($user->organization_id !== $tag->organization_id) {
            return false;
        }

        return $user->can('tags.view');
    }

    /**
     * Determine whether the user can create tags.
     */
    public function create(User $user): bool
    {
        return $user->can('tags.create');
    }

    /**
     * Determine whether the user can update the tag.
     */
    public function update(User $user, Tag $tag): bool
    {
        if ($user->organization_id !== $tag->organization_id) {
            return false;
        }

        return $user->can('tags.update');
    }

    /**
     * Determine whether the user can delete the tag.
     */
    public function delete(User $user, Tag $tag): bool
    {
        if ($user->organization_id !== $tag->organization_id) {
            return false;
        }

        return $user->can('tags.delete');
    }

    /**
     * Determine whether the user can restore the tag.
     */
    public function restore(User $user, Tag $tag): bool
    {
        if ($user->organization_id !== $tag->organization_id) {
            return false;
        }

        return $user->can('tags.update');
    }
}
