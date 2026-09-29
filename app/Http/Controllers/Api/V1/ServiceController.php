<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Http\Resources\Api\V1\ServiceResource;
use App\Models\Service;
use App\Services\OrganizationStructureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ServiceController extends Controller
{
    public function __construct(
        protected OrganizationStructureService $structureService
    ) {}

    /**
     * List all services for current organization.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Service::class);

        $query = Service::query()
            ->where('organization_id', $request->user()->organization_id)
            ->with(['direction'])
            ->withCount(['users', 'documents'])
            ->orderBy('name');

        if ($request->filled('direction_id')) {
            $query->where('direction_id', $request->input('direction_id'));
        }

        return ServiceResource::collection($query->get());
    }

    /**
     * Show a single service.
     */
    public function show(Request $request, Service $service): ServiceResource
    {
        Gate::authorize('view', $service);

        $service->load(['direction', 'users']);

        return new ServiceResource($service);
    }

    /**
     * Get users belonging to a service.
     */
    public function users(Request $request, Service $service): JsonResponse
    {
        Gate::authorize('view', $service);

        $users = $service->users()
            ->select(['users.id', 'users.first_name', 'users.last_name', 'users.email', 'users.job_title', 'users.primary_service_id'])
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'job_title' => $u->job_title,
                'is_primary' => $u->primary_service_id === $service->id,
            ]);

        return response()->json(['data' => $users]);
    }

    /**
     * Create a new service.
     */
    public function store(StoreServiceRequest $request): JsonResponse
    {
        Gate::authorize('create', Service::class);

        $service = $this->structureService->createService(
            $request->validated(),
            $request->user()
        );

        return (new ServiceResource($service))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update an existing service.
     */
    public function update(UpdateServiceRequest $request, Service $service): ServiceResource
    {
        Gate::authorize('update', $service);

        $service = $this->structureService->updateService(
            $service,
            $request->validated(),
            $request->user()
        );

        return new ServiceResource($service);
    }

    /**
     * Delete a service.
     */
    public function destroy(Request $request, Service $service): JsonResponse
    {
        Gate::authorize('delete', $service);

        $this->structureService->deleteService($service, $request->user());

        return response()->json(['message' => 'Service supprimé avec succès.']);
    }
}
