<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Folder;
use App\Services\FolderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class FolderWebController extends Controller
{
    public function __construct(
        protected FolderService $folderService
    ) {}

    /**
     * Display all folders in organization.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        if (! $user->can('folders.view')) {
            abort(403, 'Unauthorized to view folders.');
        }

        $folders = Folder::query()
            ->where('organization_id', $user->organization_id)
            ->withCount('documents')
            ->with(['parent', 'creator'])
            ->orderBy('name')
            ->get();

        return Inertia::render('Folders/Index', [
            'folders' => $folders,
        ]);
    }

    /**
     * Create a new folder.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Folder::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'parent_id' => ['nullable', 'integer', 'exists:folders,id'],
        ]);

        $this->folderService->create($validated, $request->user());

        return back()->with('success', 'Dossier créé avec succès.');
    }

    /**
     * Update an existing folder.
     */
    public function update(Request $request, Folder $folder): RedirectResponse
    {
        Gate::authorize('update', $folder);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'parent_id' => ['nullable', 'integer', 'exists:folders,id'],
        ]);

        if (isset($validated['parent_id']) && $validated['parent_id'] !== $folder->parent_id) {
            $newParent = $validated['parent_id'] ? Folder::find($validated['parent_id']) : null;
            $this->folderService->move($folder, $newParent);
        }

        $folder->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('success', 'Dossier mis à jour avec succès.');
    }

    /**
     * Delete a folder.
     */
    public function destroy(Request $request, Folder $folder): RedirectResponse
    {
        Gate::authorize('delete', $folder);

        $this->folderService->delete($folder, $request->user());

        return back()->with('success', 'Dossier supprimé avec succès.');
    }
}
