<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\AuditLog;
use App\Models\Group;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserWebController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Display a listing of organization users with search, filters, and pagination.
     */
    public function index(Request $request): Response
    {
        $authUser = $request->user();
        Gate::authorize('viewAny', User::class);

        app(PermissionRegistrar::class)->setPermissionsTeamId($authUser->organization_id);

        $query = User::query()
            ->with(['roles', 'groups'])
            ->latest('id');

        if (! $authUser->hasRole('super-admin')) {
            $query->where('organization_id', $authUser->organization_id);
        }

        // Search by first_name, last_name, email, or job_title
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $likeOperator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($search, $likeOperator) {
                $q->where('first_name', $likeOperator, "%{$search}%")
                    ->orWhere('last_name', $likeOperator, "%{$search}%")
                    ->orWhere('email', $likeOperator, "%{$search}%")
                    ->orWhere('job_title', $likeOperator, "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status') && in_array($request->input('status'), ['active', 'inactive'], true)) {
            $query->where('status', $request->input('status'));
        }

        // Filter by role
        if ($request->filled('role')) {
            $roleName = $request->input('role');
            $query->whereHas('roles', fn ($q) => $q->where('name', $roleName));
        }

        // Filter by group
        if ($request->filled('group_id')) {
            $groupId = (int) $request->input('group_id');
            $query->whereHas('groups', fn ($q) => $q->where('groups.id', $groupId));
        }

        $users = $query->paginate(15)->withQueryString();

        // Available roles for filter and forms
        $rolesQuery = Role::query();
        if (! $authUser->hasRole('super-admin')) {
            $rolesQuery->where(function ($q) use ($authUser) {
                $q->where('team_id', $authUser->organization_id)
                    ->orWhereNull('team_id');
            })->where('name', '!=', 'super-admin');
        }
        $roles = $rolesQuery->orderBy('name')->get();

        $groups = Group::where('organization_id', $authUser->organization_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'status', 'role', 'group_id']),
            'roles' => $roles,
            'groups' => $groups,
            'can' => [
                'create' => Gate::allows('create', User::class),
            ],
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(Request $request): Response
    {
        $authUser = $request->user();
        Gate::authorize('create', User::class);

        app(PermissionRegistrar::class)->setPermissionsTeamId($authUser->organization_id);

        $rolesQuery = Role::query();
        if (! $authUser->hasRole('super-admin')) {
            $rolesQuery->where(function ($q) use ($authUser) {
                $q->where('team_id', $authUser->organization_id)
                    ->orWhereNull('team_id');
            })->where('name', '!=', 'super-admin');
        }
        $roles = $rolesQuery->orderBy('name')->get(['id', 'name']);

        $groups = Group::where('organization_id', $authUser->organization_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Users/Create', [
            'roles' => $roles,
            'groups' => $groups,
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $authUser = $request->user();
        Gate::authorize('create', User::class);

        $validated = $request->validated();

        $user = DB::transaction(function () use ($validated, $authUser) {
            $user = User::create([
                'organization_id' => $authUser->organization_id,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'job_title' => $validated['job_title'] ?? null,
                'password' => Hash::make($validated['password']),
                'status' => $validated['status'],
            ]);

            // Assign role within team context
            app(PermissionRegistrar::class)->setPermissionsTeamId($authUser->organization_id);
            $user->assignRole($validated['role']);

            // Attach groups
            if (! empty($validated['group_ids'])) {
                // Verify groups belong to same organization
                $validGroupIds = Group::where('organization_id', $authUser->organization_id)
                    ->whereIn('id', $validated['group_ids'])
                    ->pluck('id');
                $user->groups()->sync($validGroupIds);
            }

            return $user;
        });

        $this->auditService->success(
            action: 'user.created',
            auditable: $user,
            newValues: [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'role' => $validated['role'],
                'status' => $user->status,
            ],
            description: "Utilisateur '{$user->name}' ({$user->email}) créé avec le rôle '{$validated['role']}'."
        );

        return redirect()->route('users.index')->with('success', "Utilisateur {$user->name} créé avec succès.");
    }

    /**
     * Display the specified user details and activity.
     */
    public function show(Request $request, User $user): Response
    {
        Gate::authorize('view', $user);

        app(PermissionRegistrar::class)->setPermissionsTeamId($user->organization_id);

        $user->load(['roles', 'groups', 'organization']);

        // Recent audit history for this user
        $auditLogs = AuditLog::where('user_id', $user->id)
            ->orWhere(fn ($q) => $q->where('auditable_type', User::class)->where('auditable_id', $user->id))
            ->latest('created_at')
            ->take(20)
            ->get();

        return Inertia::render('Users/Show', [
            'user' => $user,
            'auditLogs' => $auditLogs,
            'can' => [
                'update' => Gate::allows('update', $user),
                'delete' => Gate::allows('delete', $user) && $user->id !== $request->user()->id,
            ],
        ]);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(Request $request, User $user): Response
    {
        $authUser = $request->user();
        Gate::authorize('update', $user);

        app(PermissionRegistrar::class)->setPermissionsTeamId($user->organization_id);

        $user->load(['roles', 'groups']);

        $rolesQuery = Role::query();
        if (! $authUser->hasRole('super-admin')) {
            $rolesQuery->where(function ($q) use ($authUser) {
                $q->where('team_id', $authUser->organization_id)
                    ->orWhereNull('team_id');
            })->where('name', '!=', 'super-admin');
        }
        $roles = $rolesQuery->orderBy('name')->get(['id', 'name']);

        $groups = Group::where('organization_id', $authUser->organization_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Users/Edit', [
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'job_title' => $user->job_title,
                'status' => $user->status,
                'role' => $user->roles->first()?->name ?? '',
                'group_ids' => $user->groups->pluck('id')->toArray(),
            ],
            'roles' => $roles,
            'groups' => $groups,
            'isSelf' => $user->id === $authUser->id,
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $authUser = $request->user();
        Gate::authorize('update', $user);

        $validated = $request->validated();

        // Prevent self-deactivation
        if ($user->id === $authUser->id && $validated['status'] === 'inactive') {
            return back()->with('error', 'Vous ne pouvez pas désactiver votre propre compte.');
        }

        $oldValues = [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'status' => $user->status,
            'role' => $user->roles->first()?->name,
        ];

        DB::transaction(function () use ($validated, $user, $authUser) {
            $updateData = [
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'job_title' => $validated['job_title'] ?? null,
                'status' => $validated['status'],
            ];

            if (! empty($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            $user->update($updateData);

            // Sync role in team context if provided
            if (! empty($validated['role'])) {
                app(PermissionRegistrar::class)->setPermissionsTeamId($user->organization_id);
                $user->syncRoles([$validated['role']]);
            }

            // Sync groups
            if (isset($validated['group_ids'])) {
                $validGroupIds = Group::where('organization_id', $authUser->organization_id)
                    ->whereIn('id', $validated['group_ids'])
                    ->pluck('id');
                $user->groups()->sync($validGroupIds);
            }
        });

        $this->auditService->success(
            action: 'user.updated',
            auditable: $user,
            oldValues: $oldValues,
            newValues: [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'status' => $user->status,
                'role' => $validated['role'] ?? $oldValues['role'],
            ],
            description: "Profil de l'utilisateur '{$user->name}' mis à jour."
        );

        return redirect()->route('users.index')->with('success', "Utilisateur {$user->name} mis à jour avec succès.");
    }

    /**
     * Toggle active/inactive status of a user.
     */
    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        $authUser = $request->user();
        Gate::authorize('update', $user);

        if ($user->id === $authUser->id) {
            return back()->with('error', 'Vous ne pouvez pas modifier le statut de votre propre compte.');
        }

        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        $user->update(['status' => $newStatus]);

        $this->auditService->success(
            action: 'user.status_updated',
            auditable: $user,
            newValues: ['status' => $newStatus],
            description: "Statut de l'utilisateur '{$user->name}' changé en '{$newStatus}'."
        );

        return back()->with('success', "Statut de {$user->name} modifié en '{$newStatus}'.");
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $authUser = $request->user();
        Gate::authorize('delete', $user);

        if ($user->id === $authUser->id) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $userName = $user->name;
        $userEmail = $user->email;

        $user->delete();

        $this->auditService->success(
            action: 'user.deleted',
            auditable: $user,
            oldValues: ['email' => $userEmail, 'name' => $userName],
            description: "Utilisateur '{$userName}' ({$userEmail}) supprimé."
        );

        return redirect()->route('users.index')->with('success', "Utilisateur {$userName} supprimé avec succès.");
    }
}
