<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Services\TagService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TagWebController extends Controller
{
    public function __construct(
        protected TagService $tagService
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        if (! $user->can('tags.view')) {
            abort(403, 'Unauthorized to view tags.');
        }

        $tags = Tag::where('organization_id', $user->organization_id)
            ->withCount('documents')
            ->orderBy('name')
            ->get();

        return Inertia::render('Tags/Index', [
            'tags' => $tags,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Tag::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $this->tagService->create($validated, $request->user());

        return back()->with('success', 'Tag créé avec succès.');
    }

    public function update(Request $request, Tag $tag): RedirectResponse
    {
        Gate::authorize('update', $tag);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $this->tagService->update($tag, $validated, $request->user());

        return back()->with('success', 'Tag mis à jour avec succès.');
    }

    public function destroy(Request $request, Tag $tag): RedirectResponse
    {
        Gate::authorize('delete', $tag);

        $this->tagService->delete($tag, $request->user());

        return back()->with('success', 'Tag supprimé avec succès.');
    }
}
