<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\DocumentLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DocumentLifecycleController extends Controller
{
    public function __construct(
        protected DocumentLifecycleService $lifecycleService
    ) {}

    /**
     * List trashed documents for the current tenant.
     */
    public function trash(Request $request): JsonResponse|Response
    {
        $perPage = (int) $request->input('per_page', 15);
        $trashed = $this->lifecycleService->getTrash($request->user(), $perPage);

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json($trashed);
        }

        return Inertia::render('Trash/Index', ['documents' => $trashed]);
    }

    /**
     * List archived documents accessible to the current user.
     */
    public function archived(Request $request): JsonResponse|Response
    {
        $perPage = (int) $request->input('per_page', 15);
        $archived = $this->lifecycleService->getArchived($request->user(), $perPage);

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json($archived);
        }

        return Inertia::render('Archive/Index', ['documents' => $archived]);
    }

    /**
     * Archive an active document.
     */
    public function archive(Request $request, Document $document): mixed
    {
        $archived = $this->lifecycleService->archive($request->user(), $document);

        if ($request->header('X-Inertia')) {
            return back()->with('success', 'Document archivé avec succès.');
        }

        return response()->json([
            'message' => 'Document archived successfully.',
            'document' => $archived,
        ]);
    }

    /**
     * Unarchive an archived document.
     */
    public function unarchive(Request $request, Document $document): mixed
    {
        $unarchived = $this->lifecycleService->unarchive($request->user(), $document);

        if ($request->header('X-Inertia')) {
            return back()->with('success', 'Document désarchivé avec succès.');
        }

        return response()->json([
            'message' => 'Document unarchived successfully.',
            'document' => $unarchived,
        ]);
    }

    /**
     * Soft delete a document.
     */
    public function destroy(Request $request, Document $document): mixed
    {
        $this->lifecycleService->moveToTrash($request->user(), $document);

        if ($request->header('X-Inertia')) {
            return back()->with('success', 'Document déplacé dans la corbeille.');
        }

        return response()->json([
            'message' => 'Document moved to trash.',
        ]);
    }

    /**
     * Restore a soft-deleted document.
     */
    public function restore(Request $request, Document $document): mixed
    {
        $restored = $this->lifecycleService->restoreFromTrash($request->user(), $document);

        if ($request->header('X-Inertia')) {
            return back()->with('success', 'Document restauré avec succès.');
        }

        return response()->json([
            'message' => 'Document restored from trash.',
            'document' => $restored,
        ]);
    }

    /**
     * Permanently delete a soft-deleted document.
     */
    public function forceDestroy(Request $request, Document $document): mixed
    {
        $this->lifecycleService->forceDelete($request->user(), $document);

        if ($request->header('X-Inertia')) {
            return back()->with('success', 'Document supprimé définitivement.');
        }

        return response()->json([
            'message' => 'Document permanently deleted.',
        ]);
    }

    /**
     * Empty the trash for the current tenant.
     */
    public function emptyTrash(Request $request): mixed
    {
        $count = $this->lifecycleService->emptyTrash($request->user());

        if ($request->header('X-Inertia')) {
            return back()->with('success', "Corbeille vidée ({$count} documents supprimés).");
        }

        return response()->json([
            'message' => "Trash emptied successfully. {$count} document(s) permanently deleted.",
            'deleted_count' => $count,
        ]);
    }
}
