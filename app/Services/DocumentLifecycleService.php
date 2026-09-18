<?php

namespace App\Services;

use App\Enums\WorkflowStatus;
use App\Models\Document;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DocumentLifecycleService
{
    public function __construct(
        protected AccessControlService $accessControlService,
        protected ?AuditService $auditService = null,
        protected ?NotificationService $notificationService = null
    ) {
        $this->auditService = $this->auditService ?? app(AuditService::class);
        $this->notificationService = $this->notificationService ?? app(NotificationService::class);
    }

    /**
     * Archive an active document.
     */
    public function archive(User $actor, Document $document): Document
    {
        Gate::forUser($actor)->authorize('archive', $document);

        if ($document->trashed()) {
            throw new HttpException(422, 'Cannot archive a trashed document.');
        }

        if ($document->workflowInstances()->whereIn('status', [WorkflowStatus::InProgress, WorkflowStatus::CorrectionRequested])->exists()) {
            throw new HttpException(422, 'Cannot archive a document with an active workflow.');
        }

        $oldStatus = $document->status;
        if ($document->status !== 'archived') {
            $document->update(['status' => 'archived']);
        }

        $this->auditService->success(
            action: 'document.archived',
            auditable: $document,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => 'archived'],
            user: $actor,
            description: "Document '{$document->name}' was archived."
        );

        $this->notificationService->notifyDocumentArchived($document, $actor);

        return $document;
    }

    /**
     * Unarchive an archived document back to active.
     */
    public function unarchive(User $actor, Document $document): Document
    {
        Gate::forUser($actor)->authorize('restore', $document);

        if ($document->trashed()) {
            throw new HttpException(422, 'Cannot unarchive a trashed document.');
        }

        $oldStatus = $document->status;
        if ($document->status !== 'active') {
            $document->update(['status' => 'active']);
        }

        $this->auditService->success(
            action: 'document.unarchived',
            auditable: $document,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => 'active'],
            user: $actor,
            description: "Document '{$document->name}' was unarchived back to active."
        );

        return $document;
    }

    /**
     * Move an active or archived document to trash (soft delete).
     * Physical storage files are kept intact.
     */
    public function moveToTrash(User $actor, Document $document): void
    {
        Gate::forUser($actor)->authorize('delete', $document);

        if ($document->workflowInstances()->whereIn('status', [WorkflowStatus::InProgress, WorkflowStatus::CorrectionRequested])->exists()) {
            throw new HttpException(422, 'Cannot move to trash a document with an active workflow.');
        }

        $document->delete();

        $this->auditService->success(
            action: 'document.deleted',
            auditable: $document,
            oldValues: ['deleted_at' => null],
            newValues: ['deleted_at' => $document->deleted_at?->toISOString()],
            user: $actor,
            description: "Document '{$document->name}' moved to trash."
        );
    }

    /**
     * Restore a document from trash.
     * Retains original status ('active' or 'archived').
     */
    public function restoreFromTrash(User $actor, Document $document): Document
    {
        if (! $document->trashed()) {
            throw new HttpException(422, 'Document is not in trash.');
        }

        Gate::forUser($actor)->authorize('restore', $document);

        $document->restore();

        $this->auditService->success(
            action: 'document.restored',
            auditable: $document,
            oldValues: ['deleted_at' => 'trashed'],
            newValues: ['deleted_at' => null, 'status' => $document->status],
            user: $actor,
            description: "Document '{$document->name}' restored from trash."
        );

        $this->notificationService->notifyDocumentRestored($document, $actor);

        return $document;
    }

    /**
     * Permanently delete a document and purge all its physical files from storage.
     * Enforces that the document MUST already be in trash.
     */
    public function forceDelete(User $actor, Document $document): void
    {
        if (! $document->trashed()) {
            throw new HttpException(422, 'Document must be moved to trash before permanent deletion.');
        }

        Gate::forUser($actor)->authorize('forceDelete', $document);

        // Collect and physically purge storage files using each record's configured disk
        $purgedFiles = [];

        $document->loadMissing('versions');
        foreach ($document->versions as $version) {
            if (! empty($version->storage_path)) {
                $versionDisk = $version->storage_disk ?: config('filesystems.documents_disk', config('filesystems.default', 'local'));
                if (Storage::disk($versionDisk)->exists($version->storage_path)) {
                    Storage::disk($versionDisk)->delete($version->storage_path);
                }
                $purgedFiles[] = "{$versionDisk}://{$version->storage_path}";
            }
        }

        if (! empty($document->storage_path)) {
            $docDisk = $document->storage_disk ?: config('filesystems.documents_disk', config('filesystems.default', 'local'));
            if (Storage::disk($docDisk)->exists($document->storage_path)) {
                Storage::disk($docDisk)->delete($document->storage_path);
            }
            $docEntry = "{$docDisk}://{$document->storage_path}";
            if (! in_array($docEntry, $purgedFiles, true)) {
                $purgedFiles[] = $docEntry;
            }
        }

        // Clean up document directory if applicable
        $docDisk = $document->storage_disk ?: config('filesystems.documents_disk', config('filesystems.default', 'local'));
        $docDir = "organizations/{$document->organization_id}/documents/{$document->id}";
        try {
            Storage::disk($docDisk)->deleteDirectory($docDir);
        } catch (\Throwable) {
            // Non-blocking if directory cleanup is not supported by driver
        }

        // Log audit BEFORE deleting DB record to preserve IDs and organization info
        $this->auditService->success(
            action: 'document.force_deleted',
            auditable: $document,
            metadata: [
                'purged_files_count' => count($purgedFiles),
                'purged_files' => $purgedFiles,
            ],
            user: $actor,
            description: "Document '{$document->name}' permanently deleted and files purged."
        );

        // Database cascade handles versions, shares, permissions, metadata, categories, tags
        $document->forceDelete();
    }

    /**
     * Empty the trash for the actor's organization.
     */
    public function emptyTrash(User $actor, ?int $organizationId = null): int
    {
        $orgId = $organizationId ?? $actor->organization_id;

        $trashedDocuments = Document::onlyTrashed()
            ->where('organization_id', $orgId)
            ->get();

        $count = 0;
        foreach ($trashedDocuments as $document) {
            $this->forceDelete($actor, $document);
            $count++;
        }

        return $count;
    }

    /**
     * List trashed documents for the actor's organization.
     */
    public function getTrash(User $actor, int $perPage = 15): LengthAwarePaginator
    {
        return Document::onlyTrashed()
            ->where('organization_id', $actor->organization_id)
            ->with(['folder', 'uploader'])
            ->latest('deleted_at')
            ->paginate($perPage);
    }

    /**
     * List archived documents accessible to the actor.
     */
    public function getArchived(User $actor, int $perPage = 15): LengthAwarePaginator
    {
        $query = Document::where('status', 'archived')
            ->with(['folder', 'uploader', 'categories', 'tags']);

        $query = $this->accessControlService->applyAccessScope($query, $actor);

        return $query->latest('updated_at')->paginate($perPage);
    }
}
