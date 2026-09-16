<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Folder;
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
     * Search documents with criteria.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $filters = $request->all();

        $results = null;
        if (! empty($filters['q']) || ! empty($filters['folder_id']) || ! empty($filters['category_id']) || ! empty($filters['tag_id']) || ! empty($filters['extension']) || ! empty($filters['status'])) {
            $results = $this->searchService->search($user, $filters);
        }

        $folders = Folder::where('organization_id', $user->organization_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $categories = Category::where('organization_id', $user->organization_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $tags = Tag::where('organization_id', $user->organization_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Search/Index', [
            'results' => $results,
            'filters' => $filters,
            'folders' => $folders,
            'categories' => $categories,
            'tags' => $tags,
        ]);
    }
}
