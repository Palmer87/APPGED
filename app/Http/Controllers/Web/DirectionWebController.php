<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDirectionRequest;
use App\Http\Requests\UpdateDirectionRequest;
use App\Models\Direction;
use App\Services\BillingService;
use App\Services\OrganizationStructureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DirectionWebController extends Controller
{
    public function __construct(
        protected OrganizationStructureService $structureService,
        protected ?BillingService $billingService = null
    ) {
        $this->billingService = $this->billingService ?? app(BillingService::class);
    }

    /**
     * Display a listing of directions.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        Gate::authorize('viewAny', Direction::class);

        $directions = Direction::query()
            ->where('organization_id', $user->organization_id)
            ->with(['services' => function ($q) {
                $q->withCount('users');
            }])
            ->withCount(['services', 'documents'])
            ->orderBy('name')
            ->get()
            ->map(fn (Direction $d) => [
                'id' => $d->id,
                'name' => $d->name,
                'code' => $d->code,
                'description' => $d->description,
                'folder_id' => $d->folder_id,
                'is_active' => (bool) $d->is_active,
                'services_count' => $d->services_count,
                'documents_count' => $d->documents_count,
                'services' => $d->services->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'users_count' => $s->users_count,
                ]),
                'created_at' => $d->created_at?->toISOString(),
            ]);

        $dummyDirection = new Direction(['organization_id' => $user->organization_id]);
        $can = [
            'create' => $user->can('create', Direction::class),
            'update' => $user->can('update', $dummyDirection),
            'delete' => $user->can('delete', $dummyDirection),
        ];

        return Inertia::render('Directions/Index', [
            'directions' => $directions,
            'can' => $can,
        ]);
    }

    /**
     * Store a newly created direction.
     */
    public function store(StoreDirectionRequest $request): RedirectResponse
    {
        Gate::authorize('create', Direction::class);

        $user = $request->user();
        if ($this->billingService) {
            $this->billingService->assertCanAddDirection(1, $user->organization_id);
        }

        $direction = $this->structureService->createDirection(
            $request->validated(),
            $user
        );

        return back()->with('success', "Direction '{$direction->name}' créée avec succès.");
    }

    /**
     * Update the specified direction.
     */
    public function update(UpdateDirectionRequest $request, Direction $direction): RedirectResponse
    {
        Gate::authorize('update', $direction);

        $this->structureService->updateDirection(
            $direction,
            $request->validated(),
            $request->user()
        );

        return back()->with('success', "Direction '{$direction->name}' mise à jour avec succès.");
    }

    /**
     * Remove the specified direction.
     */
    public function destroy(Request $request, Direction $direction): RedirectResponse
    {
        Gate::authorize('delete', $direction);

        $name = $direction->name;
        $this->structureService->deleteDirection($direction, $request->user());

        return back()->with('success', "Direction '{$name}' supprimée avec succès.");
    }
}
