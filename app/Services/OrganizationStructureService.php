<?php

namespace App\Services;

use App\Enums\FolderType;
use App\Models\AccessScope;
use App\Models\Direction;
use App\Models\Folder;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class OrganizationStructureService
{
    public function __construct(
        protected ?AuditService $auditService = null,
        protected ?FolderService $folderService = null
    ) {
        $this->auditService = $this->auditService ?? app(AuditService::class);
        $this->folderService = $this->folderService ?? app(FolderService::class);
    }

    /**
     * Create a new Direction under the organization.
     *
     * @param  array{name: string, code?: ?string, description?: ?string, is_active?: bool}  $data
     */
    public function createDirection(array $data, User $creator): Direction
    {
        return DB::transaction(function () use ($data, $creator) {
            $orgId = $creator->organization_id;

            // 1. Create or sync root Department folder
            $folder = Folder::create([
                'organization_id' => $orgId,
                'parent_id' => null,
                'folder_type' => FolderType::Department,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'path' => $data['name'],
                'created_by' => $creator->id,
                'is_active' => $data['is_active'] ?? true,
            ]);

            // 2. Create Direction model
            $direction = Direction::create([
                'organization_id' => $orgId,
                'name' => $data['name'],
                'code' => $data['code'] ?? null,
                'description' => $data['description'] ?? null,
                'folder_id' => $folder->id,
                'is_active' => $data['is_active'] ?? true,
            ]);

            // Link folder to direction
            $folder->update(['direction_id' => $direction->id]);

            // 3. Audit log
            $this->auditService->success(
                action: 'direction.created',
                auditable: $direction,
                newValues: [
                    'name' => $direction->name,
                    'code' => $direction->code,
                    'folder_id' => $folder->id,
                ],
                user: $creator,
                description: "Direction '{$direction->name}' créée avec succès."
            );

            return $direction;
        });
    }

    /**
     * Update an existing Direction.
     *
     * @param  array{name?: string, code?: ?string, description?: ?string, is_active?: bool}  $data
     */
    public function updateDirection(Direction $direction, array $data, ?User $user = null): Direction
    {
        return DB::transaction(function () use ($direction, $data, $user) {
            $oldValues = $direction->only(['name', 'code', 'description', 'is_active']);

            $direction->update($data);

            // Sync with associated folder
            if ($direction->folder_id) {
                $folder = Folder::find($direction->folder_id);
                if ($folder) {
                    $folderUpdates = [];
                    if (isset($data['name'])) {
                        $folderUpdates['name'] = $data['name'];
                        $folderUpdates['path'] = $data['name'];
                    }
                    if (array_key_exists('description', $data)) {
                        $folderUpdates['description'] = $data['description'];
                    }
                    if (array_key_exists('is_active', $data)) {
                        $folderUpdates['is_active'] = (bool) $data['is_active'];
                    }
                    if (! empty($folderUpdates)) {
                        $folder->update($folderUpdates);
                    }
                }
            }

            $this->auditService->success(
                action: 'direction.updated',
                auditable: $direction,
                oldValues: $oldValues,
                newValues: $direction->only(['name', 'code', 'description', 'is_active']),
                user: $user,
                description: "Direction '{$direction->name}' mise à jour."
            );

            return $direction;
        });
    }

    /**
     * Delete a Direction.
     */
    public function deleteDirection(Direction $direction, ?User $user = null): bool
    {
        return DB::transaction(function () use ($direction, $user) {
            $name = $direction->name;

            // Cascade soft-delete child services cleanly
            $services = Service::where('direction_id', $direction->id)->get();
            foreach ($services as $service) {
                $this->deleteService($service, $user);
            }

            // Deactivate access scopes referencing this direction
            AccessScope::where('direction_id', $direction->id)->update(['is_active' => false]);

            // Soft-delete linked folder if exists
            if ($direction->folder_id) {
                Folder::where('id', $direction->folder_id)->delete();
            }

            $direction->delete();

            $this->auditService->success(
                action: 'direction.deleted',
                auditable: $direction,
                user: $user,
                description: "Direction '{$name}' supprimée."
            );

            return true;
        });
    }

    /**
     * Create a Service belonging to a Direction.
     *
     * @param  array{direction_id: int, name: string, code?: ?string, description?: ?string, is_active?: bool}  $data
     */
    public function createService(array $data, User $creator): Service
    {
        return DB::transaction(function () use ($data, $creator) {
            $direction = Direction::where('organization_id', $creator->organization_id)
                ->findOrFail($data['direction_id']);

            // 1. Create or sync Service folder under Direction's folder
            $parentFolderId = $direction->folder_id;
            $path = ($direction->folder?->path ?? $direction->name).'/'.$data['name'];

            $folder = Folder::create([
                'organization_id' => $creator->organization_id,
                'direction_id' => $direction->id,
                'parent_id' => $parentFolderId,
                'folder_type' => FolderType::Service,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'path' => $path,
                'created_by' => $creator->id,
                'is_active' => $data['is_active'] ?? true,
            ]);

            // 2. Create Service model
            $service = Service::create([
                'organization_id' => $creator->organization_id,
                'direction_id' => $direction->id,
                'name' => $data['name'],
                'code' => $data['code'] ?? null,
                'description' => $data['description'] ?? null,
                'folder_id' => $folder->id,
                'is_active' => $data['is_active'] ?? true,
            ]);

            // Link folder to service
            $folder->update(['service_id' => $service->id]);

            // 3. Audit log
            $this->auditService->success(
                action: 'service.created',
                auditable: $service,
                newValues: [
                    'name' => $service->name,
                    'direction_id' => $direction->id,
                    'direction_name' => $direction->name,
                    'code' => $service->code,
                ],
                user: $creator,
                description: "Service '{$service->name}' créé sous la direction '{$direction->name}'."
            );

            return $service;
        });
    }

    /**
     * Update an existing Service.
     */
    public function updateService(Service $service, array $data, ?User $user = null): Service
    {
        return DB::transaction(function () use ($service, $data, $user) {
            $oldValues = $service->only(['name', 'code', 'description', 'direction_id', 'is_active']);

            if (isset($data['direction_id']) && $data['direction_id'] !== $service->direction_id) {
                // Ensure target direction belongs to the same organization
                Direction::where('organization_id', $service->organization_id)->findOrFail($data['direction_id']);
            }

            $service->update($data);

            // Sync with associated folder
            if ($service->folder_id) {
                $folder = Folder::find($service->folder_id);
                if ($folder) {
                    $folderUpdates = [];
                    if (isset($data['name'])) {
                        $folderUpdates['name'] = $data['name'];
                    }
                    if (array_key_exists('description', $data)) {
                        $folderUpdates['description'] = $data['description'];
                    }
                    if (array_key_exists('is_active', $data)) {
                        $folderUpdates['is_active'] = (bool) $data['is_active'];
                    }
                    if (isset($data['direction_id'])) {
                        $newDirection = Direction::find($data['direction_id']);
                        $folderUpdates['direction_id'] = $newDirection?->id;
                        $folderUpdates['parent_id'] = $newDirection?->folder_id;
                    }
                    if (! empty($folderUpdates)) {
                        $folder->update($folderUpdates);
                    }
                }
            }

            $this->auditService->success(
                action: 'service.updated',
                auditable: $service,
                oldValues: $oldValues,
                newValues: $service->only(['name', 'code', 'description', 'direction_id', 'is_active']),
                user: $user,
                description: "Service '{$service->name}' mis à jour."
            );

            return $service;
        });
    }

    /**
     * Delete a Service.
     */
    public function deleteService(Service $service, ?User $user = null): bool
    {
        return DB::transaction(function () use ($service, $user) {
            $name = $service->name;

            // Clear primary service on affected users
            User::where('primary_service_id', $service->id)->update(['primary_service_id' => null]);

            // Detach associated users
            $service->users()->detach();

            // Deactivate access scopes referencing this service
            AccessScope::where('service_id', $service->id)->update(['is_active' => false]);

            // Soft-delete linked folder if exists
            if ($service->folder_id) {
                Folder::where('id', $service->folder_id)->delete();
            }

            $service->delete();

            $this->auditService->success(
                action: 'service.deleted',
                auditable: $service,
                user: $user,
                description: "Service '{$name}' supprimé."
            );

            return true;
        });
    }

    /**
     * Assign a user to a service (as primary or associated).
     */
    public function assignUserToService(User $user, Service $service, bool $isPrimary = false, ?User $actor = null): void
    {
        if ($user->organization_id !== $service->organization_id) {
            throw new ModelNotFoundException('User and service must belong to the same organization.');
        }

        DB::transaction(function () use ($user, $service, $isPrimary, $actor) {
            // Attach as associated if not already attached
            if (! $user->services()->where('services.id', $service->id)->exists()) {
                $user->services()->attach($service->id, ['organization_id' => $user->organization_id]);
            }

            if ($isPrimary) {
                $user->update(['primary_service_id' => $service->id]);
            }

            $this->auditService->success(
                action: 'user.service_assigned',
                auditable: $user,
                target: $service,
                newValues: [
                    'service_id' => $service->id,
                    'service_name' => $service->name,
                    'direction_id' => $service->direction_id,
                    'is_primary' => $isPrimary,
                ],
                user: $actor,
                description: "Utilisateur '{$user->name}' affecté au service '{$service->name}'".($isPrimary ? ' (Service principal)' : '').'.'
            );
        });
    }

    /**
     * Assign primary service to a user.
     */
    public function assignPrimaryService(User $user, Service $service, ?User $actor = null): void
    {
        $this->assignUserToService($user, $service, true, $actor);
    }

    /**
     * Remove a user from a service.
     */
    public function removeUserFromService(User $user, Service $service, ?User $actor = null): void
    {
        DB::transaction(function () use ($user, $service, $actor) {
            $user->services()->detach($service->id);

            if ($user->primary_service_id === $service->id) {
                // If primary service is removed, clear it or pick next associated
                $nextPrimary = $user->services()->first();
                $user->update(['primary_service_id' => $nextPrimary?->id]);
            }

            $this->auditService->success(
                action: 'user.service_removed',
                auditable: $user,
                target: $service,
                newValues: ['service_id' => $service->id, 'service_name' => $service->name],
                user: $actor,
                description: "Utilisateur '{$user->name}' retiré du service '{$service->name}'."
            );
        });
    }

    /**
     * Atomically synchronize a user's primary service and associated services.
     */
    public function syncUserServices(User $user, ?int $primaryServiceId, array $associatedServiceIds = [], ?User $actor = null): void
    {
        DB::transaction(function () use ($user, $primaryServiceId, $associatedServiceIds, $actor) {
            // Validate all services belong to the user's organization
            $allServiceIds = array_unique(array_filter(array_merge(
                $primaryServiceId ? [$primaryServiceId] : [],
                $associatedServiceIds
            )));

            if (! empty($allServiceIds)) {
                $count = Service::where('organization_id', $user->organization_id)
                    ->whereIn('id', $allServiceIds)
                    ->count();

                if ($count !== count($allServiceIds)) {
                    throw new ModelNotFoundException('Certains services spécifiés sont introuvables dans votre organisation.');
                }
            }

            // Sync pivot table
            $syncData = [];
            foreach ($allServiceIds as $sId) {
                $syncData[$sId] = ['organization_id' => $user->organization_id];
            }
            $user->services()->sync($syncData);

            // Set primary service ID
            $user->update(['primary_service_id' => $primaryServiceId]);

            $this->auditService->success(
                action: 'user.services_synced',
                auditable: $user,
                newValues: [
                    'primary_service_id' => $primaryServiceId,
                    'associated_service_ids' => $associatedServiceIds,
                ],
                user: $actor,
                description: "Structure des services mise à jour pour l'utilisateur '{$user->name}'."
            );
        });
    }
}
