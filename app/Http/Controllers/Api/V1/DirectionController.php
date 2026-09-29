<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDirectionRequest;
use App\Http\Requests\UpdateDirectionRequest;
use App\Http\Resources\Api\V1\DirectionResource;
use App\Http\Resources\Api\V1\ServiceResource;
use App\Models\Direction;
use App\Services\OrganizationStructureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class DirectionController extends Controller
{
    public function __construct(
        protected OrganizationStructureService $structureService
    ) {}

    /**
     * List all directions for current organization.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Direction::class);

        $directions = Direction::query()
            ->where('organization_id', $request->user()->organization_id)
            ->withCount('services')
            ->orderBy('name')
            ->get();

        return DirectionResource::collection($directions);
    }

    /**
     * Show a single direction.
     */
    public function show(Request $request, Direction $direction): DirectionResource
    {
        Gate::authorize('view', $direction);

        $direction->load('services');

        return new DirectionResource($direction);
    }

    /**
     * Get all services for a direction.
     */
    public function services(Request $request, Direction $direction): AnonymousResourceCollection
    {
        Gate::authorize('view', $direction);

        $services = $direction->services()
            ->withCount('users')
            ->orderBy('name')
            ->get();

        return ServiceResource::collection($services);
    }

    /**
     * Create a new direction.
     */
    public function store(StoreDirectionRequest $request): JsonResponse
    {
        Gate::authorize('create', Direction::class);

        $direction = $this->structureService->createDirection(
            $request->validated(),
            $request->user()
        );

        return (new DirectionResource($direction))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update an existing direction.
     */
    public function update(UpdateDirectionRequest $request, Direction $direction): DirectionResource
    {
        Gate::authorize('update', $direction);

        $direction = $this->structureService->updateDirection(
            $direction,
            $request->validated(),
            $request->user()
        );

        return new DirectionResource($direction);
    }

    /**
     * Delete a direction.
     */
    public function destroy(Request $request, Direction $direction): JsonResponse
    {
        Gate::authorize('delete', $direction);

        $this->structureService->deleteDirection($direction, $request->user());

        return response()->json(['message' => 'Direction supprimée avec succès.']);
    }
}
