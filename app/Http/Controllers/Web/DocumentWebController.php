<?php

namespace App\Http\Controllers\Web;

use App\Enums\FolderType;
use App\Enums\WorkflowStatus;
use App\Exceptions\SubscriptionLimitExceededException;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Document;
use App\Models\DocumentFavorite;
use App\Models\DocumentVersion;
use App\Models\Folder;
use App\Models\Group;
use App\Models\MetadataDefinition;
use App\Models\Tag;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowInstance;
use App\Services\AccessControlService;
use App\Services\AuditService;
use App\Services\BillingService;
use App\Services\DocumentMetadataService;
use App\Services\DocumentService;
use App\Services\OcrService;
use App\Services\PreviewService;
use App\Services\WorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
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
        protected PreviewService $previewService,
        protected ?BillingService $billingService = null
    ) {
        $this->billingService = $this->billingService ?? app(BillingService::class);
    }

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
            'documentType.parent',
            'documentType.metadataDefinitions',
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
            ->whereIn('status', [
                WorkflowStatus::Pending,
                WorkflowStatus::InProgress,
                WorkflowStatus::CorrectionRequested,
            ])
            ->with([
                'workflow',
                'currentStep.approverUser',
                'currentStep.approverGroup',
                'actions.user',
                'actions.step',
                'startedBy',
            ])
            ->latest('id')
            ->first();

        // Past workflow instances if any
        $pastWorkflows = WorkflowInstance::where('document_id', $document->id)
            ->whereNotIn('status', [
                WorkflowStatus::Pending,
                WorkflowStatus::InProgress,
                WorkflowStatus::CorrectionRequested,
            ])
            ->with(['workflow', 'actions.user', 'actions.step', 'startedBy'])
            ->latest('id')
            ->take(5)
            ->get();

        $canApproveCurrentStep = $activeWorkflow
            ? app(WorkflowService::class)->canUserApproveStep($user, $activeWorkflow)
            : false;

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

        // Metadata definitions configured on this document type
        $docType = $document->documentType ?? $document->folder?->getDocumentType();
        $typeMetadataDefinitions = $docType
            ? $docType->metadataDefinitions()->orderBy('folder_metadata_definition.order')->get()
            : collect();

        // All metadata definitions available for organization
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

        // Users and groups available for sharing within the organization
        $availableUsers = User::where('organization_id', $user->organization_id)
            ->where('status', 'active')
            ->where('id', '!=', $user->id)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'email']);

        $availableGroups = Group::where('organization_id', $user->organization_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $availableWorkflows = Workflow::where('organization_id', $user->organization_id)
            ->where('is_active', true)
            ->with(['steps.approverUser', 'steps.approverGroup'])
            ->get();

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
            'canApproveCurrentStep' => $canApproveCurrentStep,
            'pastWorkflows' => $pastWorkflows,
            'isFavorite' => $isFavorite,
            'history' => $history,
            'metadataDefinitions' => $metadataDefinitions,
            'typeMetadataDefinitions' => $typeMetadataDefinitions,
            'permissions' => $permissions,
            'previewUrl' => route('documents.preview', $document),
            'availableUsers' => $availableUsers,
            'availableGroups' => $availableGroups,
            'availableWorkflows' => $availableWorkflows,
        ]);
    }

    /**
     * Show the business document import form.
     */
    public function create(Request $request): Response
    {
        $user = $request->user();
        Gate::authorize('create', Document::class);

        $departments = Folder::query()
            ->where('organization_id', $user->organization_id)
            ->where('folder_type', FolderType::Department)
            ->where('is_active', true)
            ->with([
                'children' => fn ($q) => $q->where('folder_type', FolderType::DocumentType)
                    ->where('is_active', true)
                    ->with(['metadataDefinitions' => fn ($mq) => $mq->where('is_active', true)])
                    ->orderBy('name'),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn ($dept) => [
                'id' => $dept->id,
                'name' => $dept->name,
                'description' => $dept->description,
                'document_types' => $dept->children->map(fn ($type) => [
                    'id' => $type->id,
                    'name' => $type->name,
                    'description' => $type->description,
                    'parent_id' => $type->parent_id,
                    'metadata_definitions' => $type->metadataDefinitions->map(fn ($def) => [
                        'id' => $def->id,
                        'name' => $def->name,
                        'key' => $def->key,
                        'type' => $def->type,
                        'is_required' => $def->pivot->is_required !== null ? (bool) $def->pivot->is_required : (bool) $def->is_required,
                        'order' => (int) $def->pivot->order,
                        'description' => $def->description,
                    ]),
                ])->values(),
            ])->values();

        $categories = Category::where('organization_id', $user->organization_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $tags = Tag::where('organization_id', $user->organization_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Documents/Create', [
            'departments' => $departments,
            'categories' => $categories,
            'tags' => $tags,
            'preselected' => [
                'department_id' => $request->query('department_id'),
                'document_type_id' => $request->query('document_type_id'),
            ],
        ]);
    }

    /**
     * Upload and create a new document via business form.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        Gate::authorize('create', Document::class);

        $request->validate([
            'file' => ['required', 'file', 'max:51200'], // 50MB max
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'department_id' => ['nullable', 'integer', 'exists:folders,id'],
            'document_type_id' => ['nullable', 'integer', 'exists:folders,id'],
            'folder_id' => ['nullable', 'integer', 'exists:folders,id'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'tags' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ]);

        $file = $request->file('file');
        try {
            $this->billingService->assertCanAddStorage($file->getSize(), $user->organization_id);
        } catch (SubscriptionLimitExceededException $e) {
            return back()->withErrors(['limit' => $e->getMessage()])->with('error', $e->getMessage());
        }

        $folderId = $request->input('folder_id');
        $docTypeId = $request->input('document_type_id');
        $docTypeFolder = null;

        // Automatically resolve folder_id and document_type_id
        if ($docTypeId) {
            $docTypeFolder = Folder::where('organization_id', $user->organization_id)->findOrFail($docTypeId);
            $folderId = $docTypeFolder->id;
        } elseif ($folderId) {
            $folder = Folder::where('organization_id', $user->organization_id)->findOrFail($folderId);
            $docTypeFolder = $folder->getDocumentType();
            $docTypeId = $docTypeFolder?->id;
        }

        // Pre-validate metadata against document type definitions
        $normalizedMetadata = $this->validateBusinessMetadata(
            $docTypeFolder,
            (array) $request->input('metadata', []),
            $user
        );

        $document = $this->documentService->upload([
            'organization_id' => $user->organization_id,
            'uploaded_by' => $user->id,
            'folder_id' => $folderId,
            'document_type_id' => $docTypeId,
            'name' => $request->input('name') ?: $request->file('file')->getClientOriginalName(),
            'description' => $request->input('description'),
        ], $request->file('file'));

        // Grant creator ACL
        $this->aclService->grantDocumentPermission($document, $user, 'view');
        $this->aclService->grantDocumentPermission($document, $user, 'update');
        $this->aclService->grantDocumentPermission($document, $user, 'delete');
        $this->aclService->grantDocumentPermission($document, $user, 'download');
        $this->aclService->grantDocumentPermission($document, $user, 'share');

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
        if (! empty($normalizedMetadata)) {
            $this->metadataService->setValues($document, $normalizedMetadata);
        }

        return redirect()->route('documents.show', $document)->with('success', 'Document importé avec succès.');
    }

    /**
     * Show the business document edit form.
     */
    public function edit(Request $request, Document $document): Response
    {
        $user = $request->user();
        Gate::authorize('update', $document);

        $document->load([
            'folder',
            'documentType.parent',
            'categories',
            'tags',
            'currentVersion',
            'metadataValues.definition',
        ]);

        $departments = Folder::query()
            ->where('organization_id', $user->organization_id)
            ->where('folder_type', FolderType::Department)
            ->where('is_active', true)
            ->with([
                'children' => fn ($q) => $q->where('folder_type', FolderType::DocumentType)
                    ->where('is_active', true)
                    ->with(['metadataDefinitions' => fn ($mq) => $mq->where('is_active', true)])
                    ->orderBy('name'),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn ($dept) => [
                'id' => $dept->id,
                'name' => $dept->name,
                'description' => $dept->description,
                'document_types' => $dept->children->map(fn ($type) => [
                    'id' => $type->id,
                    'name' => $type->name,
                    'description' => $type->description,
                    'parent_id' => $type->parent_id,
                    'metadata_definitions' => $type->metadataDefinitions->map(fn ($def) => [
                        'id' => $def->id,
                        'name' => $def->name,
                        'key' => $def->key,
                        'type' => $def->type,
                        'is_required' => $def->pivot->is_required !== null ? (bool) $def->pivot->is_required : (bool) $def->is_required,
                        'order' => (int) $def->pivot->order,
                        'description' => $def->description,
                    ]),
                ])->values(),
            ])->values();

        $categories = Category::where('organization_id', $user->organization_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $tags = Tag::where('organization_id', $user->organization_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        // Formatted metadata values as [key => value] map
        $currentMetadata = [];
        foreach ($document->metadataValues as $mv) {
            if ($mv->definition) {
                $currentMetadata[$mv->definition->key] = $mv->getTypedValue();
            }
        }

        $currentDepartmentId = $document->documentType?->parent_id
            ?? $document->folder?->getDepartment()?->id;

        return Inertia::render('Documents/Edit', [
            'document' => $document,
            'departments' => $departments,
            'currentDepartmentId' => $currentDepartmentId,
            'currentMetadata' => $currentMetadata,
            'categories' => $categories,
            'tags' => $tags,
        ]);
    }

    /**
     * Update document and metadata, optionally creating a new version if file replaced.
     */
    public function update(Request $request, Document $document): RedirectResponse
    {
        $user = $request->user();
        Gate::authorize('update', $document);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'document_type_id' => ['nullable', 'integer', 'exists:folders,id'],
            'file' => ['nullable', 'file', 'max:51200'], // optional replacement
            'change_notes' => ['nullable', 'string', 'max:500'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'tags' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ]);

        $updateData = [
            'name' => $request->input('name'),
            'description' => $request->input('description'),
        ];

        // If document_type_id changed
        $docTypeFolder = null;
        if ($request->filled('document_type_id')) {
            $docTypeFolder = Folder::where('organization_id', $user->organization_id)
                ->findOrFail($request->input('document_type_id'));
            $updateData['document_type_id'] = $docTypeFolder->id;
            $updateData['folder_id'] = $docTypeFolder->id;
        } else {
            $docTypeFolder = $document->documentType ?? $document->folder?->getDocumentType();
        }

        // Validate metadata against document type rules
        $normalizedMetadata = null;
        if ($request->has('metadata')) {
            $normalizedMetadata = $this->validateBusinessMetadata(
                $docTypeFolder,
                (array) $request->input('metadata', []),
                $user
            );
        }

        $document->update($updateData);

        // Update categories
        if ($request->has('category_ids')) {
            $document->categories()->sync($request->input('category_ids') ?? []);
        }

        // Update tags
        if ($request->has('tags')) {
            $tags = $request->input('tags') ?? [];
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

        // Update metadata values
        if ($normalizedMetadata !== null) {
            $this->metadataService->setValues($document, $normalizedMetadata);
        }

        // Handle file replacement -> Create a new version!
        if ($request->hasFile('file')) {
            $changeNotes = $request->input('change_notes') ?: 'Fichier remplacé lors de la modification des métadonnées';
            $this->documentService->uploadNewVersion($document, $request->file('file'), $changeNotes);
        }

        $this->auditService->success(
            action: 'document.updated',
            auditable: $document,
            user: $user,
            description: "Document '{$document->name}' et métadonnées mis à jour."
        );

        return redirect()->route('documents.show', $document)->with('success', 'Document et métadonnées mis à jour avec succès.');
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

        $file = $request->file('file');
        try {
            $this->billingService->assertCanAddStorage($file->getSize(), $document->organization_id);
        } catch (SubscriptionLimitExceededException $e) {
            return back()->withErrors(['limit' => $e->getMessage()])->with('error', $e->getMessage());
        }

        $this->documentService->uploadNewVersion(
            $document,
            $file,
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

        try {
            $this->billingService->assertCanProcessOcr(1, $document->organization_id);
        } catch (SubscriptionLimitExceededException $e) {
            return back()->withErrors(['limit' => $e->getMessage()])->with('error', $e->getMessage());
        }

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

    /**
     * Validate and normalize metadata input against document type definitions.
     *
     * @param  array<string|int, mixed>  $metadata
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    protected function validateBusinessMetadata(?Folder $docTypeFolder, array $metadata, User $user): array
    {
        $allDefs = MetadataDefinition::where('organization_id', $user->organization_id)
            ->where('is_active', true)
            ->get();
        $defsById = $allDefs->keyBy('id');
        $defsByKey = $allDefs->keyBy('key');

        $normalized = [];
        foreach ($metadata as $k => $v) {
            if (is_numeric($k) && $def = $defsById->get((int) $k)) {
                $normalized[$def->key] = $v;
            } elseif ($defsByKey->has($k)) {
                $normalized[$k] = $v;
            }
        }

        $validationErrors = [];

        // If a document type is attached, check required definitions and formats
        if ($docTypeFolder) {
            $typeDefs = $docTypeFolder->metadataDefinitions()->where('is_active', true)->get();

            foreach ($typeDefs as $def) {
                $isRequired = $def->pivot->is_required !== null ? (bool) $def->pivot->is_required : (bool) $def->is_required;
                $val = $normalized[$def->key] ?? null;

                if ($isRequired && ($val === null || $val === '')) {
                    $validationErrors["metadata.{$def->key}"] = "Le champ '{$def->name}' est obligatoire.";

                    continue;
                }

                if ($val !== null && $val !== '') {
                    if ($def->type === 'integer' && ! filter_var($val, FILTER_VALIDATE_INT) && $val !== '0' && $val !== 0) {
                        $validationErrors["metadata.{$def->key}"] = "Le champ '{$def->name}' doit être un nombre entier.";
                    } elseif ($def->type === 'decimal' && ! is_numeric($val)) {
                        $validationErrors["metadata.{$def->key}"] = "Le champ '{$def->name}' doit être un montant ou nombre décimal valide.";
                    } elseif ($def->type === 'date' && strtotime((string) $val) === false) {
                        $validationErrors["metadata.{$def->key}"] = "Le champ '{$def->name}' doit être une date valide.";
                    }
                }
            }
        }

        if (! empty($validationErrors)) {
            throw ValidationException::withMessages($validationErrors);
        }

        return $normalized;
    }
}
