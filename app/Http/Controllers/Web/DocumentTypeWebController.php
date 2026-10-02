<?php

namespace App\Http\Controllers\Web;

use App\Enums\FolderType;
use App\Exceptions\SubscriptionLimitExceededException;
use App\Http\Controllers\Controller;
use App\Models\Direction;
use App\Models\Folder;
use App\Models\MetadataDefinition;
use App\Models\Service;
use App\Services\BillingService;
use App\Services\FolderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class DocumentTypeWebController extends Controller
{
    public function __construct(
        protected FolderService $folderService,
        protected ?BillingService $billingService = null
    ) {
        $this->billingService = $this->billingService ?? app(BillingService::class);
    }

    /**
     * List all document types with Direction, Service, and search filters.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        if (! $user->can('folders.view') && ! $user->hasRole('admin') && ! $user->hasRole('super-admin')) {
            abort(403, 'Unauthorized to view document types.');
        }

        $query = Folder::query()
            ->where('organization_id', $user->organization_id)
            ->where('folder_type', FolderType::DocumentType)
            ->with(['parent', 'direction', 'service'])
            ->withCount(['typedDocuments', 'metadataDefinitions'])
            ->orderBy('name');

        if ($request->filled('direction_id')) {
            $dirId = (int) $request->input('direction_id');
            $dirFolderId = Direction::where('id', $dirId)->where('organization_id', $user->organization_id)->value('folder_id');
            $query->where(function ($q) use ($dirId, $dirFolderId) {
                $q->where('direction_id', $dirId);
                if ($dirFolderId) {
                    $q->orWhere('parent_id', $dirFolderId);
                }
                $q->orWhereHas('parent', fn ($pq) => $pq->where('direction_id', $dirId));
            });
        }

        if ($request->filled('service_id')) {
            $srvId = (int) $request->input('service_id');
            $srvFolderId = Service::where('id', $srvId)->where('organization_id', $user->organization_id)->value('folder_id');
            $query->where(function ($q) use ($srvId, $srvFolderId) {
                $q->where('service_id', $srvId);
                if ($srvFolderId) {
                    $q->orWhere('parent_id', $srvFolderId);
                }
                $q->orWhereHas('parent', fn ($pq) => $pq->where('service_id', $srvId));
            });
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where('name', $like, "%{$search}%");
        }

        $documentTypes = $query->get()->map(fn ($t) => [
            'id' => $t->id,
            'name' => $t->name,
            'description' => $t->description,
            'parent_id' => $t->parent_id,
            'direction_id' => $t->direction_id ?? $t->parent?->direction_id,
            'direction_name' => $t->direction?->name ?? $t->parent?->direction?->name ?? ($t->parent?->folder_type === FolderType::Department ? $t->parent->name : null),
            'service_id' => $t->service_id ?? $t->parent?->service_id,
            'service_name' => $t->service?->name ?? ($t->parent?->folder_type === FolderType::Service ? $t->parent->name : null),
            'department_name' => $t->parent?->name ?? 'Racine',
            'folder_type' => $t->folder_type?->value,
            'is_active' => (bool) $t->is_active,
            'documents_count' => $t->typed_documents_count ?? 0,
            'metadata_count' => $t->metadata_definitions_count ?? 0,
            'updated_at' => $t->updated_at?->toISOString(),
        ]);

        $directions = Direction::where('organization_id', $user->organization_id)
            ->where('is_active', true)
            ->with(['services' => fn ($q) => $q->where('is_active', true)->orderBy('name')])
            ->orderBy('name')
            ->get(['id', 'name', 'folder_id']);

        $services = Service::where('organization_id', $user->organization_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'direction_id', 'folder_id']);

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
            'directions' => $directions,
            'services' => $services,
            'departments' => $departments,
            'filters' => $request->only(['direction_id', 'service_id', 'search']),
            'can' => $can,
        ]);
    }

    /**
     * Store a new document type under a service, direction, or department.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        Gate::authorize('createDocumentType', Folder::class);

        try {
            $this->billingService->assertCanAddDocumentType(1, $user->organization_id);
        } catch (SubscriptionLimitExceededException $e) {
            return back()->withErrors(['limit' => $e->getMessage()])->with('error', $e->getMessage());
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'parent_id' => ['nullable', 'integer', 'exists:folders,id'],
            'direction_id' => ['nullable', 'integer', 'exists:directions,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'metadata_definition_ids' => ['nullable', 'array'],
            'metadata_definition_ids.*' => ['integer', 'exists:metadata_definitions,id'],
        ]);

        $directionId = null;
        $serviceId = null;
        $parentId = null;

        if (! empty($validated['service_id'])) {
            $service = Service::where('organization_id', $user->organization_id)->findOrFail($validated['service_id']);
            $parentId = $service->folder_id;
            $serviceId = $service->id;
            $directionId = $service->direction_id;
        } elseif (! empty($validated['direction_id'])) {
            $direction = Direction::where('organization_id', $user->organization_id)->findOrFail($validated['direction_id']);
            $parentId = $direction->folder_id;
            $directionId = $direction->id;
        } elseif (! empty($validated['parent_id'])) {
            $parent = Folder::where('organization_id', $user->organization_id)->findOrFail($validated['parent_id']);
            $parentId = $parent->id;
            $directionId = $parent->direction_id;
            $serviceId = $parent->service_id;
        } else {
            throw ValidationException::withMessages([
                'parent_id' => 'Veuillez spécifier une Direction, un Service ou un Dossier parent.',
            ]);
        }

        $docType = $this->folderService->createDocumentType([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'parent_id' => $parentId,
            'direction_id' => $directionId,
            'service_id' => $serviceId,
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
