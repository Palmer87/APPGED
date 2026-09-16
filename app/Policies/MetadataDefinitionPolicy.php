<?php

namespace App\Policies;

use App\Models\MetadataDefinition;
use App\Models\User;

class MetadataDefinitionPolicy
{
    /**
     * Determine whether the user can view the metadata definition.
     */
    public function view(User $user, MetadataDefinition $definition): bool
    {
        if ($user->organization_id !== $definition->organization_id) {
            return false;
        }

        return $user->can('metadata.view');
    }

    /**
     * Determine whether the user can create metadata definitions.
     */
    public function create(User $user): bool
    {
        return $user->can('metadata.create');
    }

    /**
     * Determine whether the user can update the metadata definition.
     */
    public function update(User $user, MetadataDefinition $definition): bool
    {
        if ($user->organization_id !== $definition->organization_id) {
            return false;
        }

        return $user->can('metadata.update');
    }

    /**
     * Determine whether the user can delete the metadata definition.
     */
    public function delete(User $user, MetadataDefinition $definition): bool
    {
        if ($user->organization_id !== $definition->organization_id) {
            return false;
        }

        return $user->can('metadata.delete');
    }

    /**
     * Determine whether the user can restore the metadata definition.
     */
    public function restore(User $user, MetadataDefinition $definition): bool
    {
        if ($user->organization_id !== $definition->organization_id) {
            return false;
        }

        return $user->can('metadata.update');
    }
}
