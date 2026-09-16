<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentShare;
use App\Models\Group;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;

class DocumentShareService
{
    public function __construct(
        protected ?AuditService $auditService = null,
        protected ?NotificationService $notificationService = null
    ) {
        $this->auditService = $this->auditService ?? app(AuditService::class);
        $this->notificationService = $this->notificationService ?? app(NotificationService::class);
    }

    /**
     * Share a document with a user.
     */
    public function shareWithUser(
        User $actor,
        Document $document,
        User $target,
        string $permission = 'view',
        ?Carbon $expiresAt = null
    ): DocumentShare {
        // 1. Authorization check
        Gate::forUser($actor)->authorize('share', $document);

        // 2. Target user must belong to the document's organization
        if ($target->organization_id !== $document->organization_id) {
            abort(403, 'Target user belongs to a different organization');
        }

        // 3. Document must not be soft-deleted
        if ($document->trashed()) {
            abort(404, 'Document not found');
        }

        // 4. Validate permission
        $permission = strtolower($permission);
        if (! in_array($permission, DocumentShare::PERMISSIONS, true)) {
            abort(422, 'Invalid share permission');
        }

        // 5. Idempotent handling: update existing share or create new
        $existing = DocumentShare::where('document_id', $document->id)
            ->where('user_id', $target->id)
            ->first();

        if ($existing) {
            $existing->update([
                'permission' => $permission,
                'expires_at' => $expiresAt,
                'shared_by' => $actor->id,
                'revoked_at' => null,
                'revoked_by' => null,
            ]);

            $share = $existing->fresh();
        } else {
            $share = DocumentShare::create([
                'organization_id' => $document->organization_id,
                'document_id' => $document->id,
                'user_id' => $target->id,
                'group_id' => null,
                'permission' => $permission,
                'expires_at' => $expiresAt,
                'shared_by' => $actor->id,
            ]);
        }

        $this->auditService->success(
            action: 'document.shared',
            auditable: $document,
            target: $target,
            newValues: [
                'target_type' => 'user',
                'target_id' => $target->id,
                'target_name' => $target->name,
                'permission' => $permission,
                'expires_at' => $expiresAt?->toISOString(),
            ],
            user: $actor,
            description: "Document '{$document->name}' shared with user '{$target->name}'."
        );

        $this->notificationService->notifyDocumentShared($document, $actor, $target, $permission);

        return $share;
    }

    /**
     * Share a document with a group.
     */
    public function shareWithGroup(
        User $actor,
        Document $document,
        Group $group,
        string $permission = 'view',
        ?Carbon $expiresAt = null
    ): DocumentShare {
        // 1. Authorization check
        Gate::forUser($actor)->authorize('share', $document);

        // 2. Target group must belong to the document's organization
        if ($group->organization_id !== $document->organization_id) {
            abort(403, 'Target group belongs to a different organization');
        }

        // 3. Document must not be soft-deleted
        if ($document->trashed()) {
            abort(404, 'Document not found');
        }

        // 4. Validate permission
        $permission = strtolower($permission);
        if (! in_array($permission, DocumentShare::PERMISSIONS, true)) {
            abort(422, 'Invalid share permission');
        }

        // 5. Idempotent handling: update existing share or create new
        $existing = DocumentShare::where('document_id', $document->id)
            ->where('group_id', $group->id)
            ->first();

        if ($existing) {
            $existing->update([
                'permission' => $permission,
                'expires_at' => $expiresAt,
                'shared_by' => $actor->id,
                'revoked_at' => null,
                'revoked_by' => null,
            ]);

            $share = $existing->fresh();
        } else {
            $share = DocumentShare::create([
                'organization_id' => $document->organization_id,
                'document_id' => $document->id,
                'user_id' => null,
                'group_id' => $group->id,
                'permission' => $permission,
                'expires_at' => $expiresAt,
                'shared_by' => $actor->id,
            ]);
        }

        $this->auditService->success(
            action: 'document.shared',
            auditable: $document,
            target: $group,
            newValues: [
                'target_type' => 'group',
                'target_id' => $group->id,
                'target_name' => $group->name,
                'permission' => $permission,
                'expires_at' => $expiresAt?->toISOString(),
            ],
            user: $actor,
            description: "Document '{$document->name}' shared with group '{$group->name}'."
        );

        $this->notificationService->notifyDocumentShared($document, $actor, $group, $permission);

        return $share;
    }

    /**
     * Revoke a document share.
     */
    public function revoke(User $actor, DocumentShare $share): void
    {
        Gate::forUser($actor)->authorize('share', $share->document);

        if ($share->revoked_at === null) {
            $share->update([
                'revoked_at' => Carbon::now(),
                'revoked_by' => $actor->id,
            ]);

            $this->auditService->success(
                action: 'document.share_revoked',
                auditable: $share->document,
                target: $share->user ?? $share->group,
                metadata: [
                    'share_id' => $share->id,
                    'target_type' => $share->user_id ? 'user' : 'group',
                    'target_id' => $share->user_id ?? $share->group_id,
                ],
                user: $actor,
                description: "Share for document '{$share->document?->name}' was revoked."
            );

            $target = $share->user ?? $share->group;
            if ($target) {
                $this->notificationService->notifyDocumentShareRevoked($share->document, $actor, $target);
            }
        }
    }

    /**
     * Revoke a share by user.
     */
    public function revokeUserShare(User $actor, Document $document, User $target): void
    {
        $share = DocumentShare::where('document_id', $document->id)
            ->where('user_id', $target->id)
            ->whereNull('revoked_at')
            ->first();

        if ($share) {
            $this->revoke($actor, $share);
        }
    }

    /**
     * Revoke a share by group.
     */
    public function revokeGroupShare(User $actor, Document $document, Group $group): void
    {
        $share = DocumentShare::where('document_id', $document->id)
            ->where('group_id', $group->id)
            ->whereNull('revoked_at')
            ->first();

        if ($share) {
            $this->revoke($actor, $share);
        }
    }

    /**
     * Get all active shares for a document.
     *
     * @return Collection<int, DocumentShare>
     */
    public function getActiveShares(Document $document): Collection
    {
        return $document->shares()->active()->with(['user', 'group', 'sharedBy'])->get();
    }

    /**
     * Check if a user has view access to a document via an active share (user or group).
     */
    public function canViewViaShare(User $user, Document $document): bool
    {
        if ($user->organization_id !== $document->organization_id) {
            return false;
        }

        if ($document->trashed()) {
            return false;
        }

        // Direct user share with 'view' or 'download' (download implies view)
        $hasUserShare = DocumentShare::where('document_id', $document->id)
            ->where('user_id', $user->id)
            ->active()
            ->whereIn('permission', ['view', 'download'])
            ->exists();

        if ($hasUserShare) {
            return true;
        }

        // Group share
        $groupIds = $user->groups()->pluck('groups.id');
        if ($groupIds->isNotEmpty()) {
            return DocumentShare::where('document_id', $document->id)
                ->whereIn('group_id', $groupIds)
                ->active()
                ->whereIn('permission', ['view', 'download'])
                ->exists();
        }

        return false;
    }

    /**
     * Check if a user has download access to a document via an active share (user or group).
     */
    public function canDownloadViaShare(User $user, Document $document): bool
    {
        if ($user->organization_id !== $document->organization_id) {
            return false;
        }

        if ($document->trashed()) {
            return false;
        }

        // Direct user share with 'download'
        $hasUserShare = DocumentShare::where('document_id', $document->id)
            ->where('user_id', $user->id)
            ->active()
            ->where('permission', 'download')
            ->exists();

        if ($hasUserShare) {
            return true;
        }

        // Group share
        $groupIds = $user->groups()->pluck('groups.id');
        if ($groupIds->isNotEmpty()) {
            return DocumentShare::where('document_id', $document->id)
                ->whereIn('group_id', $groupIds)
                ->active()
                ->where('permission', 'download')
                ->exists();
        }

        return false;
    }
}
