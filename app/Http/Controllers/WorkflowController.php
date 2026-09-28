<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkflowRequest;
use App\Http\Requests\UpdateWorkflowRequest;
use App\Models\Group;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowInstance;
use App\Services\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class WorkflowController extends Controller
{
    public function __construct(
        protected WorkflowService $workflowService
    ) {}

    /**
     * List workflows for the authenticated user's organization.
     */
    public function index(Request $request): mixed
    {
        Gate::forUser($request->user())->authorize('viewAny', Workflow::class);

        $perPage = max(1, min((int) $request->input('per_page', 25), 100));

        $workflows = Workflow::where('organization_id', $request->user()->organization_id)
            ->with(['steps.approverUser', 'steps.approverGroup', 'creator'])
            ->latest('id')
            ->paginate($perPage);

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json($workflows);
        }

        $instances = WorkflowInstance::where('organization_id', $request->user()->organization_id)
            ->with(['workflow', 'document', 'startedBy', 'currentStep.approverUser', 'currentStep.approverGroup'])
            ->latest('id')
            ->take(30)
            ->get();

        $instances->each(function ($instance) use ($request) {
            $instance->can_approve = $this->workflowService->canUserApproveStep($request->user(), $instance);
        });

        $availableUsers = User::where('organization_id', $request->user()->organization_id)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'email']);

        $availableGroups = Group::where('organization_id', $request->user()->organization_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $canCreate = Gate::forUser($request->user())->allows('create', Workflow::class);

        return Inertia::render('Workflows/Index', [
            'workflows' => $workflows,
            'instances' => $instances,
            'availableUsers' => $availableUsers,
            'availableGroups' => $availableGroups,
            'canCreate' => $canCreate,
        ]);
    }

    /**
     * Store a newly created workflow.
     */
    public function store(StoreWorkflowRequest $request): JsonResponse|RedirectResponse
    {
        $workflow = $this->workflowService->createWorkflow($request->user(), $request->validated());

        if ($request->header('X-Inertia') || ! $request->wantsJson()) {
            return redirect()->route('workflows.index')->with('success', "Modèle de workflow '{$workflow->name}' créé avec succès.");
        }

        return response()->json($workflow, 201);
    }

    /**
     * Display the specified workflow.
     */
    public function show(Request $request, Workflow $workflow): JsonResponse
    {
        Gate::forUser($request->user())->authorize('view', $workflow);

        $workflow->load(['steps.approverUser', 'steps.approverGroup', 'creator']);

        return response()->json($workflow);
    }

    /**
     * Update the specified workflow.
     */
    public function update(UpdateWorkflowRequest $request, Workflow $workflow): JsonResponse|RedirectResponse
    {
        $updated = $this->workflowService->updateWorkflow($request->user(), $workflow, $request->validated());

        if ($request->header('X-Inertia') || ! $request->wantsJson()) {
            return redirect()->route('workflows.index')->with('success', 'Workflow mis à jour avec succès.');
        }

        return response()->json($updated);
    }

    /**
     * Remove the specified workflow.
     */
    public function destroy(Request $request, Workflow $workflow): JsonResponse|RedirectResponse
    {
        $this->workflowService->deleteWorkflow($request->user(), $workflow);

        if ($request->header('X-Inertia') || ! $request->wantsJson()) {
            return redirect()->route('workflows.index')->with('success', 'Workflow supprimé avec succès.');
        }

        return response()->json(['message' => 'Workflow deleted successfully.']);
    }
}
