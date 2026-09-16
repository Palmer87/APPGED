<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class AuditLogService
{
    /**
     * Minimum and maximum allowed pagination limits.
     */
    public const DEFAULT_PER_PAGE = 25;

    public const MAX_PER_PAGE = 100;

    /**
     * Get paginated audit logs with filters and multi-tenant security.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getLogs(User $actor, array $filters = [], int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        Gate::forUser($actor)->authorize('viewAny', AuditLog::class);

        $perPage = max(1, min($perPage, self::MAX_PER_PAGE));

        $query = AuditLog::query()->with(['user', 'organization']);

        // 1. Strict multi-tenant isolation
        if (! $actor->hasRole('super-admin')) {
            $query->where('organization_id', $actor->organization_id);
        } else {
            // Super-admin can optionally filter by organization
            if (! empty($filters['organization_id'])) {
                $query->where('organization_id', (int) $filters['organization_id']);
            }
        }

        // 2. User filter
        if (! empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        // 3. Action filter
        if (! empty($filters['action'])) {
            $query->where('action', (string) $filters['action']);
        }

        // 4. Result filter ('success' | 'failure')
        if (! empty($filters['result'])) {
            $query->where('result', (string) $filters['result']);
        }

        // 5. Auditable resource filter
        if (! empty($filters['auditable_type'])) {
            $query->where('auditable_type', (string) $filters['auditable_type']);
        }
        if (! empty($filters['auditable_id'])) {
            $query->where('auditable_id', (int) $filters['auditable_id']);
        }

        // 6. Target resource filter
        if (! empty($filters['target_type'])) {
            $query->where('target_type', (string) $filters['target_type']);
        }
        if (! empty($filters['target_id'])) {
            $query->where('target_id', (int) $filters['target_id']);
        }

        // 7. Date range filter
        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        // Chronological descending order (most recent first)
        return $query->latest('created_at')->paginate($perPage);
    }

    /**
     * Get the full audit history for a specific document.
     */
    public function getDocumentHistory(User $actor, Document $document, int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        // 1. Authorization check: actor must be allowed to view the document
        Gate::forUser($actor)->authorize('view', $document);

        $perPage = max(1, min($perPage, self::MAX_PER_PAGE));

        $query = AuditLog::query()
            ->with(['user'])
            ->where('organization_id', $document->organization_id)
            ->where(function (Builder $sub) use ($document) {
                $sub->where(function (Builder $q) use ($document) {
                    $q->where('auditable_type', $document->getMorphClass())
                        ->where('auditable_id', $document->id);
                })->orWhere(function (Builder $q) use ($document) {
                    $q->where('target_type', $document->getMorphClass())
                        ->where('target_id', $document->id);
                });
            })
            ->latest('created_at');

        return $query->paginate($perPage);
    }
}
