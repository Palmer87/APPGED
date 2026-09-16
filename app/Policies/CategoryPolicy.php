<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    /**
     * Determine whether the user can view the category.
     */
    public function view(User $user, Category $category): bool
    {
        if ($user->organization_id !== $category->organization_id) {
            return false;
        }

        return $user->can('categories.view');
    }

    /**
     * Determine whether the user can create categories.
     */
    public function create(User $user): bool
    {
        return $user->can('categories.create');
    }

    /**
     * Determine whether the user can update the category.
     */
    public function update(User $user, Category $category): bool
    {
        if ($user->organization_id !== $category->organization_id) {
            return false;
        }

        return $user->can('categories.update');
    }

    /**
     * Determine whether the user can delete the category.
     */
    public function delete(User $user, Category $category): bool
    {
        if ($user->organization_id !== $category->organization_id) {
            return false;
        }

        return $user->can('categories.delete');
    }

    /**
     * Determine whether the user can restore the category.
     */
    public function restore(User $user, Category $category): bool
    {
        if ($user->organization_id !== $category->organization_id) {
            return false;
        }

        return $user->can('categories.update');
    }
}
