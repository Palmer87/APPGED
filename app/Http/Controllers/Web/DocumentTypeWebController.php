<?php

namespace App\Http\Controllers\Web;

use App\Enums\FolderType;
use App\Http\Controllers\Controller;
use App\Models\Folder;
use App\Models\MetadataDefinition;
use App\Services\FolderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DocumentTypeWebController extends Controller
{
    public function __construct(
        protected FolderService $folderService
    ) {}

    /**
     * List all document types grouped by department.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        if (! $user->can('folders.view') && ! $user->hasRole('admin') && ! $user->hasRole('super-admin')) {
            abort(403, 'Unauthorized to view document types.');
        }

        $documentTypes = Folder::query()
            ->where('organization_id', $user->organization_id)
            ->where('folder_type', FolderType::DocumentType)
            ->with(['parent' => fn ($q) => $q->select(['id', 'name', 'folder_type'])])
            ->withCount(['typedDocuments', 'metadataDefinitions'])
            ->orderBy('name')
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'description' => $t->description,
                'parent_id' => $t->parent_id,
                'department_name' => $t->parent?->name ?? 'Racine',
                'folder_type' => $t->folder_type?->value,
                'is_active' => (bool) $t->is_active,
                'documents_count' => $t->typed_documents_count ?? 0,
                'metadata_count' => $t->metadata_definitions_count ?? 0,
                'updated_at' => $t->updated_at?->toISOString(),
            ]);

        $departments = Folder::query()
            ->where('organization_id', $user->organization_id)
            ->where('folder_type', FolderType::Department)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $can = [
            'create' => $user->can('folders.create') || $user->hasRole('admin') || $user->hasRole('super-admin'),
            'edit' => $user->can('folders.update') || $user->hasRole('admin') || $user->hasRole('super-admin'),
            'delete' => $user->can('folders.delete') || $user->hasRole('admin') || $user->hasRole('super-admin'),
        ];

        return Inertia::render('DocumentTypes/Index', [
            'documentTypes' => $documentTypes,
            'departments' => $departments,
            'can' => $can,
        ]);
    }

    /**
     * Store a new document type under a department.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        Gate::authorize('createDocumentType', Folder::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'parent_id' => ['required', 'integer', 'exists:folders,id'],
            'metadata_definition_ids' => ['nullable', 'array'],
            'metadata_definition_ids.*' => ['integer', 'exists:metadata_definitions,id'],
        ]);

        $parent = Folder::where('organization_id', $user->organization_id)->findOrFail($validated['parent_id']);

        $docType = $this->folderService->createDocumentType([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'parent_id' => $parent->id,
        ], $user);

        if (! empty($validated['metadata_definition_ids'])) {
            $this->folderService->syncMetadataDefinitions($docType, $validated['metadata_definition_ids'], $user);
        }

        return back()->with('success', "Type documentaire '{$docType->name}' créé avec succès.");
    }

    /**
     * Update an existing document type.
     */
    public function update(Request $request, Folder $documentType): RedirectResponse
    {
        $user = $request->user();
        if ($documentType->organization_id !== $user->organization_id) {
            abort(403, 'Unauthorized.');
        }

        Gate::authorize('update', $documentType);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'parent_id' => ['nullable', 'integer', 'exists:folders,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $this->folderService->update($documentType, $validated, $user);

        return back()->with('success', "Type documentaire '{$documentType->name}' mis à jour.");
    }

    /**
     * Delete or deactivate a document type.
     */
    public function destroy(Request $request, Folder $documentType): RedirectResponse
    {
        $user = $request->user();
        if ($documentType->organization_id !== $user->organization_id) {
            abort(403, 'Unauthorized.');
        }

        Gate::authorize('delete', $documentType);

        $force = (bool) $request->input('force', false);
        try {
            $this->folderService->delete($documentType, $force);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Type documentaire '{$documentType->name}' supprimé avec succès.");
    }

    /**
     * Display the metadata configuration page for a document type.
     */
    public function metadata(Request $request, Folder $documentType): Response
    {
        $user = $request->user();
        if ($documentType->organization_id !== $user->organization_id) {
            abort(403, 'Unauthorized.');
        }

        Gate::authorize('view', $documentType);

        $documentType->load(['parent']);

        $assignedDefinitions = $documentType->metadataDefinitions()
            ->orderByPivot('order')
            ->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'name' => $d->name,
                'key' => $d->key,
                'type' => $d->type,
                'is_required' => $d->pivot->is_required !== null ? (bool) $d->pivot->is_required : (bool) $d->is_required,
                'order' => (int) $d->pivot->order,
                'is_active' => (bool) $d->is_active,
            ]);

        $availableDefinitions = MetadataDefinition::query()
            ->where('organization_id', $user->organization_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'name' => $d->name,
                'key' => $d->key,
                'type' => $d->type,
                'is_required' => (bool) $d->is_required,
                'description' => $d->description,
            ]);

        $can = [
            'manage' => Gate::forUser($user)->allows('update', $documentType),
        ];

        return Inertia::render('DocumentTypes/Metadata', [
            'documentType' => [
                'id' => $documentType->id,
                'name' => $documentType->name,
                'description' => $documentType->description,
                'department_name' => $documentType->parent?->name ?? 'Racine',
            ],
            'assignedDefinitions' => $assignedDefinitions,
            'availableDefinitions' => $availableDefinitions,
            'can' => $can,
        ]);
    }

    /**
     * Save metadata configuration for a document type.
     */
    public function syncMetadata(Request $request, Folder $documentType): RedirectResponse
    {
        $user = $request->user();
        if ($documentType->organization_id !== $user->organization_id) {
            abort(403, 'Unauthorized.');
        }

        Gate::authorize('manageMetadata', $documentType);

        $validated = $request->validate([
            'definitions' => ['required', 'array'],
            'definitions.*.id' => ['required', 'integer', 'exists:metadata_definitions,id'],
            'definitions.*.is_required' => ['nullable', 'boolean'],
            'definitions.*.order' => ['nullable', 'integer'],
        ]);

        $this->folderService->syncMetadataDefinitions($documentType, $validated['definitions'], $user);

        return back()->with('success', 'Métadonnées configurées avec succès.');
    }
}
