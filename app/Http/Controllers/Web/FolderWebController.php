<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Folder;
use App\Services\AccessControlService;
use App\Services\FolderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class FolderWebController extends Controller
{
    public function __construct(
        protected FolderService $folderService,
        protected AccessControlService $aclService
    ) {}

    /**
     * Display root folders and documents in the organization.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        if (! $user->can('folders.view') && ! $user->hasRole('admin') && ! $user->hasRole('super-admin')) {
            abort(403, 'Unauthorized to view folders.');
        }

        return $this->renderExplorer($request, null);
    }

    /**
     * Display the contents of a specific folder.
     */
    public function show(Request $request, Folder $folder): Response
    {
        Gate::authorize('view', $folder);

        return $this->renderExplorer($request, $folder);
    }

    /**
     * Render the unified Windows Explorer / Finder view.
     */
    protected function renderExplorer(Request $request, ?Folder $currentFolder): Response
    {
        $user = $request->user();
        $orgId = $user->organization_id;
        $search = trim($request->input('search', ''));
        $likeOperator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        // 1. Build Breadcrumbs & parent reference
        $breadcrumbs = [
            [
                'id' => null,
                'name' => 'Accueil',
                'href' => route('folders.index'),
            ],
        ];

        $parentFolder = null;

        if ($currentFolder) {
            $ancestors = [];
            $cursor = $currentFolder->parent;
            $parentFolder = $cursor ? [
                'id' => $cursor->id,
                'name' => $cursor->name,
                'href' => route('folders.show', $cursor->id),
            ] : [
                'id' => null,
                'name' => 'Accueil',
                'href' => route('folders.index'),
            ];

            while ($cursor) {
                array_unshift($ancestors, [
                    'id' => $cursor->id,
                    'name' => $cursor->name,
                    'href' => route('folders.show', $cursor->id),
                ]);
                $cursor = $cursor->parent;
            }

            $breadcrumbs = array_merge($breadcrumbs, $ancestors, [
                [
                    'id' => $currentFolder->id,
                    'name' => $currentFolder->name,
                    'href' => route('folders.show', $currentFolder->id),
                ],
            ]);
        }

        // 2. Subfolders Query (displayed first)
        $subfoldersQuery = Folder::query()
            ->where('organization_id', $orgId)
            ->withCount(['documents', 'children as subfolders_count'])
            ->orderBy('name');

        if ($currentFolder) {
            $subfoldersQuery->where('parent_id', $currentFolder->id);
        } else {
            $subfoldersQuery->whereNull('parent_id');
        }

        if ($search !== '') {
            $subfoldersQuery->where('name', $likeOperator, "%{$search}%");
        }

        $subfolders = $subfoldersQuery->get()->map(function ($f) {
            return [
                'id' => $f->id,
                'name' => $f->name,
                'description' => $f->description,
                'parent_id' => $f->parent_id,
                'path' => $f->path,
                'documents_count' => $f->documents_count ?? 0,
                'subfolders_count' => $f->subfolders_count ?? 0,
                'items_count' => ($f->documents_count ?? 0) + ($f->subfolders_count ?? 0),
                'updated_at' => $f->updated_at?->toISOString(),
            ];
        });

        // 3. Documents Query in current folder
        $documentsQuery = Document::query()
            ->where('organization_id', $orgId)
            ->whereNull('deleted_at');

        if ($currentFolder) {
            $documentsQuery->where('folder_id', $currentFolder->id);
        } else {
            $documentsQuery->whereNull('folder_id');
        }

        $documentsQuery = $this->aclService->applyAccessScope($documentsQuery, $user, 'view');

        if ($search !== '') {
            $documentsQuery->where(function ($q) use ($search, $likeOperator) {
                $q->where('name', $likeOperator, "%{$search}%")
                    ->orWhere('file_name', $likeOperator, "%{$search}%");
            });
        }

        $documents = $documentsQuery
            ->with(['creator', 'categories', 'tags'])
            ->orderBy('name')
            ->get()
            ->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'name' => $doc->name,
                    'file_name' => $doc->file_name,
                    'mime_type' => $doc->mime_type,
                    'extension' => strtolower($doc->extension ?? pathinfo($doc->file_name ?? '', PATHINFO_EXTENSION)),
                    'size' => $doc->size,
                    'size_human' => $this->formatBytes($doc->size),
                    'status' => $doc->status,
                    'folder_id' => $doc->folder_id,
                    'updated_at' => $doc->updated_at?->toISOString(),
                    'created_at' => $doc->created_at?->toISOString(),
                    'creator' => $doc->creator ? [
                        'id' => $doc->creator->id,
                        'name' => $doc->creator->name,
                    ] : null,
                    'category' => $doc->categories->first()?->name,
                ];
            });

        // 4. Lightweight Folder Tree of the entire organization for navigation & move modals
        $tree = Folder::query()
            ->where('organization_id', $orgId)
            ->select(['id', 'name', 'parent_id'])
            ->orderBy('name')
            ->get()
            ->map(fn ($f) => [
                'id' => $f->id,
                'name' => $f->name,
                'parent_id' => $f->parent_id,
            ]);

        // 5. User permissions context
        $can = [
            'create_folder' => $user->can('create', Folder::class),
            'upload_document' => $user->can('create', Document::class),
            'update_folder' => $currentFolder ? $user->can('update', $currentFolder) : false,
            'delete_folder' => $currentFolder ? $user->can('delete', $currentFolder) : false,
        ];

        return Inertia::render('Folders/Index', [
            'currentFolder' => $currentFolder ? [
                'id' => $currentFolder->id,
                'name' => $currentFolder->name,
                'description' => $currentFolder->description,
                'parent_id' => $currentFolder->parent_id,
                'path' => $currentFolder->path,
                'created_at' => $currentFolder->created_at?->toISOString(),
                'updated_at' => $currentFolder->updated_at?->toISOString(),
            ] : null,
            'breadcrumbs' => $breadcrumbs,
            'parentFolder' => $parentFolder,
            'subfolders' => $subfolders,
            'folders' => $subfolders,
            'documents' => $documents,
            'tree' => $tree,
            'filters' => [
                'search' => $search,
            ],
            'can' => $can,
        ]);
    }

    /**
     * Create a new folder.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Folder::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'parent_id' => ['nullable', 'integer', 'exists:folders,id'],
        ]);

        if (! empty($validated['parent_id'])) {
            Folder::where('organization_id', $request->user()->organization_id)
                ->findOrFail($validated['parent_id']);
        }

        $folder = $this->folderService->create($validated, $request->user());

        return back()->with('success', "Dossier '{$folder->name}' créé avec succès.");
    }

    /**
     * Update an existing folder (rename, description, move parent).
     */
    public function update(Request $request, Folder $folder): RedirectResponse
    {
        Gate::authorize('update', $folder);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'parent_id' => ['nullable', 'integer'],
        ]);

        $newParentId = ! empty($validated['parent_id']) ? (int) $validated['parent_id'] : null;

        if ($newParentId !== (int) $folder->parent_id) {
            $newParent = null;
            if ($newParentId) {
                $newParent = Folder::where('organization_id', $request->user()->organization_id)
                    ->findOrFail($newParentId);
            }

            try {
                $this->folderService->move($folder, $newParent);
            } catch (\Throwable $e) {
                return back()->with('error', 'Impossible de déplacer le dossier : '.$e->getMessage());
            }
        }

        $folder->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('success', "Dossier '{$folder->name}' mis à jour avec succès.");
    }

    /**
     * Delete a folder.
     */
    public function destroy(Request $request, Folder $folder): RedirectResponse
    {
        Gate::authorize('delete', $folder);

        $folderName = $folder->name;
        $parentId = $folder->parent_id;

        $this->folderService->delete($folder);

        $targetUrl = $parentId ? route('folders.show', $parentId) : route('folders.index');

        return redirect($targetUrl)->with('success', "Dossier '{$folderName}' supprimé avec succès.");
    }

    /**
     * Format bytes into human-readable string.
     */
    protected function formatBytes(?int $bytes, int $precision = 1): string
    {
        if (! $bytes || $bytes <= 0) {
            return '0 o';
        }

        $units = ['o', 'Ko', 'Mo', 'Go', 'To'];
        $pow = min((int) floor(log($bytes, 1024)), count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }
}
