<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService
    ) {}

    /**
     * List categories for the user's organization.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->can('categories.view')) {
            abort(403, 'Unauthorized to view categories.');
        }

        $categories = Category::where('organization_id', $user->organization_id)
            ->withCount('documents')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => CategoryResource::collection($categories),
        ]);
    }

    /**
     * Create a category.
     */
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Category::class);

        $category = $this->categoryService->create($request->all());

        return response()->json([
            'data' => new CategoryResource($category),
            'message' => 'Category created successfully.',
        ], 201);
    }

    /**
     * Show category details.
     */
    public function show(Category $category): JsonResponse
    {
        Gate::authorize('view', $category);

        $category->loadCount('documents');

        return response()->json([
            'data' => new CategoryResource($category),
        ]);
    }

    /**
     * Update a category.
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        Gate::authorize('update', $category);

        $updated = $this->categoryService->update($category, $request->all());

        return response()->json([
            'data' => new CategoryResource($updated),
            'message' => 'Category updated successfully.',
        ]);
    }

    /**
     * Delete a category.
     */
    public function destroy(Category $category): JsonResponse
    {
        Gate::authorize('delete', $category);

        $this->categoryService->delete($category);

        return response()->json([
            'message' => 'Category deleted successfully.',
        ]);
    }
}
