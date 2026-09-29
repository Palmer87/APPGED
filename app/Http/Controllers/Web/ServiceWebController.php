<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Models\Direction;
use App\Models\Service;
use App\Models\User;
use App\Services\OrganizationStructureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ServiceWebController extends Controller
{
    public function __construct(
        protected OrganizationStructureService $structureService
    ) {}

    /**
     * Display a listing of services.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        Gate::authorize('viewAny', Service::class);

        $query = Service::query()
            ->where('organization_id', $user->organization_id)
            ->with(['direction', 'users'])
            ->withCount(['users', 'documents'])
            ->orderBy('name');

        if ($request->filled('direction_id')) {
            $query->where('direction_id', $request->input('direction_id'));
        }

        $services = $query->get()->map(fn (Service $s) => [
            'id' => $s->id,
            'name' => $s->name,
            'code' => $s->code,
            'description' => $s->description,
            'direction_id' => $s->direction_id,
            'direction_name' => $s->direction?->name,
            'folder_id' => $s->folder_id,
            'is_active' => (bool) $s->is_active,
            'users_count' => $s->users_count,
            'documents_count' => $s->documents_count,
            'users' => $s->users->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'job_title' => $u->job_title,
                'is_primary' => $u->primary_service_id === $s->id,
            ]),
            'created_at' => $s->created_at?->toISOString(),
        ]);

        $directions = Direction::query()
            ->where('organization_id', $user->organization_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $availableUsers = User::query()
            ->where('organization_id', $user->organization_id)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'email', 'primary_service_id'])
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'primary_service_id' => $u->primary_service_id,
            ]);

        $can = [
            'create' => $user->can('create', Service::class),
            'update' => $user->can('create', Service::class),
            'delete' => $user->can('create', Service::class),
        ];

        return Inertia::render('Services/Index', [
            'services' => $services,
            'directions' => $directions,
            'availableUsers' => $availableUsers,
            'selectedDirectionId' => $request->input('direction_id') ? (int) $request->input('direction_id') : null,
            'can' => $can,
        ]);
    }

    /**
     * Store a newly created service.
     */
    public function store(StoreServiceRequest $request): RedirectResponse
    {
        Gate::authorize('create', Service::class);

        $service = $this->structureService->createService(
            $request->validated(),
            $request->user()
        );

        return back()->with('success', "Service '{$service->name}' créé avec succès.");
    }

    /**
     * Update the specified service.
     */
    public function update(UpdateServiceRequest $request, Service $service): RedirectResponse
    {
        Gate::authorize('update', $service);

        $this->structureService->updateService(
            $service,
            $request->validated(),
            $request->user()
        );

        return back()->with('success', "Service '{$service->name}' mis à jour avec succès.");
    }

    /**
     * Remove the specified service.
     */
    public function destroy(Request $request, Service $service): RedirectResponse
    {
        Gate::authorize('delete', $service);

        $name = $service->name;
        $this->structureService->deleteService($service, $request->user());

        return back()->with('success', "Service '{$name}' supprimé avec succès.");
    }

    /**
     * Assign a user to the service.
     */
    public function assignUser(Request $request, Service $service): RedirectResponse
    {
        Gate::authorize('update', $service);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $targetUser = User::where('organization_id', $request->user()->organization_id)
            ->findOrFail($validated['user_id']);

        $this->structureService->assignUserToService(
            $targetUser,
            $service,
            (bool) ($validated['is_primary'] ?? false),
            $request->user()
        );

        return back()->with('success', "Utilisateur '{$targetUser->name}' assigné au service '{$service->name}'.");
    }

    /**
     * Remove a user from the service.
     */
    public function removeUser(Request $request, Service $service, User $user): RedirectResponse
    {
        Gate::authorize('update', $service);

        if ($user->organization_id !== $request->user()->organization_id) {
            abort(403);
        }

        $this->structureService->removeUserFromService($user, $service, $request->user());

        return back()->with('success', "Utilisateur '{$user->name}' retiré du service '{$service->name}'.");
    }
}
