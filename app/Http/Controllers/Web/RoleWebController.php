<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleWebController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Display a listing of organization roles.
     */
    public function index(Request $request): Response
    {
        $authUser = $request->user();
        Gate::authorize('viewAny', Role::class);

        app(PermissionRegistrar::class)->setPermissionsTeamId($authUser->organization_id);

        $teamForeignKey = config('permission.column_names.team_foreign_key', 'team_id');

        $query = Role::query()
            ->where($teamForeignKey, $authUser->organization_id)
            ->where('guard_name', 'web')
            ->withCount(['users', 'permissions'])
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $likeOperator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where('name', $likeOperator, "%{$search}%");
        }

        $roles = $query->paginate(15)->withQueryString();

        return Inertia::render('Roles/Index', [
            'roles' => $roles,
            'filters' => [
                'search' => $request->input('search', ''),
            ],
            'can' => [
                'create' => $authUser->can('create', Role::class),
            ],
        ]);
    }

    /**
     * Show the form for creating a new role.
     */
    public function create(Request $request): Response
    {
        Gate::authorize('create', Role::class);

        return Inertia::render('Roles/Create', [
            'permissionsGrouped' => $this->getGroupedPermissions(),
        ]);
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $authUser = $request->user();
        Gate::authorize('create', Role::class);

        $validated = $request->validated();
        $teamId = $authUser->organization_id;

        app(PermissionRegistrar::class)->setPermissionsTeamId($teamId);

        $teamForeignKey = config('permission.column_names.team_foreign_key', 'team_id');

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
            $teamForeignKey => $teamId,
        ]);

        if (! empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        $this->auditService->success(
            action: 'role.created',
            auditable: $role,
            newValues: [
                'name' => $role->name,
                'permissions' => $validated['permissions'] ?? [],
            ],
            description: "Rôle '{$role->name}' créé avec ".count($validated['permissions'] ?? []).' permission(s).'
        );

        return redirect()->route('roles.index')->with('success', "Le rôle '{$role->name}' a été créé avec succès.");
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit(Request $request, Role $role): Response
    {
        $authUser = $request->user();
        app(PermissionRegistrar::class)->setPermissionsTeamId($authUser->organization_id);

        Gate::authorize('update', $role);

        $role->load('permissions');

        return Inertia::render('Roles/Edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'team_id' => $role->team_id,
                'permissions' => $role->permissions->pluck('name')->toArray(),
            ],
            'isSystemRole' => in_array($role->name, ['super-admin', 'admin'], true),
            'permissionsGrouped' => $this->getGroupedPermissions(),
        ]);
    }

    /**
     * Update the specified role in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $authUser = $request->user();
        app(PermissionRegistrar::class)->setPermissionsTeamId($authUser->organization_id);

        Gate::authorize('update', $role);

        $validated = $request->validated();
        $oldValues = [
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name')->toArray(),
        ];

        // System roles cannot have their name changed
        if (! in_array($role->name, ['super-admin', 'admin'], true) && isset($validated['name'])) {
            $role->name = $validated['name'];
            $role->save();
        }

        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        $this->auditService->success(
            action: 'role.updated',
            auditable: $role,
            oldValues: $oldValues,
            newValues: [
                'name' => $role->name,
                'permissions' => $validated['permissions'] ?? [],
            ],
            description: "Rôle '{$role->name}' mis à jour."
        );

        return redirect()->route('roles.index')->with('success', "Le rôle '{$role->name}' a été mis à jour avec succès.");
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy(Request $request, Role $role): RedirectResponse
    {
        $authUser = $request->user();
        app(PermissionRegistrar::class)->setPermissionsTeamId($authUser->organization_id);

        Gate::authorize('delete', $role);

        // System roles protection
        if (in_array($role->name, ['super-admin', 'admin'], true)) {
            return back()->with('error', 'Les rôles système ne peuvent pas être supprimés.');
        }

        // Prevent deletion if role is assigned to any user
        if ($role->users()->count() > 0) {
            return back()->with('error', "Impossible de supprimer ce rôle car il est actuellement attribué à {$role->users()->count()} utilisateur(s).");
        }

        $roleName = $role->name;
        $role->delete();

        $this->auditService->success(
            action: 'role.deleted',
            auditable: $role,
            oldValues: ['name' => $roleName],
            description: "Rôle '{$roleName}' supprimé."
        );

        return redirect()->route('roles.index')->with('success', "Le rôle '{$roleName}' a été supprimé avec succès.");
    }

    /**
     * Get all permissions grouped by logical functional domain.
     */
    private function getGroupedPermissions(): array
    {
        $domainLabels = [
            'documents' => 'Documents',
            'folders' => 'Dossiers',
            'users' => 'Utilisateurs',
            'groups' => 'Groupes',
            'categories' => 'Catégories',
            'tags' => 'Étiquettes (Tags)',
            'metadata' => 'Métadonnées',
            'workflows' => 'Circuits & Workflows',
            'comments' => 'Commentaires',
            'audit' => 'Journal d\'Audit',
            'settings' => 'Paramètres Système',
        ];

        $allPermissions = Permission::orderBy('name')->get();
        $grouped = [];

        foreach ($allPermissions as $permission) {
            $parts = explode('.', $permission->name, 2);
            $domain = $parts[0] ?? 'general';
            $action = $parts[1] ?? $permission->name;

            if (! isset($grouped[$domain])) {
                $grouped[$domain] = [
                    'domain' => $domain,
                    'label' => $domainLabels[$domain] ?? ucfirst($domain),
                    'permissions' => [],
                ];
            }

            $grouped[$domain]['permissions'][] = [
                'id' => $permission->id,
                'name' => $permission->name,
                'action' => $action,
            ];
        }

        return array_values($grouped);
    }
}
