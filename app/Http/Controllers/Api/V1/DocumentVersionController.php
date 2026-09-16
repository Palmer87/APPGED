<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DocumentVersionResource;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class DocumentVersionController extends Controller
{
    public function __construct(
        protected DocumentService $documentService
    ) {}

    /**
     * List all versions of a document.
     */
    public function index(Document $document): JsonResponse
    {
        Gate::authorize('view', $document);

        $versions = $document->versions()
            ->with('uploader')
            ->orderByDesc('version_number')
            ->get();

        return response()->json([
            'data' => DocumentVersionResource::collection($versions),
        ]);
    }

    /**
     * Upload a new version for a document.
     */
    public function store(Request $request, Document $document): JsonResponse
    {
        Gate::authorize('update', $document);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:102400'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $version = $this->documentService->uploadNewVersion(
            $document,
            $request->file('file'),
            $validated['comment'] ?? null
        );

        return response()->json([
            'data' => new DocumentVersionResource($version->load('uploader')),
            'message' => 'New version uploaded successfully.',
        ], 201);
    }

    /**
     * Download a specific version.
     */
    public function download(Document $document, DocumentVersion $version): Response
    {
        Gate::authorize('download', $document);

        return $this->documentService->downloadVersion($document, $version);
    }

    /**
     * Restore an older version.
     */
    public function restore(Document $document, DocumentVersion $version): JsonResponse
    {
        Gate::authorize('update', $document);

        $newVersion = $this->documentService->restoreVersion($document, $version);

        return response()->json([
            'data' => new DocumentVersionResource($newVersion->load('uploader')),
            'message' => 'Document restored to selected version successfully.',
        ]);
    }
}
