<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentFavorite;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;

class FavoriteService
{
    public function __construct(
        protected AccessControlService $aclService,
        protected ?AuditService $auditService = null
    ) {
        $this->auditService = $this->auditService ?? app(AuditService::class);
    }

    /**
     * Toggle favorite status for a document.
     *
     * @return bool True if added to favorites, false if removed
     */
    public function toggleFavorite(User $user, Document $document): bool
    {
        // 1. Soft-delete check
        if ($document->trashed()) {
            abort(404, 'Document not found');
        }

        // 2. Tenant isolation
        if (! $user->hasRole('super-admin') && $user->organization_id !== $document->organization_id) {
            abort(403, 'Document belongs to a different organization');
        }

        // 3. Authorization check
        Gate::forUser($user)->authorize('view', $document);

        $existing = DocumentFavorite::where('user_id', $user->id)
            ->where('document_id', $document->id)
            ->first();

        if ($existing) {
            $existing->delete();

            $this->auditService->success(
                action: 'document.favorite_removed',
                auditable: $document,
                user: $user,
                description: "Document '{$document->name}' removed from favorites."
            );

            return false;
        }

        DocumentFavorite::create([
            'organization_id' => $document->organization_id,
            'user_id' => $user->id,
            'document_id' => $document->id,
        ]);

        $this->auditService->success(
            action: 'document.favorite_added',
            auditable: $document,
            user: $user,
            description: "Document '{$document->name}' added to favorites."
        );

        return true;
    }

    /**
     * Check if a document is favorited by the user.
     */
    public function isFavorite(User $user, Document $document): bool
    {
        return DocumentFavorite::where('user_id', $user->id)
            ->where('document_id', $document->id)
            ->exists();
    }

    /**
     * Get accessible favorite documents for the user.
     *
     * @return Collection<int, Document>
     */
    public function getUserFavorites(User $user, int $limit = 5): Collection
    {
        $limit = max(1, min($limit, 50));

        $query = Document::query()
            ->whereNull('documents.deleted_at')
            ->whereExists(function ($q) use ($user) {
                $q->selectRaw(1)
                    ->from('document_favorites')
                    ->whereColumn('document_favorites.document_id', 'documents.id')
                    ->where('document_favorites.user_id', $user->id);
            })
            ->with(['folder', 'categories', 'tags'])
            ->latest('documents.updated_at')
            ->take($limit);

        $this->aclService->applyAccessScope($query, $user);

        return $query->get();
    }

    /**
     * Get count of accessible active favorite documents for the user.
     */
    public function getFavoriteCount(User $user): int
    {
        $query = Document::query()
            ->whereNull('documents.deleted_at')
            ->whereExists(function ($q) use ($user) {
                $q->selectRaw(1)
                    ->from('document_favorites')
                    ->whereColumn('document_favorites.document_id', 'documents.id')
                    ->where('document_favorites.user_id', $user->id);
            });

        $this->aclService->applyAccessScope($query, $user);

        return $query->count();
    }
}
