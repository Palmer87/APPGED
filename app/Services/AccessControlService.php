<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentPermission;
use App\Models\DocumentShare;
use App\Models\Folder;
use App\Models\FolderPermission;
use App\Models\Group;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AccessControlService
{
    public function __construct(
        protected ?AuditService $auditService = null
    ) {
        $this->auditService = $this->auditService ?? app(AuditService::class);
    }

    /**
     * Allowed permissions for folders.
     */
    public const ALLOWED_FOLDER_PERMISSIONS = ['view', 'create', 'update', 'delete', 'share'];

    /**
     * Allowed permissions for documents.
     */
    public const ALLOWED_DOCUMENT_PERMISSIONS = ['view', 'create', 'update', 'delete', 'download', 'share', 'archive', 'restore'];

    /**
     * Normalize folder permission string.
     */
    public function normalizeFolderPermission(string $permission): string
    {
        $perm = str_starts_with($permission, 'folders.') ? substr($permission, 8) : $permission;

        if (! in_array($perm, self::ALLOWED_FOLDER_PERMISSIONS, true)) {
            abort(422, "Invalid folder permission '{$permission}'");
        }

        return $perm;
    }

    /**
     * Normalize document permission string.
     */
    public function normalizeDocumentPermission(string $permission): string
    {
        $perm = str_starts_with($permission, 'documents.') ? substr($permission, 10) : $permission;

        if (! in_array($perm, self::ALLOWED_DOCUMENT_PERMISSIONS, true)) {
            abort(422, "Invalid document permission '{$permission}'");
        }

        return $perm;
    }

    /**
     * Grant permission on a folder to a user.
     */
    public function grantFolderPermission(Folder $folder, User $user, string $permission): FolderPermission
    {
        if ($folder->organization_id !== $user->organization_id) {
            abort(403, 'User belongs to a different organization');
        }

        $perm = $this->normalizeFolderPermission($permission);

        $record = FolderPermission::firstOrCreate([
            'folder_id' => $folder->id,
            'user_id' => $user->id,
            'permission' => $perm,
        ]);

        $this->auditService->success(
            action: 'folder.permission_granted',
            auditable: $folder,
            target: $user,
            newValues: [
                'target_type' => 'user',
                'target_id' => $user->id,
                'target_name' => $user->name,
                'permission' => $perm,
            ],
            description: "Permission '{$perm}' granted to user '{$user->name}' on folder '{$folder->name}'."
        );

        return $record;
    }

    /**
     * Revoke permission on a folder from a user (idempotent).
     */
    public function revokeFolderPermission(Folder $folder, User $user, string $permission): void
    {
        if ($folder->organization_id !== $user->organization_id) {
            abort(403, 'User belongs to a different organization');
        }

        $perm = $this->normalizeFolderPermission($permission);

        FolderPermission::where('folder_id', $folder->id)
            ->where('user_id', $user->id)
            ->where('permission', $perm)
            ->delete();

        $this->auditService->success(
            action: 'folder.permission_revoked',
            auditable: $folder,
            target: $user,
            metadata: [
                'target_type' => 'user',
                'target_id' => $user->id,
                'permission' => $perm,
            ],
            description: "Permission '{$perm}' revoked from user '{$user->name}' on folder '{$folder->name}'."
        );
    }

    /**
     * Grant permission on a folder to a group.
     */
    public function grantFolderPermissionToGroup(Folder $folder, Group $group, string $permission): FolderPermission
    {
        if ($folder->organization_id !== $group->organization_id) {
            abort(403, 'Group belongs to a different organization');
        }

        $perm = $this->normalizeFolderPermission($permission);

        $record = FolderPermission::firstOrCreate([
            'folder_id' => $folder->id,
            'group_id' => $group->id,
            'permission' => $perm,
        ]);

        $this->auditService->success(
            action: 'folder.permission_granted',
            auditable: $folder,
            target: $group,
            newValues: [
                'target_type' => 'group',
                'target_id' => $group->id,
                'target_name' => $group->name,
                'permission' => $perm,
            ],
            description: "Permission '{$perm}' granted to group '{$group->name}' on folder '{$folder->name}'."
        );

        return $record;
    }

    /**
     * Revoke permission on a folder from a group (idempotent).
     */
    public function revokeFolderPermissionFromGroup(Folder $folder, Group $group, string $permission): void
    {
        if ($folder->organization_id !== $group->organization_id) {
            abort(403, 'Group belongs to a different organization');
        }

        $perm = $this->normalizeFolderPermission($permission);

        FolderPermission::where('folder_id', $folder->id)
            ->where('group_id', $group->id)
            ->where('permission', $perm)
            ->delete();

        $this->auditService->success(
            action: 'folder.permission_revoked',
            auditable: $folder,
            target: $group,
            metadata: [
                'target_type' => 'group',
                'target_id' => $group->id,
                'permission' => $perm,
            ],
            description: "Permission '{$perm}' revoked from group '{$group->name}' on folder '{$folder->name}'."
        );
    }

    /**
     * Grant permission on a document to a user.
     */
    public function grantDocumentPermission(Document $document, User $user, string $permission): DocumentPermission
    {
        if ($document->organization_id !== $user->organization_id) {
            abort(403, 'User belongs to a different organization');
        }

        $perm = $this->normalizeDocumentPermission($permission);

        $record = DocumentPermission::firstOrCreate([
            'document_id' => $document->id,
            'user_id' => $user->id,
            'permission' => $perm,
        ]);

        $this->auditService->success(
            action: 'document.permission_granted',
            auditable: $document,
            target: $user,
            newValues: [
                'target_type' => 'user',
                'target_id' => $user->id,
                'target_name' => $user->name,
                'permission' => $perm,
            ],
            description: "Permission '{$perm}' granted to user '{$user->name}' on document '{$document->name}'."
        );

        return $record;
    }

    /**
     * Revoke permission on a document from a user (idempotent).
     */
    public function revokeDocumentPermission(Document $document, User $user, string $permission): void
    {
        if ($document->organization_id !== $user->organization_id) {
            abort(403, 'User belongs to a different organization');
        }

        $perm = $this->normalizeDocumentPermission($permission);

        DocumentPermission::where('document_id', $document->id)
            ->where('user_id', $user->id)
            ->where('permission', $perm)
            ->delete();

        $this->auditService->success(
            action: 'document.permission_revoked',
            auditable: $document,
            target: $user,
            metadata: [
                'target_type' => 'user',
                'target_id' => $user->id,
                'permission' => $perm,
            ],
            description: "Permission '{$perm}' revoked from user '{$user->name}' on document '{$document->name}'."
        );
    }

    /**
     * Grant permission on a document to a group.
     */
    public function grantDocumentPermissionToGroup(Document $document, Group $group, string $permission): DocumentPermission
    {
        if ($document->organization_id !== $group->organization_id) {
            abort(403, 'Group belongs to a different organization');
        }

        $perm = $this->normalizeDocumentPermission($permission);

        $record = DocumentPermission::firstOrCreate([
            'document_id' => $document->id,
            'group_id' => $group->id,
            'permission' => $perm,
        ]);

        $this->auditService->success(
            action: 'document.permission_granted',
            auditable: $document,
            target: $group,
            newValues: [
                'target_type' => 'group',
                'target_id' => $group->id,
                'target_name' => $group->name,
                'permission' => $perm,
            ],
            description: "Permission '{$perm}' granted to group '{$group->name}' on document '{$document->name}'."
        );

        return $record;
    }

    /**
     * Revoke permission on a document from a group (idempotent).
     */
    public function revokeDocumentPermissionFromGroup(Document $document, Group $group, string $permission): void
    {
        if ($document->organization_id !== $group->organization_id) {
            abort(403, 'Group belongs to a different organization');
        }

        $perm = $this->normalizeDocumentPermission($permission);

        DocumentPermission::where('document_id', $document->id)
            ->where('group_id', $group->id)
            ->where('permission', $perm)
            ->delete();

        $this->auditService->success(
            action: 'document.permission_revoked',
            auditable: $document,
            target: $group,
            metadata: [
                'target_type' => 'group',
                'target_id' => $group->id,
                'permission' => $perm,
            ],
            description: "Permission '{$perm}' revoked from group '{$group->name}' on document '{$document->name}'."
        );
    }

    /**
     * Check if a user can access a folder for a given permission.
     */
    public function canAccessFolder(User $user, Folder $folder, string $permission): bool
    {
        // 1. Super-admin global access
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // 2. Tenant isolation
        if ($user->organization_id !== $folder->organization_id) {
            return false;
        }

        // 3. Soft deleted check
        if ($folder->trashed()) {
            return false;
        }

        $perm = $this->normalizeFolderPermission($permission);

        // 4. Direct user ACL on folder
        $hasUserAcl = FolderPermission::where('folder_id', $folder->id)
            ->where('user_id', $user->id)
            ->where('permission', $perm)
            ->exists();

        if ($hasUserAcl) {
            return true;
        }

        // 5. Group ACL on folder
        $userGroupIds = $user->groups()->pluck('groups.id');
        if ($userGroupIds->isNotEmpty()) {
            $hasGroupAcl = FolderPermission::where('folder_id', $folder->id)
                ->whereIn('group_id', $userGroupIds)
                ->where('permission', $perm)
                ->exists();

            if ($hasGroupAcl) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a user can access a document for a given permission.
     */
    public function canAccessDocument(User $user, Document $document, string $permission): bool
    {
        // 1. Super-admin global access
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // 2. Tenant isolation
        if ($user->organization_id !== $document->organization_id) {
            return false;
        }

        // 3. Soft deleted check
        if ($document->trashed()) {
            return false;
        }

        $perm = $this->normalizeDocumentPermission($permission);

        // 4. Direct user ACL on document
        $hasUserAcl = DocumentPermission::where('document_id', $document->id)
            ->where('user_id', $user->id)
            ->where('permission', $perm)
            ->exists();

        if ($hasUserAcl) {
            return true;
        }

        // 5. Group ACL on document
        $userGroupIds = $user->groups()->pluck('groups.id');
        if ($userGroupIds->isNotEmpty()) {
            $hasGroupAcl = DocumentPermission::where('document_id', $document->id)
                ->whereIn('group_id', $userGroupIds)
                ->where('permission', $perm)
                ->exists();

            if ($hasGroupAcl) {
                return true;
            }
        }

        // 6. Direct folder inheritance
        if ($document->folder_id) {
            $folder = $document->folder ?? Folder::find($document->folder_id);
            if ($folder && ! $folder->trashed()) {
                $folderEquivalentPerm = match ($perm) {
                    'download' => 'view',
                    'archive', 'restore' => 'update',
                    default => in_array($perm, self::ALLOWED_FOLDER_PERMISSIONS, true) ? $perm : null,
                };

                if ($folderEquivalentPerm !== null && $this->canAccessFolder($user, $folder, $folderEquivalentPerm)) {
                    return true;
                }
            }
        }

        // 7. Active Document Share (user or group)
        if (in_array($perm, ['view', 'download'], true)) {
            $shareQuery = DocumentShare::where('document_id', $document->id)->active();

            if ($perm === 'view') {
                $shareQuery->whereIn('permission', ['view', 'download']);
            } else {
                $shareQuery->where('permission', 'download');
            }

            $hasUserShare = (clone $shareQuery)->where('user_id', $user->id)->exists();
            if ($hasUserShare) {
                return true;
            }

            if ($userGroupIds->isNotEmpty()) {
                $hasGroupShare = (clone $shareQuery)->whereIn('group_id', $userGroupIds)->exists();
                if ($hasGroupShare) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if a document or folder has any ACL defined.
     */
    public function hasAcl(Document|Folder $resource): bool
    {
        if ($resource instanceof Document) {
            $hasDocAcl = DocumentPermission::where('document_id', $resource->id)->exists();
            if ($hasDocAcl) {
                return true;
            }

            if ($resource->folder_id) {
                return FolderPermission::where('folder_id', $resource->folder_id)->exists();
            }

            return false;
        }

        return FolderPermission::where('folder_id', $resource->id)->exists();
    }

    /**
     * Get all ACL permissions for a document.
     *
     * @return Collection<int, DocumentPermission>
     */
    public function getDocumentPermissions(Document $document): Collection
    {
        return $document->permissions()->with(['user', 'group'])->get();
    }

    /**
     * Get all ACL permissions for a folder.
     *
     * @return Collection<int, FolderPermission>
     */
    public function getFolderPermissions(Folder $folder): Collection
    {
        return $folder->permissions()->with(['user', 'group'])->get();
    }

    /**
     * Apply access control scope directly to an Eloquent document query.
     *
     * 1. Super-admin: global access (all documents).
     * 2. Non-super-admin: strictly limited to user's organization.
     * 3. Requires 'view' permission via:
     *    - Direct user ACL on document, OR
     *    - Direct group ACL on document, OR
     *    - Inherited view ACL from parent folder (folder not trashed).
     */
    public function applyAccessScope(Builder $query, User $user): Builder
    {
        if ($user->hasRole('super-admin')) {
            return $query;
        }

        // 1. Strict tenant isolation
        $query->where('documents.organization_id', $user->organization_id);

        // Admins have organization-wide document access
        if ($user->hasRole('admin')) {
            return $query;
        }

        // 2. Fetch user's group IDs
        $userGroupIds = $user->groups()->pluck('groups.id');

        // 3. Document must be accessible with 'view' permission
        return $query->where(function (Builder $sub) use ($user, $userGroupIds) {
            // Uploaded by user
            $sub->where('documents.uploaded_by', $user->id);

            // Direct user ACL on document
            $sub->orWhereExists(function ($permQuery) use ($user) {
                $permQuery->select(DB::raw(1))
                    ->from('document_permissions')
                    ->whereColumn('document_permissions.document_id', 'documents.id')
                    ->where('document_permissions.user_id', $user->id)
                    ->where('document_permissions.permission', 'view');
            });

            // Group ACL on document
            if ($userGroupIds->isNotEmpty()) {
                $sub->orWhereExists(function ($permQuery) use ($userGroupIds) {
                    $permQuery->select(DB::raw(1))
                        ->from('document_permissions')
                        ->whereColumn('document_permissions.document_id', 'documents.id')
                        ->whereIn('document_permissions.group_id', $userGroupIds)
                        ->where('document_permissions.permission', 'view');
                });
            }

            // Folder inheritance (folder must not be trashed)
            $sub->orWhere(function (Builder $folderSub) use ($user, $userGroupIds) {
                $folderSub->whereNotNull('documents.folder_id')
                    ->whereExists(function ($fpQuery) use ($user, $userGroupIds) {
                        $fpQuery->select(DB::raw(1))
                            ->from('folder_permissions')
                            ->join('folders', 'folders.id', '=', 'folder_permissions.folder_id')
                            ->whereColumn('folder_permissions.folder_id', 'documents.folder_id')
                            ->whereNull('folders.deleted_at')
                            ->where('folder_permissions.permission', 'view')
                            ->where(function ($ownerSub) use ($user, $userGroupIds) {
                                $ownerSub->where('folder_permissions.user_id', $user->id);
                                if ($userGroupIds->isNotEmpty()) {
                                    $ownerSub->orWhereIn('folder_permissions.group_id', $userGroupIds);
                                }
                            });
                    });
            });

            // 4. Active user DocumentShare
            $sub->orWhereExists(function ($shareQuery) use ($user) {
                $shareQuery->select(DB::raw(1))
                    ->from('document_shares')
                    ->whereColumn('document_shares.document_id', 'documents.id')
                    ->where('document_shares.user_id', $user->id)
                    ->whereNull('document_shares.revoked_at')
                    ->where(function ($eq) {
                        $eq->whereNull('document_shares.expires_at')
                            ->orWhere('document_shares.expires_at', '>', Carbon::now());
                    });
            });

            // 5. Active group DocumentShare
            if ($userGroupIds->isNotEmpty()) {
                $sub->orWhereExists(function ($shareQuery) use ($userGroupIds) {
                    $shareQuery->select(DB::raw(1))
                        ->from('document_shares')
                        ->whereColumn('document_shares.document_id', 'documents.id')
                        ->whereIn('document_shares.group_id', $userGroupIds)
                        ->whereNull('document_shares.revoked_at')
                        ->where(function ($eq) {
                            $eq->whereNull('document_shares.expires_at')
                                ->orWhere('document_shares.expires_at', '>', Carbon::now());
                        });
                });
            }
        });
    }
}
