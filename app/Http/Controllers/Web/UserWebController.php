<?php

namespace App\Http\Controllers\Web;

use App\Enums\AccessScopeType;
use App\Exceptions\SubscriptionLimitExceededException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\AuditLog;
use App\Models\Direction;
use App\Models\Group;
use App\Models\Service;
use App\Models\User;
use App\Services\AccessControlService;
use App\Services\AccessScopeService;
use App\Services\AuditService;
use App\Services\BillingService;
use App\Services\OrganizationStructureService;
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
        protected AuditService $auditService,
        protected OrganizationStructureService $structureService,
        protected AccessScopeService $scopeService,
        protected ?AccessControlService $accessControlService = null,
        protected ?BillingService $billingService = null
    ) {
        $this->accessControlService = $this->accessControlService ?? app(AccessControlService::class);
        $this->billingService = $this->billingService ?? app(BillingService::class);
    }

    /**
     * Display a listing of organization users with search, filters, and pagination.
     */
    public function index(Request $request): Response
    {
        $authUser = $request->user();
        Gate::authorize('viewAny', User::class);

        app(PermissionRegistrar::class)->setPermissionsTeamId($authUser->organization_id);

        $query = User::query()
            ->with(['roles', 'groups', 'primaryService.direction', 'services.direction'])
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

        // Filter by service
        if ($request->filled('service_id')) {
            $serviceId = (int) $request->input('service_id');
            $query->where(function ($q) use ($serviceId) {
                $q->where('primary_service_id', $serviceId)
                    ->orWhereHas('services', fn ($sq) => $sq->where('services.id', $serviceId));
            });
        }

        // Filter by direction
        if ($request->filled('direction_id')) {
            $directionId = (int) $request->input('direction_id');
            $query->whereHas('primaryService', fn ($sq) => $sq->where('direction_id', $directionId));
        }

        $users = $query->paginate(15)->withQueryString();

        $teamForeignKey = config('permission.column_names.team_foreign_key', 'organization_id');
        $rolesQuery = Role::query();
        if (! $authUser->hasRole('super-admin')) {
            $rolesQuery->where(function ($q) use ($authUser, $teamForeignKey) {
                $q->where($teamForeignKey, $authUser->organization_id)
                    ->orWhereNull($teamForeignKey);
            })->where('name', '!=', 'super-admin');
        }
        $roles = $rolesQuery->orderBy('name')->get();

        $groups = Group::where('organization_id', $authUser->organization_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $directions = Direction::where('organization_id', $authUser->organization_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $services = Service::where('organization_id', $authUser->organization_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'direction_id']);

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'status', 'role', 'group_id', 'direction_id', 'service_id']),
            'roles' => $roles,
            'groups' => $groups,
            'directions' => $directions,
            'services' => $services,
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

        $teamForeignKey = config('permission.column_names.team_foreign_key', 'organization_id');
        $rolesQuery = Role::query();
        if (! $authUser->hasRole('super-admin')) {
            $rolesQuery->where(function ($q) use ($authUser, $teamForeignKey) {
                $q->where($teamForeignKey, $authUser->organization_id)
                    ->orWhereNull($teamForeignKey);
            })->where('name', '!=', 'super-admin');
        }
        $roles = $rolesQuery->orderBy('name')->get(['id', 'name']);

        $groups = Group::where('organization_id', $authUser->organization_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $directions = Direction::where('organization_id', $authUser->organization_id)
            ->where('is_active', true)
            ->with(['services' => fn ($q) => $q->where('is_active', true)->orderBy('name')])
            ->orderBy('name')
            ->get(['id', 'name']);

        $services = Service::where('organization_id', $authUser->organization_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'direction_id']);

        return Inertia::render('Users/Create', [
            'roles' => $roles,
            'groups' => $groups,
            'directions' => $directions,
            'services' => $services,
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $authUser = $request->user();
        Gate::authorize('create', User::class);

        try {
            $this->billingService->assertCanAddUser(1, $authUser->organization_id);
        } catch (SubscriptionLimitExceededException $e) {
            return back()->withErrors(['limit' => $e->getMessage()])->with('error', $e->getMessage());
        }

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
                'primary_service_id' => $validated['primary_service_id'] ?? null,
                'status' => $validated['status'],
            ]);

            // Assign role within team context
            app(PermissionRegistrar::class)->setPermissionsTeamId($authUser->organization_id);
            $user->assignRole($validated['role']);

            $this->auditService->success(
                action: 'user.role_assigned',
                auditable: $user,
                newValues: ['role' => $validated['role']],
                user: $authUser,
                description: "Rôle '{$validated['role']}' attribué à l'utilisateur '{$user->name}'."
            );

            // Sync services
            $primaryId = $validated['primary_service_id'] ?? null;
            $associatedIds = $validated['associated_service_ids'] ?? [];
            if ($primaryId || ! empty($associatedIds)) {
                $this->structureService->syncUserServices(
                    $user,
                    $primaryId,
                    $associatedIds,
                    $authUser
                );
            }

            // Assign Access Scope if specified
            if (! empty($validated['access_scope_type'])) {
                $scopeType = AccessScopeType::tryFrom($validated['access_scope_type']);
                if ($scopeType) {
                    $targets = match ($scopeType) {
                        AccessScopeType::Direction => ['direction_id' => $validated['access_scope_direction_id'] ?? null],
                        AccessScopeType::Service => ['service_id' => $validated['access_scope_service_id'] ?? null],
                        default => [],
                    };
                    $this->scopeService->grantScope(
                        $user,
                        $scopeType,
                        $targets,
                        $authUser
                    );
                }
            }

            // Attach groups
            if (! empty($validated['group_ids'])) {
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

        $user->load([
            'roles',
            'groups',
            'organization',
            'primaryService.direction',
            'services.direction',
            'accessScopes',
        ]);

        // Recent audit history for this user
        $auditLogs = AuditLog::where('user_id', $user->id)
            ->orWhere(fn ($q) => $q->where('auditable_type', User::class)->where('auditable_id', $user->id))
            ->latest('created_at')
            ->take(20)
            ->get();

        // Compute complete effective rights (roles, services, scopes, ACLs)
        $effectiveRights = $this->accessControlService->getEffectiveRights($user);

        return Inertia::render('Users/Show', [
            'user' => $user,
            'effectiveRights' => $effectiveRights,
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

        $user->load(['roles', 'groups', 'primaryService.direction', 'services']);

        $teamForeignKey = config('permission.column_names.team_foreign_key', 'organization_id');
        $rolesQuery = Role::query();
        if (! $authUser->hasRole('super-admin')) {
            $rolesQuery->where(function ($q) use ($authUser, $teamForeignKey) {
                $q->where($teamForeignKey, $authUser->organization_id)
                    ->orWhereNull($teamForeignKey);
            })->where('name', '!=', 'super-admin');
        }
        $roles = $rolesQuery->orderBy('name')->get(['id', 'name']);

        $groups = Group::where('organization_id', $authUser->organization_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $directions = Direction::where('organization_id', $authUser->organization_id)
            ->where('is_active', true)
            ->with(['services' => fn ($q) => $q->where('is_active', true)->orderBy('name')])
            ->orderBy('name')
            ->get(['id', 'name']);

        $services = Service::where('organization_id', $authUser->organization_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'direction_id']);

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
                'primary_service_id' => $user->primary_service_id,
                'direction_id' => $user->primaryService?->direction_id,
                'associated_service_ids' => $user->services->pluck('id')->toArray(),
            ],
            'roles' => $roles,
            'groups' => $groups,
            'directions' => $directions,
            'services' => $services,
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
            'primary_service_id' => $user->primary_service_id,
        ];

        DB::transaction(function () use ($validated, $user, $authUser) {
            $updateData = [
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'job_title' => $validated['job_title'] ?? null,
                'status' => $validated['status'],
                'primary_service_id' => $validated['primary_service_id'] ?? null,
            ];

            if (! empty($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            $user->update($updateData);

            // Sync role in team context if provided
            if (! empty($validated['role'])) {
                app(PermissionRegistrar::class)->setPermissionsTeamId($user->organization_id);
                $oldRole = $user->roles->first()?->name;
                $user->syncRoles([$validated['role']]);

                if ($oldRole && $oldRole !== $validated['role']) {
                    $this->auditService->success(
                        action: 'user.role_removed',
                        auditable: $user,
                        oldValues: ['role' => $oldRole],
                        user: $authUser,
                        description: "Rôle '{$oldRole}' retiré pour l'utilisateur '{$user->name}'."
                    );
                    $this->auditService->success(
                        action: 'user.role_assigned',
                        auditable: $user,
                        newValues: ['role' => $validated['role']],
                        user: $authUser,
                        description: "Rôle '{$validated['role']}' attribué à l'utilisateur '{$user->name}'."
                    );
                }
            }

            // Sync services
            $this->structureService->syncUserServices(
                $user,
                $validated['primary_service_id'] ?? null,
                $validated['associated_service_ids'] ?? [],
                $authUser
            );

            // Sync groups
            if (isset($validated['group_ids'])) {
                $validGroupIds = Group::where('organization_id', $authUser->organization_id)
                    ->whereIn('id', $validated['group_ids'])
                    ->pluck('id');
                $user->groups()->sync($validGroupIds);
            }
        });

        if ($oldValues['status'] !== $user->status) {
            $statusAction = $user->status === 'active' ? 'user.enabled' : 'user.disabled';
            $this->auditService->success(
                action: $statusAction,
                auditable: $user,
                newValues: ['status' => $user->status],
                user: $authUser,
                description: "Compte utilisateur '{$user->name}' ".($user->status === 'active' ? 'activé' : 'désactivé').'.'
            );
        }

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

        $statusAction = $newStatus === 'active' ? 'user.enabled' : 'user.disabled';
        $this->auditService->success(
            action: $statusAction,
            auditable: $user,
            newValues: ['status' => $newStatus],
            description: "Statut de l'utilisateur '{$user->name}' changé en '{$newStatus}'."
        );
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
