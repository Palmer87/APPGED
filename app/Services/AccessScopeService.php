<?php

namespace App\Services;

use App\Enums\AccessScopeType;
use App\Models\AccessScope;
use App\Models\Direction;
use App\Models\Document;
use App\Models\Folder;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class AccessScopeService
{
    public function __construct(
        protected ?AuditService $auditService = null
    ) {
        $this->auditService = $this->auditService ?? app(AuditService::class);
    }

    /**
     * Grant an access scope to a user.
     *
     * @param  array{direction_id?: ?int, service_id?: ?int, folder_id?: ?int, document_id?: ?int}  $targets
     */
    public function grantScope(User $user, string|AccessScopeType $scopeType, array $targets = [], ?User $actor = null): AccessScope
    {
        $type = is_string($scopeType) ? AccessScopeType::from($scopeType) : $scopeType;

        return DB::transaction(function () use ($user, $type, $targets, $actor) {
            $directionId = $targets['direction_id'] ?? null;
            $serviceId = $targets['service_id'] ?? null;
            $folderId = $targets['folder_id'] ?? null;
            $documentId = $targets['document_id'] ?? null;

            // Enforce tenant isolation for referenced targets
            if ($directionId) {
                Direction::where('organization_id', $user->organization_id)->findOrFail($directionId);
            }
            if ($serviceId) {
                Service::where('organization_id', $user->organization_id)->findOrFail($serviceId);
            }
            if ($folderId) {
                Folder::where('organization_id', $user->organization_id)->findOrFail($folderId);
            }
            if ($documentId) {
                Document::where('organization_id', $user->organization_id)->findOrFail($documentId);
            }

            $scope = AccessScope::create([
                'organization_id' => $user->organization_id,
                'user_id' => $user->id,
                'scope_type' => $type,
                'direction_id' => $directionId,
                'service_id' => $serviceId,
                'folder_id' => $folderId,
                'document_id' => $documentId,
                'is_active' => true,
            ]);

            $this->auditService->success(
                action: 'access_scope.granted',
                auditable: $scope,
                target: $user,
                newValues: [
                    'scope_type' => $type->value,
                    'direction_id' => $directionId,
                    'service_id' => $serviceId,
                    'folder_id' => $folderId,
                    'document_id' => $documentId,
                ],
                user: $actor,
                description: "Périmètre d'accès '{$type->label()}' octroyé à '{$user->name}'."
            );

            return $scope;
        });
    }

    /**
     * Revoke / Delete an access scope.
     */
    public function revokeScope(AccessScope $scope, ?User $actor = null): void
    {
        DB::transaction(function () use ($scope, $actor) {
            $user = $scope->user;
            $typeLabel = $scope->scope_type->label();

            $scope->delete();

            $this->auditService->success(
                action: 'access_scope.revoked',
                auditable: $scope,
                target: $user,
                user: $actor,
                description: "Périmètre d'accès '{$typeLabel}' révoqué pour '{$user?->name}'."
            );
        });
    }

    /**
     * Get all active access scopes for a user.
     *
     * @return Collection<int, AccessScope>
     */
    public function getUserScopes(User $user): Collection
    {
        return AccessScope::query()
            ->where('organization_id', $user->organization_id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->with(['direction', 'service', 'folder', 'document'])
            ->get();
    }

    /**
     * Check if a user has access to a specific scope type and optional target id.
     */
    public function canAccess(User $user, AccessScopeType|string $scopeType, ?int $targetId = null): bool
    {
        $type = is_string($scopeType) ? AccessScopeType::from($scopeType) : $scopeType;

        if ($user->organization_id) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($user->organization_id);
        }

        // 1. Super admin always has access
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // 2. Organization admin always has organization-wide access
        if ($user->hasRole('admin')) {
            return true;
        }

        $scopes = $this->getUserScopes($user);
        if ($scopes->isEmpty()) {
            return false;
        }

        // If user has organization-wide scope, they have access to everything
        if ($scopes->contains('scope_type', AccessScopeType::Organization)) {
            return true;
        }

        foreach ($scopes as $scope) {
            if ($scope->scope_type === $type) {
                if ($targetId === null) {
                    return true;
                }

                $matchedId = match ($type) {
                    AccessScopeType::Direction => $scope->direction_id,
                    AccessScopeType::Service => $scope->service_id,
                    AccessScopeType::Folder, AccessScopeType::DocumentType => $scope->folder_id,
                    AccessScopeType::Document => $scope->document_id,
                    default => null,
                };

                if ($matchedId === $targetId) {
                    return true;
                }
            }

            // Direction scope implies access to its services
            if ($scope->scope_type === AccessScopeType::Direction && $type === AccessScopeType::Service && $targetId) {
                $service = Service::find($targetId);
                if ($service && $service->direction_id === $scope->direction_id) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if a resource falls within any of the user's active access scopes.
     */
    public function hasScopeAccess(User $user, Document|Folder $resource): bool
    {
        if ($user->organization_id) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($user->organization_id);
        }

        // 1. Super admin always has access
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // 2. Organization admin always has organization-wide access
        if ($user->hasRole('admin')) {
            return $user->organization_id === $resource->organization_id;
        }

        // 3. Strict tenant check
        if ($user->organization_id !== $resource->organization_id) {
            return false;
        }

        $scopes = $this->getUserScopes($user);
        if ($scopes->isEmpty()) {
            return false;
        }

        // Resolve resource coordinates
        $resDirectionId = null;
        $resServiceId = null;
        $resFolderId = null;
        $resDocTypeId = null;

        if ($resource instanceof Document) {
            $resDirectionId = $resource->direction_id ?? $resource->getDepartment()?->direction_id ?? $resource->getDepartment()?->id;
            $resServiceId = $resource->service_id ?? $resource->getService()?->service_id ?? $resource->getService()?->id;
            $resFolderId = $resource->folder_id;
            $resDocTypeId = $resource->document_type_id;
        } else {
            // Folder
            $resDirectionId = $resource->direction_id ?? ($resource->isDepartment() ? $resource->id : $resource->getDepartment()?->id);
            $resServiceId = $resource->service_id ?? ($resource->isService() ? $resource->id : $resource->getService()?->id);
            $resFolderId = $resource->id;
            $resDocTypeId = $resource->isDocumentType() ? $resource->id : null;
        }

        foreach ($scopes as $scope) {
            switch ($scope->scope_type) {
                case AccessScopeType::Organization:
                    return true;

                case AccessScopeType::Direction:
                    if ($resDirectionId && $scope->direction_id == $resDirectionId) {
                        return true;
                    }
                    if ($resFolderId && $scope->direction?->folder_id && $scope->direction->folder_id == $resFolderId) {
                        return true;
                    }
                    break;

                case AccessScopeType::Service:
                    if ($resServiceId && $scope->service_id == $resServiceId) {
                        return true;
                    }
                    if ($resFolderId && $scope->service?->folder_id && $scope->service->folder_id == $resFolderId) {
                        return true;
                    }
                    break;

                case AccessScopeType::DocumentType:
                    if ($resDocTypeId && $scope->folder_id == $resDocTypeId) {
                        return true;
                    }
                    break;

                case AccessScopeType::Folder:
                    if ($resFolderId && $scope->folder_id == $resFolderId) {
                        return true;
                    }
                    break;

                case AccessScopeType::Document:
                    if ($resource instanceof Document && $scope->document_id == $resource->id) {
                        return true;
                    }
                    break;
            }
        }

        return false;
    }

    /**
     * Apply access scope filtering directly to an Eloquent Document query.
     */
    public function applyScopeToQuery(Builder $query, User $user): Builder
    {
        if ($user->hasRole('super-admin')) {
            return $query;
        }

        $query->where('documents.organization_id', $user->organization_id);

        if ($user->hasRole('admin')) {
            return $query;
        }

        $scopes = $this->getUserScopes($user);
        if ($scopes->isEmpty()) {
            return $query;
        }

        // Check if user has organization-wide scope
        if ($scopes->contains('scope_type', AccessScopeType::Organization)) {
            return $query;
        }

        $directionIds = $scopes->where('scope_type', AccessScopeType::Direction)->pluck('direction_id')->filter()->all();
        $directionFolderIds = Direction::whereIn('id', $directionIds)->pluck('folder_id')->filter()->all();
        $allDirectionRefIds = array_unique(array_merge($directionIds, $directionFolderIds));

        $serviceIds = $scopes->where('scope_type', AccessScopeType::Service)->pluck('service_id')->filter()->all();
        $serviceFolderIds = Service::whereIn('id', $serviceIds)->pluck('folder_id')->filter()->all();
        $allServiceRefIds = array_unique(array_merge($serviceIds, $serviceFolderIds));

        $folderIds = $scopes->whereIn('scope_type', [AccessScopeType::Folder, AccessScopeType::DocumentType])->pluck('folder_id')->filter()->all();
        $docIds = $scopes->where('scope_type', AccessScopeType::Document)->pluck('document_id')->filter()->all();

        return $query->where(function (Builder $sub) use ($allDirectionRefIds, $allServiceRefIds, $folderIds, $docIds) {
            if (! empty($allDirectionRefIds)) {
                $sub->orWhereIn('documents.direction_id', $allDirectionRefIds);
            }

            if (! empty($allServiceRefIds)) {
                $sub->orWhereIn('documents.service_id', $allServiceRefIds);
            }

            if (! empty($folderIds)) {
                $sub->orWhereIn('documents.folder_id', $folderIds)
                    ->orWhereIn('documents.document_type_id', $folderIds);
            }

            if (! empty($docIds)) {
                $sub->orWhereIn('documents.id', $docIds);
            }
        });
    }
}
