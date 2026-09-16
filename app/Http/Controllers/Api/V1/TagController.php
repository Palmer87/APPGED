<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\TagResource;
use App\Models\Tag;
use App\Services\TagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TagController extends Controller
{
    public function __construct(
        protected TagService $tagService
    ) {}

    /**
     * List tags for the user's organization.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->can('tags.view')) {
            abort(403, 'Unauthorized to view tags.');
        }

        $tags = Tag::where('organization_id', $user->organization_id)
            ->withCount('documents')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => TagResource::collection($tags),
        ]);
    }

    /**
     * Create a tag.
     */
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Tag::class);

        $tag = $this->tagService->create($request->all());

        return response()->json([
            'data' => new TagResource($tag),
            'message' => 'Tag created successfully.',
        ], 201);
    }

    /**
     * Show tag details.
     */
    public function show(Tag $tag): JsonResponse
    {
        Gate::authorize('view', $tag);

        $tag->loadCount('documents');

        return response()->json([
            'data' => new TagResource($tag),
        ]);
    }

    /**
     * Update a tag.
     */
    public function update(Request $request, Tag $tag): JsonResponse
    {
        Gate::authorize('update', $tag);

        $updated = $this->tagService->update($tag, $request->all());

        return response()->json([
            'data' => new TagResource($updated),
            'message' => 'Tag updated successfully.',
        ]);
    }

    /**
     * Delete a tag.
     */
    public function destroy(Tag $tag): JsonResponse
    {
        Gate::authorize('delete', $tag);

        $this->tagService->delete($tag);

        return response()->json([
            'message' => 'Tag deleted successfully.',
        ]);
    }
}
