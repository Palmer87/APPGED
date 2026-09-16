<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DocumentResource;
use App\Models\AuditLog;
use App\Models\Document;
use App\Services\AccessControlService;
use App\Services\DocumentLifecycleService;
use App\Services\DocumentService;
use App\Services\PreviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class DocumentController extends Controller
{
    public function __construct(
        protected DocumentService $documentService,
        protected DocumentLifecycleService $lifecycleService,
        protected AccessControlService $aclService,
        protected PreviewService $previewService
    ) {}

    /**
     * List accessible documents for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->can('documents.view')) {
            abort(403, 'Unauthorized to view documents.');
        }

        $perPage = max(1, min((int) $request->input('per_page', 25), 100));

        $query = Document::query()
            ->with(['creator', 'folder', 'categories', 'tags', 'currentVersion', 'favorites'])
            ->withCount('versions');

        $this->aclService->applyAccessScope($query, $user, 'view');

        if ($request->filled('folder_id')) {
            $folderId = $request->input('folder_id');
            if ($folderId === 'null' || $folderId === '') {
                $query->whereNull('folder_id');
            } else {
                $query->where('folder_id', (int) $folderId);
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        } else {
            $query->where('status', 'active');
        }

        if ($request->filled('category_id')) {
            $query->whereHas('categories', fn ($q) => $q->where('categories.id', (int) $request->input('category_id')));
        }

        if ($request->filled('tag_id')) {
            $query->whereHas('tags', fn ($q) => $q->where('tags.id', (int) $request->input('tag_id')));
        }

        $documents = $query->latest('id')->paginate($perPage);

        return response()->json([
            'data' => DocumentResource::collection($documents),
            'meta' => [
                'current_page' => $documents->currentPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
                'last_page' => $documents->lastPage(),
            ],
        ]);
    }

    /**
     * Upload and store a new document.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        Gate::authorize('create', Document::class);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:102400'], // 100MB max
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'folder_id' => ['nullable', 'integer', 'exists:folders,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
        ]);

        $docData = [
            'organization_id' => $user->organization_id,
            'uploaded_by' => $user->id,
            'name' => $validated['name'] ?? null,
            'description' => $validated['description'] ?? null,
            'folder_id' => $validated['folder_id'] ?? null,
        ];

        $document = $this->documentService->upload($docData, $request->file('file'));

        if (! empty($validated['category_id'])) {
            $this->documentService->attachCategory($document, $validated['category_id']);
        }

        if (! empty($validated['tag_ids'])) {
            $this->documentService->syncTags($document, $validated['tag_ids']);
        }

        // Grant creator access in ACL
        $this->aclService->grantDocumentPermission($document, $user, 'view');
        $this->aclService->grantDocumentPermission($document, $user, 'update');
        $this->aclService->grantDocumentPermission($document, $user, 'delete');
        $this->aclService->grantDocumentPermission($document, $user, 'download');
        $this->aclService->grantDocumentPermission($document, $user, 'share');

        return response()->json([
            'data' => new DocumentResource($document->fresh(['creator', 'folder', 'categories', 'tags', 'currentVersion'])),
            'message' => 'Document uploaded successfully.',
        ], 201);
    }

    /**
     * Show document details.
     */
    public function show(Document $document): JsonResponse
    {
        Gate::authorize('view', $document);

        $document->load(['creator', 'folder', 'categories', 'tags', 'currentVersion', 'favorites'])
            ->loadCount('versions');

        return response()->json([
            'data' => new DocumentResource($document),
        ]);
    }

    /**
     * Update document metadata.
     */
    public function update(Request $request, Document $document): JsonResponse
    {
        Gate::authorize('update', $document);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'folder_id' => ['nullable', 'integer', 'exists:folders,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
        ]);

        $attributes = array_filter([
            'name' => $validated['name'] ?? null,
            'description' => array_key_exists('description', $validated) ? $validated['description'] : null,
            'folder_id' => array_key_exists('folder_id', $validated) ? $validated['folder_id'] : null,
        ], fn ($v) => $v !== null || array_key_exists('folder_id', $validated) || array_key_exists('description', $validated));

        if (! empty($attributes)) {
            $document->update($attributes);
        }

        if (array_key_exists('category_id', $validated)) {
            if ($validated['category_id']) {
                $this->documentService->attachCategory($document, $validated['category_id']);
            } else {
                $document->categories()->detach();
            }
        }

        if (array_key_exists('tag_ids', $validated)) {
            $this->documentService->syncTags($document, $validated['tag_ids'] ?? []);
        }

        return response()->json([
            'data' => new DocumentResource($document->fresh(['creator', 'folder', 'categories', 'tags', 'currentVersion'])),
            'message' => 'Document updated successfully.',
        ]);
    }

    /**
     * Soft-delete document (move to trash).
     */
    public function destroy(Request $request, Document $document): JsonResponse
    {
        $user = $request->user();
        Gate::authorize('delete', $document);

        $this->lifecycleService->moveToTrash($user, $document);

        return response()->json([
            'message' => 'Document moved to trash successfully.',
        ]);
    }

    /**
     * Download document file.
     */
    public function download(Document $document): Response
    {
        Gate::authorize('download', $document);

        return $this->documentService->download($document);
    }

    /**
     * Preview document file.
     */
    public function preview(Document $document): Response
    {
        Gate::authorize('view', $document);

        return $this->previewService->preview($document);
    }

    /**
     * Get document audit history.
     */
    public function history(Request $request, Document $document): JsonResponse
    {
        Gate::authorize('view', $document);

        $perPage = max(1, min((int) $request->input('per_page', 25), 100));

        $logs = AuditLog::where('auditable_type', Document::class)
            ->where('auditable_id', $document->id)
            ->with('user')
            ->latest('created_at')
            ->paginate($perPage);

        return response()->json([
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'last_page' => $logs->lastPage(),
            ],
        ]);
    }
}
