<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class RecentDocumentService
{
    public function __construct(
        protected AccessControlService $aclService
    ) {}

    /**
     * Get recently interacted or recently updated documents accessible to the user.
     *
     * @return Collection<int, Document>
     */
    public function getRecentDocuments(User $user, int $limit = 5): Collection
    {
        $limit = max(1, min($limit, 50));

        // 1. Fetch document IDs recently interacted with by this user via AuditLog
        $recentInteractions = AuditLog::query()
            ->where('user_id', $user->id)
            ->where('auditable_type', Document::class)
            ->whereIn('action', [
                'document.previewed',
                'document.viewed',
                'document.created',
                'document.updated',
                'document.comment_created',
            ])
            ->latest('created_at')
            ->take(20)
            ->pluck('auditable_id')
            ->unique()
            ->values();

        $recentDocs = new Collection;

        if ($recentInteractions->isNotEmpty()) {
            $query = Document::query()
                ->whereIn('documents.id', $recentInteractions)
                ->whereNull('documents.deleted_at')
                ->where('documents.status', '!=', 'archived')
                ->with(['folder', 'categories', 'tags']);

            $this->aclService->applyAccessScope($query, $user);

            $accessibleDocs = $query->get()->keyBy('id');

            // Preserve the chronological order of interaction
            foreach ($recentInteractions as $docId) {
                if ($accessibleDocs->has($docId) && $recentDocs->count() < $limit) {
                    $recentDocs->push($accessibleDocs->get($docId));
                }
            }
        }

        // 2. If fewer than limit, supplement with most recently updated accessible documents
        if ($recentDocs->count() < $limit) {
            $needed = $limit - $recentDocs->count();
            $alreadyIncludedIds = $recentDocs->pluck('id')->all();

            $fallbackQuery = Document::query()
                ->whereNull('documents.deleted_at')
                ->where('documents.status', '!=', 'archived')
                ->when(! empty($alreadyIncludedIds), fn ($q) => $q->whereNotIn('documents.id', $alreadyIncludedIds))
                ->with(['folder', 'categories', 'tags'])
                ->latest('documents.updated_at')
                ->take($needed);

            $this->aclService->applyAccessScope($fallbackQuery, $user);

            foreach ($fallbackQuery->get() as $doc) {
                $recentDocs->push($doc);
            }
        }

        return $recentDocs;
    }
}
