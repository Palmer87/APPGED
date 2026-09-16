<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkflowRequest;
use App\Http\Requests\UpdateWorkflowRequest;
use App\Models\Workflow;
use App\Services\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WorkflowController extends Controller
{
    public function __construct(
        protected WorkflowService $workflowService
    ) {}

    /**
     * List workflows for the authenticated user's organization.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::forUser($request->user())->authorize('viewAny', Workflow::class);

        $perPage = max(1, min((int) $request->input('per_page', 25), 100));

        $workflows = Workflow::where('organization_id', $request->user()->organization_id)
            ->with(['steps.approverUser', 'steps.approverGroup', 'creator'])
            ->latest('id')
            ->paginate($perPage);

        return response()->json($workflows);
    }

    /**
     * Store a newly created workflow.
     */
    public function store(StoreWorkflowRequest $request): JsonResponse
    {
        $workflow = $this->workflowService->createWorkflow($request->user(), $request->validated());

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
    public function update(UpdateWorkflowRequest $request, Workflow $workflow): JsonResponse
    {
        $updated = $this->workflowService->updateWorkflow($request->user(), $workflow, $request->validated());

        return response()->json($updated);
    }

    /**
     * Remove the specified workflow.
     */
    public function destroy(Request $request, Workflow $workflow): JsonResponse
    {
        $this->workflowService->deleteWorkflow($request->user(), $workflow);

        return response()->json(['message' => 'Workflow deleted successfully.']);
    }
}
