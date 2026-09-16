<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\DocumentLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentLifecycleController extends Controller
{
    public function __construct(
        protected DocumentLifecycleService $lifecycleService
    ) {}

    /**
     * List trashed documents for the current tenant.
     */
    public function trash(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $trashed = $this->lifecycleService->getTrash($request->user(), $perPage);

        return response()->json($trashed);
    }

    /**
     * List archived documents accessible to the current user.
     */
    public function archived(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $archived = $this->lifecycleService->getArchived($request->user(), $perPage);

        return response()->json($archived);
    }

    /**
     * Archive an active document.
     */
    public function archive(Request $request, Document $document): JsonResponse
    {
        $archived = $this->lifecycleService->archive($request->user(), $document);

        return response()->json([
            'message' => 'Document archived successfully.',
            'document' => $archived,
        ]);
    }

    /**
     * Unarchive an archived document.
     */
    public function unarchive(Request $request, Document $document): JsonResponse
    {
        $unarchived = $this->lifecycleService->unarchive($request->user(), $document);

        return response()->json([
            'message' => 'Document unarchived successfully.',
            'document' => $unarchived,
        ]);
    }

    /**
     * Move a document to trash (soft delete).
     */
    public function destroy(Request $request, Document $document): JsonResponse
    {
        $this->lifecycleService->moveToTrash($request->user(), $document);

        return response()->json([
            'message' => 'Document moved to trash.',
        ]);
    }

    /**
     * Restore a document from trash.
     */
    public function restore(Request $request, Document $document): JsonResponse
    {
        $restored = $this->lifecycleService->restoreFromTrash($request->user(), $document);

        return response()->json([
            'message' => 'Document restored from trash.',
            'document' => $restored,
        ]);
    }

    /**
     * Permanently delete a trashed document and purge files.
     */
    public function forceDestroy(Request $request, Document $document): JsonResponse
    {
        $this->lifecycleService->forceDelete($request->user(), $document);

        return response()->json([
            'message' => 'Document permanently deleted.',
        ]);
    }

    /**
     * Empty all trashed documents for the current tenant.
     */
    public function emptyTrash(Request $request): JsonResponse
    {
        $count = $this->lifecycleService->emptyTrash($request->user());

        return response()->json([
            'message' => "Trash emptied successfully. {$count} document(s) permanently deleted.",
            'deleted_count' => $count,
        ]);
    }
}
