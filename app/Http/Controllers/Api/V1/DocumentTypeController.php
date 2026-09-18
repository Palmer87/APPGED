<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\FolderType;
use App\Http\Controllers\Controller;
use App\Models\Folder;
use App\Services\FolderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DocumentTypeController extends Controller
{
    public function __construct(
        protected FolderService $folderService
    ) {}

    /**
     * List document types for the user's organization.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        Gate::authorize('viewAny', Folder::class);

        $query = Folder::where('organization_id', $user->organization_id)
            ->where('folder_type', FolderType::DocumentType)
            ->with(['parent:id,name', 'metadataDefinitions'])
            ->withCount('typedDocuments')
            ->orderBy('name');

        if ($departmentId = $request->input('department_id')) {
            $query->where('parent_id', $departmentId);
        }

        $documentTypes = $query->paginate($request->input('per_page', 20));

        return response()->json($documentTypes);
    }

    /**
     * Create a new document type.
     */
    public function store(Request $request): JsonResponse
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

        return response()->json($docType->load(['metadataDefinitions']), 201);
    }

    /**
     * Show a document type.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $docType = Folder::where('organization_id', $user->organization_id)
            ->where('folder_type', FolderType::DocumentType)
            ->with(['parent:id,name', 'metadataDefinitions'])
            ->withCount('typedDocuments')
            ->findOrFail($id);

        Gate::authorize('view', $docType);

        return response()->json($docType);
    }

    /**
     * Update a document type.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $docType = Folder::where('organization_id', $user->organization_id)
            ->where('folder_type', FolderType::DocumentType)
            ->findOrFail($id);

        Gate::authorize('update', $docType);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'parent_id' => ['sometimes', 'required', 'integer', 'exists:folders,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $updated = $this->folderService->update($docType, $validated, $user);

        return response()->json($updated);
    }

    /**
     * Delete or deactivate a document type.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $docType = Folder::where('organization_id', $user->organization_id)
            ->where('folder_type', FolderType::DocumentType)
            ->findOrFail($id);

        Gate::authorize('delete', $docType);

        $force = filter_var($request->input('force', false), FILTER_VALIDATE_BOOLEAN);
        $this->folderService->delete($docType, $force);

        return response()->json(['message' => 'Document type deleted successfully.']);
    }

    /**
     * Get metadata definitions for a document type.
     */
    public function metadata(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $docType = Folder::where('organization_id', $user->organization_id)
            ->where('folder_type', FolderType::DocumentType)
            ->findOrFail($id);

        Gate::authorize('view', $docType);

        return response()->json($docType->metadataDefinitions()->orderByPivot('order')->get());
    }

    /**
     * Sync metadata definitions for a document type.
     */
    public function syncMetadata(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $docType = Folder::where('organization_id', $user->organization_id)
            ->where('folder_type', FolderType::DocumentType)
            ->findOrFail($id);

        Gate::authorize('manageMetadata', $docType);

        $validated = $request->validate([
            'definitions' => ['required', 'array'],
            'definitions.*.id' => ['required', 'integer', 'exists:metadata_definitions,id'],
            'definitions.*.is_required' => ['nullable', 'boolean'],
            'definitions.*.order' => ['nullable', 'integer'],
        ]);

        $this->folderService->syncMetadataDefinitions($docType, $validated['definitions'], $user);

        return response()->json([
            'message' => 'Metadata definitions synchronized successfully.',
            'definitions' => $docType->metadataDefinitions()->orderByPivot('order')->get(),
        ]);
    }
}
