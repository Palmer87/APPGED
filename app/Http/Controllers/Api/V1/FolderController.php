<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\FolderResource;
use App\Models\Folder;
use App\Services\AccessControlService;
use App\Services\FolderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FolderController extends Controller
{
    public function __construct(
        protected FolderService $folderService,
        protected AccessControlService $aclService
    ) {}

    /**
     * List accessible folders for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->can('folders.view')) {
            abort(403, 'Unauthorized to view folders.');
        }

        $query = Folder::query()
            ->where('organization_id', $user->organization_id)
            ->with(['creator', 'parent'])
            ->withCount('children');

        if ($request->has('parent_id')) {
            $parentId = $request->input('parent_id');
            if ($parentId === 'null' || $parentId === null || $parentId === '') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', (int) $parentId);
            }
        } elseif (! $request->boolean('all')) {
            $query->whereNull('parent_id');
        }

        $folders = $query->orderBy('name')->get()
            ->filter(fn ($folder) => ! $this->aclService->hasAcl($folder) || $this->aclService->canAccessFolder($user, $folder, 'view'))
            ->values();

        return response()->json([
            'data' => FolderResource::collection($folders),
        ]);
    }

    /**
     * Create a new folder.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        Gate::authorize('create', Folder::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'parent_id' => ['nullable', 'integer', 'exists:folders,id'],
        ]);

        $folder = $this->folderService->create($validated, $user);

        return response()->json([
            'data' => new FolderResource($folder->load(['creator', 'parent'])),
            'message' => 'Folder created successfully.',
        ], 201);
    }

    /**
     * Get folder details.
     */
    public function show(Folder $folder): JsonResponse
    {
        Gate::authorize('view', $folder);

        $folder->load(['creator', 'parent', 'children' => fn ($q) => $q->withCount('children')]);

        return response()->json([
            'data' => new FolderResource($folder),
        ]);
    }

    /**
     * Update an existing folder.
     */
    public function update(Request $request, Folder $folder): JsonResponse
    {
        Gate::authorize('update', $folder);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'parent_id' => ['nullable', 'integer', 'exists:folders,id'],
        ]);

        if (array_key_exists('parent_id', $validated)) {
            $newParent = $validated['parent_id'] ? Folder::find($validated['parent_id']) : null;
            $this->folderService->move($folder, $newParent);
        }

        if (isset($validated['name']) || array_key_exists('description', $validated)) {
            $folder->update(array_filter([
                'name' => $validated['name'] ?? null,
                'description' => $validated['description'] ?? null,
            ], fn ($val) => $val !== null));
        }

        return response()->json([
            'data' => new FolderResource($folder->fresh(['creator', 'parent'])),
            'message' => 'Folder updated successfully.',
        ]);
    }

    /**
     * Delete a folder.
     */
    public function destroy(Folder $folder): JsonResponse
    {
        Gate::authorize('delete', $folder);

        $this->folderService->delete($folder);

        return response()->json([
            'message' => 'Folder moved to trash successfully.',
        ]);
    }
}
