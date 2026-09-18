<?php

namespace App\Services;

use App\Enums\FolderType;
use App\Models\Document;
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
     * @param  array  $data  ['name' => string, 'description' => ?string, 'parent_id' => ?int, 'folder_type' => ?FolderType|string]
     * @param  User  $user  Authenticated user
     */
    public function create(array $data, User $user): Folder
    {
        // Ensure folder belongs to user's organization
        $data['organization_id'] = $user->organization_id;

        // Normalize folder_type
        if (isset($data['folder_type']) && is_string($data['folder_type'])) {
            $data['folder_type'] = FolderType::tryFrom($data['folder_type']) ?? FolderType::Standard;
        } else {
            $data['folder_type'] = $data['folder_type'] ?? FolderType::Standard;
        }

        // Validate department constraints: must have parent_id = null
        if ($data['folder_type'] === FolderType::Department) {
            $data['parent_id'] = null;
        }

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

        $action = match ($folder->folder_type) {
            FolderType::Department => 'direction.created',
            FolderType::DocumentType => 'document_type.created',
            default => 'folder.created',
        };

        $this->auditService->success(
            action: $action,
            auditable: $folder,
            newValues: [
                'name' => $folder->name,
                'parent_id' => $folder->parent_id,
                'folder_type' => $folder->folder_type?->value,
                'path' => $folder->path,
            ],
            user: $user,
            description: "{$folder->folder_type->label()} '{$folder->name}' created."
        );

        return $folder;
    }

    /**
     * Create a department (Direction).
     */
    public function createDepartment(array $data, User $user): Folder
    {
        $data['folder_type'] = FolderType::Department;
        $data['parent_id'] = null;

        return $this->create($data, $user);
    }

    /**
     * Create a document type (Type documentaire).
     */
    public function createDocumentType(array $data, User $user): Folder
    {
        $data['folder_type'] = FolderType::DocumentType;

        return $this->create($data, $user);
    }

    /**
     * Update an existing folder.
     */
    public function update(Folder $folder, array $data, ?User $user = null): Folder
    {
        $oldValues = [
            'name' => $folder->name,
            'description' => $folder->description,
            'parent_id' => $folder->parent_id,
            'is_active' => $folder->is_active,
        ];

        if (isset($data['name'])) {
            $folder->name = $data['name'];
        }

        if (array_key_exists('description', $data)) {
            $folder->description = $data['description'];
        }

        if (array_key_exists('is_active', $data)) {
            $folder->is_active = (bool) $data['is_active'];
        }

        if (array_key_exists('parent_id', $data) && $folder->folder_type !== FolderType::Department) {
            $newParentId = $data['parent_id'] ? (int) $data['parent_id'] : null;
            if ($newParentId !== $folder->parent_id) {
                $newParent = $newParentId ? Folder::findOrFail($newParentId) : null;
                $this->validateParent($folder, $newParent);
                $folder->parent_id = $newParentId;
            }
        }

        $folder->save();
        $this->updatePath($folder);

        $action = match ($folder->folder_type) {
            FolderType::Department => 'direction.updated',
            FolderType::DocumentType => 'document_type.updated',
            default => 'folder.updated',
        };

        $this->auditService->success(
            action: $action,
            auditable: $folder,
            oldValues: $oldValues,
            newValues: [
                'name' => $folder->name,
                'description' => $folder->description,
                'parent_id' => $folder->parent_id,
                'is_active' => $folder->is_active,
            ],
            user: $user,
            description: "{$folder->folder_type->label()} '{$folder->name}' updated."
        );

        return $folder;
    }

    /**
     * Associate / Sync metadata definitions with a document type folder.
     *
     * @param  array<int, array{id: int, is_required?: bool|null, order?: int}>|array<int, int>  $definitions
     */
    public function syncMetadataDefinitions(Folder $folder, array $definitions, ?User $user = null): void
    {
        $syncData = [];
        $order = 0;

        foreach ($definitions as $item) {
            $order++;
            if (is_array($item)) {
                $defId = $item['id'];
                $syncData[$defId] = [
                    'is_required' => $item['is_required'] ?? null,
                    'order' => $item['order'] ?? $order,
                ];
            } else {
                $syncData[(int) $item] = [
                    'is_required' => null,
                    'order' => $order,
                ];
            }
        }

        $folder->metadataDefinitions()->sync($syncData);

        $this->auditService->success(
            action: 'document_type.metadata_updated',
            auditable: $folder,
            newValues: ['definitions_count' => count($syncData), 'definition_ids' => array_keys($syncData)],
            user: $user,
            description: "Metadata definitions updated for document type '{$folder->name}'."
        );
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
     * Check if folder has documents directly or in subfolders.
     */
    public function hasDocuments(Folder $folder): bool
    {
        if ($folder->documents()->exists() || $folder->typedDocuments()->exists()) {
            return true;
        }

        foreach ($folder->children as $child) {
            if ($this->hasDocuments($child)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Soft delete a folder safely.
     *
     * @param  bool  $force  If true, allows delete even if empty check fails (caller verified)
     */
    public function delete(Folder $folder, bool $force = false): void
    {
        if (! $force && $this->hasDocuments($folder)) {
            abort(422, 'Ce dossier ou ses sous-dossiers contiennent des documents. Veuillez les déplacer ou désactiver le dossier.');
        }

        $action = match ($folder->folder_type) {
            FolderType::Department => 'direction.deleted',
            FolderType::DocumentType => 'document_type.deleted',
            default => 'folder.deleted',
        };

        $folder->delete();

        $this->auditService->success(
            action: $action,
            auditable: $folder,
            oldValues: ['deleted_at' => null],
            newValues: ['deleted_at' => $folder->deleted_at?->toISOString()],
            description: "{$folder->folder_type->label()} '{$folder->name}' moved to trash."
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
    public function updatePath(Folder $folder): void
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
