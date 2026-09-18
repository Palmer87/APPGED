<?php

namespace App\Http\Controllers\Web;

use App\Enums\FolderType;
use App\Http\Controllers\Controller;
use App\Models\Folder;
use App\Services\FolderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentWebController extends Controller
{
    public function __construct(
        protected FolderService $folderService
    ) {}

    /**
     * List all departments (directions) in the organization.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        if (! $user->can('folders.view') && ! $user->hasRole('admin') && ! $user->hasRole('super-admin')) {
            abort(403, 'Unauthorized to view departments.');
        }

        $departments = Folder::query()
            ->where('organization_id', $user->organization_id)
            ->where('folder_type', FolderType::Department)
            ->withCount([
                'children as document_types_count' => fn ($q) => $q->where('folder_type', FolderType::DocumentType),
                'documents',
            ])
            ->orderBy('name')
            ->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'name' => $d->name,
                'description' => $d->description,
                'folder_type' => $d->folder_type?->value,
                'is_active' => (bool) $d->is_active,
                'document_types_count' => $d->document_types_count ?? 0,
                'documents_count' => $d->documents_count ?? 0,
                'created_at' => $d->created_at?->toISOString(),
                'updated_at' => $d->updated_at?->toISOString(),
            ]);

        $can = [
            'create' => $user->can('folders.create') || $user->hasRole('admin') || $user->hasRole('super-admin'),
            'edit' => $user->can('folders.update') || $user->hasRole('admin') || $user->hasRole('super-admin'),
            'delete' => $user->can('folders.delete') || $user->hasRole('admin') || $user->hasRole('super-admin'),
        ];

        return Inertia::render('Departments/Index', [
            'departments' => $departments,
            'can' => $can,
        ]);
    }

    /**
     * Store a new department.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        Gate::authorize('createDepartment', Folder::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $department = $this->folderService->createDepartment($validated, $user);

        return back()->with('success', "Direction '{$department->name}' créée avec succès.");
    }

    /**
     * Update an existing department.
     */
    public function update(Request $request, Folder $department): RedirectResponse
    {
        $user = $request->user();
        if ($department->organization_id !== $user->organization_id) {
            abort(403, 'Unauthorized.');
        }

        Gate::authorize('update', $department);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $this->folderService->update($department, $validated, $user);

        return back()->with('success', "Direction '{$department->name}' mise à jour.");
    }

    /**
     * Delete or deactivate a department.
     */
    public function destroy(Request $request, Folder $department): RedirectResponse
    {
        $user = $request->user();
        if ($department->organization_id !== $user->organization_id) {
            abort(403, 'Unauthorized.');
        }

        Gate::authorize('delete', $department);

        $force = (bool) $request->input('force', false);
        try {
            $this->folderService->delete($department, $force);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Direction '{$department->name}' supprimée avec succès.");
    }
}
