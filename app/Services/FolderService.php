<?php

namespace App\Services;

use App\Models\Folder;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

class FolderService
{
    public function __construct(
        protected ?AuditService $auditService = null
    ) {
        $this->auditService = $this->auditService ?? app(AuditService::class);
    }

    /**
     * Create a new folder.
     *
     * @param  array  $data  ['name' => string, 'description' => ?string, 'parent_id' => ?int]
     * @param  User  $user  Authenticated user
     */
    public function create(array $data, User $user): Folder
    {
        // Ensure folder belongs to user's organization
        $data['organization_id'] = $user->organization_id;

        // Validate parent if given
        if (! empty($data['parent_id'])) {
            $parent = Folder::findOrFail($data['parent_id']);
            if ($parent->organization_id !== $user->organization_id) {
                throw new ModelNotFoundException('Parent folder belongs to another organization');
            }
        }

        $data['created_by'] = $user->id;
        $folder = Folder::create($data);
        $this->updatePath($folder);

        $this->auditService->success(
            action: 'folder.created',
            auditable: $folder,
            newValues: [
                'name' => $folder->name,
                'parent_id' => $folder->parent_id,
                'path' => $folder->path,
            ],
            user: $user,
            description: "Folder '{$folder->name}' created."
        );

        return $folder;
    }

    /**
     * Move a folder under a new parent.
     */
    public function move(Folder $folder, ?Folder $newParent): void
    {
        $oldParentId = $folder->parent_id;
        $oldPath = $folder->path;

        $this->validateParent($folder, $newParent);
        $folder->parent_id = $newParent ? $newParent->id : null;
        $folder->save();
        $this->updatePath($folder);

        $this->auditService->success(
            action: 'folder.updated',
            auditable: $folder,
            oldValues: ['parent_id' => $oldParentId, 'path' => $oldPath],
            newValues: ['parent_id' => $folder->parent_id, 'path' => $folder->path],
            description: "Folder '{$folder->name}' moved to new parent."
        );
    }

    /**
     * Validate parent relationship (same organization, no cycles).
     */
    public function validateParent(Folder $folder, ?Folder $parent): void
    {
        if ($parent) {
            if ($parent->organization_id !== $folder->organization_id) {
                throw new ModelNotFoundException('Parent belongs to another organization');
            }
            // Prevent self‑parenting
            if ($parent->id === $folder->id) {
                throw new QueryException('Folder cannot be its own parent');
            }
            // Detect cycles
            $ancestor = $parent->parent;
            while ($ancestor) {
                if ($ancestor->id === $folder->id) {
                    throw new QueryException('Cyclic folder hierarchy detected');
                }
                $ancestor = $ancestor->parent;
            }
        }
    }

    /**
     * Soft delete a folder.
     */
    public function delete(Folder $folder): void
    {
        $folder->delete();

        $this->auditService->success(
            action: 'folder.deleted',
            auditable: $folder,
            oldValues: ['deleted_at' => null],
            newValues: ['deleted_at' => $folder->deleted_at?->toISOString()],
            description: "Folder '{$folder->name}' moved to trash."
        );
    }

    /**
     * Restore a soft‑deleted folder.
     */
    public function restore(Folder $folder): void
    {
        $folder->restore();

        $this->auditService->success(
            action: 'folder.restored',
            auditable: $folder,
            oldValues: ['deleted_at' => 'trashed'],
            newValues: ['deleted_at' => null],
            description: "Folder '{$folder->name}' restored from trash."
        );
    }

    /**
     * Recalculate and store the folder's path.
     */
    protected function updatePath(Folder $folder): void
    {
        $segments = [];
        $current = $folder;
        while ($current) {
            array_unshift($segments, $current->name);
            $current = $current->parent;
        }
        $folder->path = implode('/', $segments);
        $folder->save();
    }
}
