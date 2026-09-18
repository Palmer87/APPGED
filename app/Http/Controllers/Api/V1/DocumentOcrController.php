<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DocumentOcrResource;
use App\Models\Document;
use App\Models\DocumentOcr;
use App\Models\DocumentVersion;
use App\Services\OcrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class DocumentOcrController extends Controller
{
    public function __construct(
        protected OcrService $ocrService
    ) {}

    /**
     * Get OCR status and extracted text for a document.
     */
    public function show(Request $request, Document $document): JsonResponse
    {
        $user = $request->user();

        // Multi-tenant check
        if (! $user->hasRole('super-admin') && $user->organization_id !== $document->organization_id) {
            abort(403, 'Unauthorized access to document.');
        }

        // Permission check
        Gate::authorize('view', $document);

        $versionId = $request->query('version_id');
        $query = DocumentOcr::where('document_id', $document->id);

        if ($versionId) {
            $query->where('document_version_id', (int) $versionId);
        } else {
            // Default to latest version or document-level OCR
            $query->latest('id');
        }

        $ocr = $query->first();

        if (! $ocr) {
            return response()->json([
                'status' => 'not_processed',
                'status_label' => 'Non traité',
                'has_text' => false,
                'extracted_text' => null,
                'error_message' => null,
                'message' => 'Aucun traitement OCR trouvé pour ce document.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => new DocumentOcrResource($ocr),
        ]);
    }

    /**
     * Trigger / retry OCR processing for a document.
     */
    public function retry(Request $request, Document $document): JsonResponse
    {
        $user = $request->user();

        // Multi-tenant check
        if (! $user->hasRole('super-admin') && $user->organization_id !== $document->organization_id) {
            abort(403, 'Unauthorized access to document.');
        }

        // Permission check (requires update permission on document)
        Gate::authorize('update', $document);

        $version = null;
        if ($request->filled('version_id')) {
            $version = DocumentVersion::where('document_id', $document->id)
                ->findOrFail($request->input('version_id'));
        }

        $ocr = $this->ocrService->dispatchOcr($document, $version, $user);

        return response()->json([
            'message' => 'Traitement OCR planifié avec succès.',
            'data' => new DocumentOcrResource($ocr),
        ], Response::HTTP_ACCEPTED);
    }
}
