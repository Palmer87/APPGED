<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Services\PreviewService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentPreviewController extends Controller
{
    public function __construct(
        protected PreviewService $previewService
    ) {}

    /**
     * Preview the current version of a document.
     */
    public function preview(Request $request, Document $document): StreamedResponse
    {
        return $this->previewService->preview($document, null, $request->user());
    }

    /**
     * Preview a specific version of a document.
     */
    public function previewVersion(Request $request, Document $document, DocumentVersion $version): StreamedResponse
    {
        return $this->previewService->preview($document, $version, $request->user());
    }
}
