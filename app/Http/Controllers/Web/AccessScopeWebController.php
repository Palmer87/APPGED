<?php

namespace App\Http\Controllers\Web;

use App\Enums\AccessScopeType;
use App\Enums\FolderType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAccessScopeRequest;
use App\Models\AccessScope;
use App\Models\Direction;
use App\Models\Folder;
use App\Models\Service;
use App\Models\User;
use App\Services\AccessScopeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AccessScopeWebController extends Controller
{
    public function __construct(
        protected AccessScopeService $scopeService
    ) {}

    /**
     * Display a listing of access scopes.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        Gate::authorize('viewAny', AccessScope::class);

        $query = AccessScope::query()
            ->where('organization_id', $user->organization_id)
            ->with(['user', 'direction', 'service', 'folder', 'document', 'organization'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('scope_type')) {
            $query->where('scope_type', $request->input('scope_type'));
        }

        $scopes = $query->paginate(20)->withQueryString()->through(fn (AccessScope $s) => [
            'id' => $s->id,
            'user_id' => $s->user_id,
            'user_name' => $s->user?->name,
            'user_email' => $s->user?->email,
            'scope_type' => $s->scope_type->value,
            'scope_type_label' => $s->scope_type->label(),
            'scope_target_name' => $s->target_name,
            'is_active' => (bool) $s->is_active,
            'created_at' => $s->created_at?->toISOString(),
        ]);

        $users = User::query()
            ->where('organization_id', $user->organization_id)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'email'])
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
            ]);

        $directions = Direction::query()
            ->where('organization_id', $user->organization_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $services = Service::query()
            ->where('organization_id', $user->organization_id)
            ->where('is_active', true)
            ->with('direction:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'direction_id'])
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'direction_name' => $s->direction?->name,
            ]);

        $documentTypes = Folder::query()
            ->where('organization_id', $user->organization_id)
            ->where('folder_type', FolderType::DocumentType)
            ->orderBy('name')
            ->get(['id', 'name']);

        $scopeTypes = collect(AccessScopeType::cases())->map(fn ($t) => [
            'value' => $t->value,
            'label' => $t->label(),
        ]);

        $can = [
            'create' => $user->can('create', AccessScope::class),
            'delete' => $user->can('delete', new AccessScope(['organization_id' => $user->organization_id])),
        ];

        return Inertia::render('AccessScopes/Index', [
            'scopes' => $scopes,
            'users' => $users,
            'directions' => $directions,
            'services' => $services,
            'documentTypes' => $documentTypes,
            'scopeTypes' => $scopeTypes,
            'filters' => [
                'user_id' => $request->input('user_id'),
                'scope_type' => $request->input('scope_type'),
            ],
            'can' => $can,
        ]);
    }

    /**
     * Store a newly created access scope.
     */
    public function store(StoreAccessScopeRequest $request): RedirectResponse
    {
        Gate::authorize('create', AccessScope::class);

        $targetUser = User::where('organization_id', $request->user()->organization_id)
            ->findOrFail($request->validated('user_id'));

        $scopeType = AccessScopeType::from($request->validated('scope_type'));

        $targets = [
            'direction_id' => $request->validated('direction_id'),
            'service_id' => $request->validated('service_id'),
            'folder_id' => $request->validated('folder_id'),
            'document_id' => $request->validated('document_id'),
        ];

        // Also support scope_id if provided by the frontend modal based on scope_type
        if ($request->filled('scope_id')) {
            $scopeId = (int) $request->input('scope_id');
            match ($scopeType) {
                AccessScopeType::Direction => $targets['direction_id'] = $scopeId,
                AccessScopeType::Service => $targets['service_id'] = $scopeId,
                AccessScopeType::DocumentType, AccessScopeType::Folder => $targets['folder_id'] = $scopeId,
                AccessScopeType::Document => $targets['document_id'] = $scopeId,
                default => null,
            };
        }

        $this->scopeService->grantScope(
            user: $targetUser,
            scopeType: $scopeType,
            targets: array_filter($targets, fn ($val) => ! is_null($val)),
            actor: $request->user()
        );

        return back()->with('success', "Périmètre d'accès accordé avec succès à {$targetUser->name}.");
    }

    /**
     * Remove the specified access scope.
     */
    public function destroy(Request $request, AccessScope $accessScope): RedirectResponse
    {
        Gate::authorize('delete', $accessScope);

        $this->scopeService->revokeScope($accessScope, $request->user());

        return back()->with('success', "Périmètre d'accès révoqué avec succès.");
    }
}
