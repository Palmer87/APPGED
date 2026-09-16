<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReorderWorkflowStepsRequest;
use App\Http\Requests\StoreWorkflowStepRequest;
use App\Http\Requests\UpdateWorkflowStepRequest;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Services\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkflowStepController extends Controller
{
    public function __construct(
        protected WorkflowService $workflowService
    ) {}

    /**
     * Add a step to a workflow.
     */
    public function store(StoreWorkflowStepRequest $request, Workflow $workflow): JsonResponse
    {
        $step = $this->workflowService->addStep($request->user(), $workflow, $request->validated());

        return response()->json($step, 201);
    }

    /**
     * Update an existing step.
     */
    public function update(UpdateWorkflowStepRequest $request, WorkflowStep $step): JsonResponse
    {
        $updated = $this->workflowService->updateStep($request->user(), $step, $request->validated());

        return response()->json($updated);
    }

    /**
     * Remove a step from a workflow.
     */
    public function destroy(Request $request, WorkflowStep $step): JsonResponse
    {
        $this->workflowService->removeStep($request->user(), $step);

        return response()->json(['message' => 'Step removed successfully.']);
    }

    /**
     * Reorder steps in a workflow.
     */
    public function reorder(ReorderWorkflowStepsRequest $request, Workflow $workflow): JsonResponse
    {
        $this->workflowService->reorderSteps($request->user(), $workflow, $request->validated('positions'));

        return response()->json(['message' => 'Steps reordered successfully.']);
    }
}
