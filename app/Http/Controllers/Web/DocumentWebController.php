<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Document;
use App\Models\DocumentFavorite;
use App\Models\DocumentVersion;
use App\Models\Folder;
use App\Models\MetadataDefinition;
use App\Models\Tag;
use App\Models\WorkflowInstance;
use App\Services\AccessControlService;
use App\Services\AuditService;
use App\Services\DocumentMetadataService;
use App\Services\DocumentService;
use App\Services\OcrService;
use App\Services\PreviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentWebController extends Controller
{
    public function __construct(
        protected DocumentService $documentService,
        protected AccessControlService $aclService,
        protected AuditService $auditService,
        protected DocumentMetadataService $metadataService,
        protected PreviewService $previewService
    ) {}

    /**
     * Display a listing of accessible documents.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = Document::query()
            ->where('documents.organization_id', $user->organization_id)
            ->where('documents.status', '!=', 'archived')
            ->with(['folder', 'categories', 'tags', 'creator', 'currentVersion']);

        // ACL scope
        $this->aclService->applyAccessScope($query, $user);

        // Filters
        if ($folderId = $request->input('folder_id')) {
            $query->where('documents.folder_id', $folderId);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('documents.name', 'ilike', "%{$search}%")
                    ->orWhere('documents.description', 'ilike', "%{$search}%");
            });
        }

        if ($extension = $request->input('extension')) {
            $query->where('documents.extension', strtolower($extension));
        }

        if ($status = $request->input('status')) {
            $query->where('documents.status', $status);
        }

        if ($categoryId = $request->input('category_id')) {
            $query->whereHas('categories', fn ($q) => $q->where('categories.id', $categoryId));
        }

        if ($tagId = $request->input('tag_id')) {
            $query->whereHas('tags', fn ($q) => $q->where('tags.id', $tagId));
        }

        // Sorting
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        $allowedSorts = ['name', 'size', 'created_at', 'updated_at', 'status'];

        if (in_array($sortField, $allowedSorts, true)) {
            $query->orderBy("documents.{$sortField}", $sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest('documents.created_at');
        }

        $perPage = max(5, min((int) $request->input('per_page', 15), 100));
        $documents = $query->paginate($perPage)->withQueryString();

        // Extra context for UI (upload modal & filter dropdowns)
        $folders = Folder::where('organization_id', $user->organization_id)
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id', 'path']);

        $categories = Category::where('organization_id', $user->organization_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $tags = Tag::where('organization_id', $user->organization_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $currentFolder = $folderId ? Folder::find($folderId) : null;

        return Inertia::render('Documents/Index', [
            'documents' => $documents,
            'filters' => $request->only(['search', 'folder_id', 'extension', 'status', 'category_id', 'tag_id', 'sort', 'direction']),
            'currentFolder' => $currentFolder,
            'folders' => $folders,
            'categories' => $categories,
            'tags' => $tags,
        ]);
    }

    /**
     * Display detailed document view.
     */
    public function show(Request $request, Document $document): Response
    {
        $user = $request->user();
        Gate::authorize('view', $document);

        // Load document relations
        $document->load([
            'folder',
            'categories',
            'tags',
            'creator',
            'versions.uploader',
            'versions.creator',
            'versions.ocr',
            'currentOcr',
            'metadataValues.definition',
            'shares.user',
            'shares.group',
            'comments' => fn ($q) => $q->whereNull('parent_id')->with(['user', 'replies.user'])->latest(),
        ]);

        // Active workflow instance if any
        $activeWorkflow = WorkflowInstance::where('document_id', $document->id)
            ->with(['workflow', 'currentStep', 'reviews.user'])
            ->latest()
            ->first();

        // Is favorite
        $isFavorite = DocumentFavorite::where('user_id', $user->id)
            ->where('document_id', $document->id)
            ->exists();

        // Audit history
        $history = AuditLog::where('auditable_type', Document::class)
            ->where('auditable_id', $document->id)
            ->with('user')
            ->latest('created_at')
            ->take(20)
            ->get();

        // Metadata definitions available for organization
        $metadataDefinitions = MetadataDefinition::where('organization_id', $user->organization_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // User capabilities on this document
        $permissions = [
            'can_edit' => Gate::forUser($user)->allows('update', $document),
            'can_delete' => Gate::forUser($user)->allows('delete', $document),
            'can_share' => Gate::forUser($user)->allows('share', $document),
            'can_download' => Gate::forUser($user)->allows('view', $document),
            'can_version' => Gate::forUser($user)->allows('update', $document),
            'can_archive' => Gate::forUser($user)->allows('update', $document),
        ];

        // Audit previewed/viewed
        $this->auditService->success(
            action: 'document.viewed',
            auditable: $document,
            user: $user,
            description: "Document '{$document->name}' viewed."
        );

        return Inertia::render('Documents/Show', [
            'document' => $document,
            'activeWorkflow' => $activeWorkflow,
            'isFavorite' => $isFavorite,
            'history' => $history,
            'metadataDefinitions' => $metadataDefinitions,
            'permissions' => $permissions,
            'previewUrl' => route('documents.preview', $document),
        ]);
    }

    /**
     * Upload and create a new document.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:51200'], // 50MB max
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'folder_id' => ['nullable', 'integer', 'exists:folders,id'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'tags' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ]);

        $user = $request->user();

        $document = $this->documentService->upload([
            'organization_id' => $user->organization_id,
            'uploaded_by' => $user->id,
            'folder_id' => $request->input('folder_id'),
            'name' => $request->input('name') ?: $request->file('file')->getClientOriginalName(),
            'description' => $request->input('description'),
        ], $request->file('file'));

        // Attach categories
        if ($categoryIds = $request->input('category_ids')) {
            $document->categories()->sync($categoryIds);
        }

        // Attach tags
        if ($tags = $request->input('tags')) {
            $tagIds = [];
            foreach ($tags as $tagName) {
                if (is_numeric($tagName)) {
                    $tagIds[] = (int) $tagName;
                } else {
                    $tag = Tag::firstOrCreate([
                        'organization_id' => $user->organization_id,
                        'name' => trim($tagName),
                    ]);
                    $tagIds[] = $tag->id;
                }
            }
            $document->tags()->sync($tagIds);
        }

        // Custom metadata values
        if ($metadata = $request->input('metadata')) {
            foreach ($metadata as $keyOrId => $value) {
                if ($value !== null && $value !== '') {
                    $def = is_numeric($keyOrId)
                        ? MetadataDefinition::where('organization_id', $user->organization_id)->find($keyOrId)
                        : MetadataDefinition::where('organization_id', $user->organization_id)->where('key', $keyOrId)->first();

                    if ($def) {
                        $this->metadataService->setValue($document, $def, $value);
                    }
                }
            }
        }

        return redirect()->route('documents.show', $document)->with('success', 'Document téléversé avec succès.');
    }

    /**
     * Download the latest version of a document.
     */
    public function download(Request $request, Document $document): StreamedResponse
    {
        return $this->documentService->download($document);
    }

    /**
     * Download a specific version of a document.
     */
    public function downloadVersion(Request $request, Document $document, DocumentVersion $version): StreamedResponse
    {
        return $this->documentService->downloadVersion($document, $version);
    }

    /**
     * Upload a new version for an existing document.
     */
    public function storeVersion(Request $request, Document $document): RedirectResponse
    {
        Gate::authorize('update', $document);

        $request->validate([
            'file' => ['required', 'file', 'max:51200'],
            'change_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->documentService->uploadNewVersion(
            $document,
            $request->file('file'),
            $request->user(),
            $request->input('change_notes')
        );

        return back()->with('success', 'Nouvelle version ajoutée avec succès.');
    }

    /**
     * Restore an older version as the active version.
     */
    public function restoreVersion(Request $request, Document $document, DocumentVersion $version): RedirectResponse
    {
        Gate::authorize('update', $document);

        if ($version->document_id !== $document->id) {
            abort(404, 'Version does not belong to this document');
        }

        $this->documentService->restoreVersion($document, $version, $request->user());

        return back()->with('success', "Version {$version->version_number} restaurée avec succès.");
    }

    /**
     * Retry OCR processing for a document.
     */
    public function retryOcr(Request $request, Document $document): RedirectResponse
    {
        Gate::authorize('update', $document);

        $version = null;
        if ($request->filled('version_id')) {
            $version = DocumentVersion::where('document_id', $document->id)
                ->findOrFail($request->input('version_id'));
        }

        app(OcrService::class)->dispatchOcr($document, $version, $request->user());

        return back()->with('success', 'Traitement OCR planifié avec succès.');
    }

    /**
     * Move a document to a different folder (or root).
     */
    public function move(Request $request, Document $document): RedirectResponse
    {
        Gate::authorize('update', $document);

        $validated = $request->validate([
            'folder_id' => ['nullable', 'integer', 'exists:folders,id'],
        ]);

        $targetFolder = null;
        if (! empty($validated['folder_id'])) {
            $targetFolder = Folder::where('organization_id', $request->user()->organization_id)
                ->findOrFail($validated['folder_id']);
        }

        $this->documentService->move($document, $targetFolder);

        $folderName = $targetFolder ? "'{$targetFolder->name}'" : 'la racine';

        return back()->with('success', "Document '{$document->name}' déplacé vers {$folderName}.");
    }
}
