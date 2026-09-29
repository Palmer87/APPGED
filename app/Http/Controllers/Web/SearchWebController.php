<?php

namespace App\Http\Controllers\Web;

use App\Enums\FolderType;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Direction;
use App\Models\Folder;
use App\Models\Service;
use App\Models\Tag;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchWebController extends Controller
{
    public function __construct(
        protected SearchService $searchService
    ) {}

    /**
     * Search documents with business criteria.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $filters = $request->all();

        $hasFilters = ! empty($filters['q'])
            || ! empty($filters['department_id'])
            || ! empty($filters['document_type_id'])
            || ! empty($filters['folder_id'])
            || ! empty($filters['category_id'])
            || ! empty($filters['tag_id'])
            || ! empty($filters['extension'])
            || ! empty($filters['status'])
            || ! empty($filters['created_from'])
            || ! empty($filters['created_to'])
            || (! empty($filters['metadata']) && array_filter((array) $filters['metadata']));

        $results = null;
        if ($hasFilters) {
            $results = $this->searchService->search($user, $filters);
            $results->loadMissing(['documentType.parent', 'creator', 'currentVersion']);
        }

        $departments = Folder::query()
            ->where('organization_id', $user->organization_id)
            ->where('folder_type', FolderType::Department)
            ->where('is_active', true)
            ->with([
                'children' => fn ($q) => $q->where('folder_type', FolderType::DocumentType)
                    ->where('is_active', true)
                    ->with(['metadataDefinitions' => fn ($mq) => $mq->where('is_active', true)])
                    ->orderBy('name'),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn ($dept) => [
                'id' => $dept->id,
                'name' => $dept->name,
                'document_types' => $dept->children->map(fn ($type) => [
                    'id' => $type->id,
                    'name' => $type->name,
                    'description' => $type->description,
                    'parent_id' => $type->parent_id,
                    'metadata_definitions' => $type->metadataDefinitions->map(fn ($def) => [
                        'id' => $def->id,
                        'name' => $def->name,
                        'key' => $def->key,
                        'type' => $def->type,
                        'is_required' => $def->pivot->is_required !== null ? (bool) $def->pivot->is_required : (bool) $def->is_required,
                        'order' => (int) $def->pivot->order,
                        'description' => $def->description,
                    ]),
                ])->values(),
            ])->values();

        $folders = Folder::where('organization_id', $user->organization_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $categories = Category::where('organization_id', $user->organization_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $tags = Tag::where('organization_id', $user->organization_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $directions = Direction::where('organization_id', $user->organization_id)
            ->where('is_active', true)
            ->with(['services' => fn ($q) => $q->where('is_active', true)->orderBy('name')])
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $services = Service::where('organization_id', $user->organization_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'direction_id']);

        $userContext = [
            'primary_service_id' => $user->primary_service_id,
            'primary_direction_id' => $user->primaryService?->direction_id,
            'primary_service_name' => $user->primaryService?->name,
            'primary_direction_name' => $user->primaryService?->direction?->name,
        ];

        return Inertia::render('Search/Index', [
            'results' => $results,
            'filters' => $filters,
            'departments' => $departments,
            'directions' => $directions,
            'services' => $services,
            'userContext' => $userContext,
            'folders' => $folders,
            'categories' => $categories,
            'tags' => $tags,
        ]);
    }
}
