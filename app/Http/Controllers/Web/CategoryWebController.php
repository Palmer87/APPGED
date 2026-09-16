<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CategoryWebController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        if (! $user->can('categories.view')) {
            abort(403, 'Unauthorized to view categories.');
        }

        $categories = Category::where('organization_id', $user->organization_id)
            ->withCount('documents')
            ->orderBy('name')
            ->get();

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Category::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:50'],
            'icon' => ['nullable', 'string', 'max:50'],
        ]);

        $this->categoryService->create($validated, $request->user());

        return back()->with('success', 'Catégorie créée avec succès.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        Gate::authorize('update', $category);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:50'],
            'icon' => ['nullable', 'string', 'max:50'],
        ]);

        $this->categoryService->update($category, $validated, $request->user());

        return back()->with('success', 'Catégorie mise à jour avec succès.');
    }

    public function destroy(Request $request, Category $category): RedirectResponse
    {
        Gate::authorize('delete', $category);

        $this->categoryService->delete($category, $request->user());

        return back()->with('success', 'Catégorie supprimée avec succès.');
    }
}
