<?php

namespace App\Services;

use App\Http\Requests\SearchRequest;
use App\Models\Category;
use App\Models\Document;
use App\Models\Folder;
use App\Models\MetadataDefinition;
use App\Models\Tag;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SearchService
{
    public function __construct(
        protected AccessControlService $aclService
    ) {}

    /**
     * Search documents accessible by the user with filters, sorting, and pagination.
     *
     * @param  array<string, mixed>  $filters
     */
    public function search(User $user, array $filters = []): LengthAwarePaginator
    {
        // 1. Centralized filter validation using SearchRequest rules
        $validated = Validator::make($filters, (new SearchRequest)->rules())->validate();

        // 2. Base query with eager loading to prevent N+1 issues
        $query = Document::query()
            ->with(['categories', 'tags', 'metadataValues.definition', 'folder', 'currentOcr']);

        // 3. Super-admin vs normal tenant isolation & ACL access control
        $this->aclService->applyAccessScope($query, $user);

        // Optional organization filter for super-admin
        if ($user->hasRole('super-admin') && ! empty($validated['organization_id'])) {
            $query->where('documents.organization_id', $validated['organization_id']);
        }

        // 4. Text search ('q') across name, file_name, description, OCR, and metadata
        if (! empty($validated['q'])) {
            $this->applyTextSearch($query, trim($validated['q']));
        }

        // 5. Department filter (Direction)
        if (! empty($validated['department_id'])) {
            $this->applyDepartmentFilter($query, $user, (int) $validated['department_id']);
        }

        // 6. Document Type filter (Type documentaire)
        if (! empty($validated['document_type_id'])) {
            $this->applyDocumentTypeFilter($query, $user, (int) $validated['document_type_id']);
        }

        // 7. Folder filter
        if (! empty($validated['folder_id'])) {
            $this->applyFolderFilter($query, $user, (int) $validated['folder_id']);
        }

        // 8. Category filter
        if (! empty($validated['category_id'])) {
            $this->applyCategoryFilter($query, $user, (int) $validated['category_id']);
        }

        // 9. Tag filters (AND logic when multiple tags are specified)
        $tagIds = $this->extractTagIds($validated);
        if (! empty($tagIds)) {
            $this->applyTagFilters($query, $user, $tagIds);
        }

        // 10. Metadata key/value filter
        if (! empty($validated['metadata_key'])) {
            $this->applyMetadataFilter(
                $query,
                $user,
                $validated['metadata_key'],
                $validated['metadata_value'] ?? null
            );
        }

        // 11. Date filters (created_at & updated_at)
        $this->applyDateFilters($query, $validated);

        // 12. Extension filter
        if (! empty($validated['extension'])) {
            $ext = strtolower(ltrim(trim($validated['extension']), '.'));
            $query->where('documents.extension', $ext);
        }

        // 13. Status filter ('active', 'archived')
        if (! empty($validated['status'])) {
            $query->where('documents.status', $validated['status']);
        }

        // 14. Whitelisted sorting
        $sortColumn = $validated['sort'] ?? 'created_at';
        $sortDirection = strtolower($validated['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy("documents.{$sortColumn}", $sortDirection);

        // 15. Pagination (default: 15, max: 100 enforced by validator)
        $perPage = isset($validated['per_page']) ? (int) $validated['per_page'] : 15;

        return $query->paginate($perPage);
    }

    /**
     * Apply case-insensitive full-text and partial search across name, file_name, description, OCR, and metadata.
     */
    protected function applyTextSearch(Builder $query, string $term): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            $wildcard = "%{$term}%";
            $query->where(function (Builder $sub) use ($term, $wildcard) {
                $sub->where('documents.name', 'ilike', $wildcard)
                    ->orWhere('documents.file_name', 'ilike', $wildcard)
                    ->orWhere('documents.description', 'ilike', $wildcard)
                    ->orWhereRaw(
                        "to_tsvector('simple', coalesce(documents.name, '') || ' ' || coalesce(documents.file_name, '') || ' ' || coalesce(documents.description, '')) @@ plainto_tsquery('simple', ?)",
                        [$term]
                    )
                    ->orWhereHas('metadataValues', function (Builder $mq) use ($wildcard) {
                        $mq->where('value_string', 'ilike', $wildcard)
                            ->orWhere('value_text', 'ilike', $wildcard);
                    })
                    ->orWhereHas('ocrs', function (Builder $oq) use ($term, $wildcard) {
                        $oq->where('status', 'completed')
                            ->where(function (Builder $toq) use ($term, $wildcard) {
                                $toq->whereRaw("to_tsvector('simple', coalesce(extracted_text, '')) @@ plainto_tsquery('simple', ?)", [$term])
                                    ->orWhere('extracted_text', 'ilike', $wildcard);
                            });
                    });
            });
        } else {
            $wildcard = '%'.mb_strtolower($term).'%';
            $query->where(function (Builder $sub) use ($wildcard) {
                $sub->whereRaw('LOWER(documents.name) LIKE ?', [$wildcard])
                    ->orWhereRaw('LOWER(documents.file_name) LIKE ?', [$wildcard])
                    ->orWhereRaw('LOWER(documents.description) LIKE ?', [$wildcard])
                    ->orWhereHas('metadataValues', function (Builder $mq) use ($wildcard) {
                        $mq->whereRaw('LOWER(value_string) LIKE ?', [$wildcard])
                            ->orWhereRaw('LOWER(value_text) LIKE ?', [$wildcard]);
                    })
                    ->orWhereHas('ocrs', function (Builder $oq) use ($wildcard) {
                        $oq->where('status', 'completed')
                            ->whereRaw('LOWER(extracted_text) LIKE ?', [$wildcard]);
                    });
            });
        }
    }

    /**
     * Apply department filter (Direction).
     */
    protected function applyDepartmentFilter(Builder $query, User $user, int $deptId): void
    {
        $deptQuery = Folder::where('id', $deptId)->whereNull('deleted_at');
        if (! $user->hasRole('super-admin')) {
            $deptQuery->where('organization_id', $user->organization_id);
        }
        $deptFolder = $deptQuery->first();
        if (! $deptFolder) {
            $query->whereRaw('1 = 0');

            return;
        }

        $descendantQuery = Folder::where('organization_id', $deptFolder->organization_id)->whereNull('deleted_at');
        if (! empty($deptFolder->path)) {
            $descendantQuery->where(function ($q) use ($deptFolder) {
                $q->where('path', 'like', $deptFolder->path.'%')
                    ->orWhere('parent_id', $deptFolder->id);
            });
        } else {
            $descendantQuery->where('parent_id', $deptFolder->id);
        }

        $descendantIds = $descendantQuery->pluck('id')
            ->push($deptFolder->id)
            ->all();

        $query->where(function (Builder $q) use ($descendantIds) {
            $q->whereIn('documents.folder_id', $descendantIds)
                ->orWhereIn('documents.document_type_id', $descendantIds);
        });
    }

    /**
     * Apply document type filter.
     */
    protected function applyDocumentTypeFilter(Builder $query, User $user, int $typeId): void
    {
        $typeQuery = Folder::where('id', $typeId)->whereNull('deleted_at');
        if (! $user->hasRole('super-admin')) {
            $typeQuery->where('organization_id', $user->organization_id);
        }
        if (! $typeQuery->exists()) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function (Builder $q) use ($typeId) {
            $q->where('documents.document_type_id', $typeId)
                ->orWhere('documents.folder_id', $typeId);
        });
    }

    /**
     * Apply folder filter with tenant check.
     */
    protected function applyFolderFilter(Builder $query, User $user, int $folderId): void
    {
        if ($user->hasRole('super-admin')) {
            $folderExists = Folder::where('id', $folderId)->whereNull('deleted_at')->exists();
            if (! $folderExists) {
                $query->whereRaw('1 = 0');

                return;
            }
        } else {
            $folder = Folder::where('id', $folderId)
                ->where('organization_id', $user->organization_id)
                ->whereNull('deleted_at')
                ->first();

            if (! $folder) {
                $query->whereRaw('1 = 0');

                return;
            }
        }

        $query->where('documents.folder_id', $folderId);
    }

    /**
     * Apply category filter with tenant check.
     */
    protected function applyCategoryFilter(Builder $query, User $user, int $categoryId): void
    {
        if ($user->hasRole('super-admin')) {
            $categoryExists = Category::where('id', $categoryId)->whereNull('deleted_at')->exists();
            if (! $categoryExists) {
                $query->whereRaw('1 = 0');

                return;
            }
        } else {
            $category = Category::where('id', $categoryId)
                ->where('organization_id', $user->organization_id)
                ->whereNull('deleted_at')
                ->first();

            if (! $category) {
                $query->whereRaw('1 = 0');

                return;
            }
        }

        $query->whereHas('categories', function (Builder $cq) use ($categoryId) {
            $cq->where('categories.id', $categoryId)
                ->whereNull('categories.deleted_at');
        });
    }

    /**
     * Extract and normalize tag IDs from filters.
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, int>
     */
    protected function extractTagIds(array $filters): array
    {
        $tagIds = [];

        if (! empty($filters['tag_id'])) {
            $tagIds[] = (int) $filters['tag_id'];
        }

        if (! empty($filters['tag_ids']) && is_array($filters['tag_ids'])) {
            foreach ($filters['tag_ids'] as $tid) {
                $tagIds[] = (int) $tid;
            }
        }

        return array_values(array_unique($tagIds));
    }

    /**
     * Apply tag filters with strict AND semantics and tenant verification.
     *
     * @param  array<int, int>  $tagIds
     */
    protected function applyTagFilters(Builder $query, User $user, array $tagIds): void
    {
        foreach ($tagIds as $tagId) {
            if ($user->hasRole('super-admin')) {
                $tagExists = Tag::where('id', $tagId)->whereNull('deleted_at')->exists();
                if (! $tagExists) {
                    $query->whereRaw('1 = 0');

                    return;
                }
            } else {
                $tag = Tag::where('id', $tagId)
                    ->where('organization_id', $user->organization_id)
                    ->whereNull('deleted_at')
                    ->first();

                if (! $tag) {
                    $query->whereRaw('1 = 0');

                    return;
                }
            }

            $query->whereHas('tags', function (Builder $tq) use ($tagId) {
                $tq->where('tags.id', $tagId)
                    ->whereNull('tags.deleted_at');
            });
        }
    }

    /**
     * Apply metadata filter with tenant and type safety.
     */
    protected function applyMetadataFilter(
        Builder $query,
        User $user,
        string $metadataKey,
        mixed $metadataValue
    ): void {
        $defQuery = MetadataDefinition::where('key', $metadataKey)->whereNull('deleted_at');

        if (! $user->hasRole('super-admin')) {
            $defQuery->where('organization_id', $user->organization_id);
        }

        $definition = $defQuery->first();

        if (! $definition) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereHas('metadataValues', function (Builder $mq) use ($definition, $metadataValue) {
            $mq->where('metadata_definition_id', $definition->id);

            if ($metadataValue !== null) {
                match ($definition->type) {
                    'integer' => $mq->where('value_integer', (int) $metadataValue),
                    'decimal' => $mq->where('value_decimal', (float) $metadataValue),
                    'boolean' => $mq->where('value_boolean', filter_var($metadataValue, FILTER_VALIDATE_BOOLEAN)),
                    'date' => $mq->whereDate('value_date', $metadataValue),
                    'datetime' => $mq->where('value_datetime', Carbon::parse($metadataValue)),
                    'text' => $mq->where('value_text', 'like', "%{$metadataValue}%"),
                    default => $mq->where('value_string', (string) $metadataValue),
                };
            }
        });
    }

    /**
     * Apply date range filters on created_at and updated_at.
     *
     * @param  array<string, mixed>  $filters
     */
    protected function applyDateFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['created_from'])) {
            $query->whereDate('documents.created_at', '>=', $filters['created_from']);
        }

        if (! empty($filters['created_to'])) {
            $query->whereDate('documents.created_at', '<=', $filters['created_to']);
        }

        if (! empty($filters['updated_from'])) {
            $query->whereDate('documents.updated_at', '>=', $filters['updated_from']);
        }

        if (! empty($filters['updated_to'])) {
            $query->whereDate('documents.updated_at', '<=', $filters['updated_to']);
        }
    }
}
