<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MetadataDefinitionResource;
use App\Models\Document;
use App\Models\MetadataDefinition;
use App\Services\DocumentMetadataService;
use App\Services\MetadataDefinitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MetadataController extends Controller
{
    public function __construct(
        protected MetadataDefinitionService $definitionService,
        protected DocumentMetadataService $metadataService
    ) {}

    /**
     * List metadata definitions for the organization.
     */
    public function definitions(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->can('metadata.view')) {
            abort(403, 'Unauthorized to view metadata definitions.');
        }

        $definitions = MetadataDefinition::where('organization_id', $user->organization_id)
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => MetadataDefinitionResource::collection($definitions),
        ]);
    }

    /**
     * Create a metadata definition.
     */
    public function storeDefinition(Request $request): JsonResponse
    {
        Gate::authorize('create', MetadataDefinition::class);

        $definition = $this->definitionService->create($request->all());

        return response()->json([
            'data' => new MetadataDefinitionResource($definition),
            'message' => 'Metadata definition created successfully.',
        ], 201);
    }

    /**
     * Show a metadata definition.
     */
    public function showDefinition(MetadataDefinition $definition): JsonResponse
    {
        Gate::authorize('view', $definition);

        return response()->json([
            'data' => new MetadataDefinitionResource($definition),
        ]);
    }

    /**
     * Update a metadata definition.
     */
    public function updateDefinition(Request $request, MetadataDefinition $definition): JsonResponse
    {
        Gate::authorize('update', $definition);

        $updated = $this->definitionService->update($definition, $request->all());

        return response()->json([
            'data' => new MetadataDefinitionResource($updated),
            'message' => 'Metadata definition updated successfully.',
        ]);
    }

    /**
     * Delete a metadata definition.
     */
    public function destroyDefinition(MetadataDefinition $definition): JsonResponse
    {
        Gate::authorize('delete', $definition);

        $this->definitionService->delete($definition);

        return response()->json([
            'message' => 'Metadata definition deleted successfully.',
        ]);
    }

    /**
     * Get all metadata values for a document.
     */
    public function getDocumentMetadata(Document $document): JsonResponse
    {
        Gate::authorize('view', $document);

        $metadata = $this->metadataService->getDocumentMetadata($document);

        return response()->json([
            'data' => $metadata,
        ]);
    }

    /**
     * Update/set metadata values for a document.
     */
    public function updateDocumentMetadata(Request $request, Document $document): JsonResponse
    {
        Gate::authorize('update', $document);

        $validated = $request->validate([
            'values' => ['required', 'array'],
        ]);

        $this->metadataService->setValues($document, $validated['values']);

        return response()->json([
            'data' => $this->metadataService->getDocumentMetadata($document),
            'message' => 'Document metadata updated successfully.',
        ]);
    }
}
