<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReorderWorkflowStepsRequest;
use App\Http\Requests\StoreWorkflowStepRequest;
use App\Http\Requests\UpdateWorkflowStepRequest;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Services\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WorkflowStepController extends Controller
{
    public function __construct(
        protected WorkflowService $workflowService
    ) {}

    /**
     * Add a step to a workflow.
     */
    public function store(StoreWorkflowStepRequest $request, Workflow $workflow): JsonResponse|RedirectResponse
    {
        $step = $this->workflowService->addStep($request->user(), $workflow, $request->validated());

        if ($request->header('X-Inertia') || ! $request->wantsJson()) {
            return back()->with('success', 'Étape ajoutée avec succès.');
        }

        return response()->json($step, 201);
    }

    /**
     * Update an existing step.
     */
    public function update(UpdateWorkflowStepRequest $request, WorkflowStep $step): JsonResponse|RedirectResponse
    {
        $updated = $this->workflowService->updateStep($request->user(), $step, $request->validated());

        if ($request->header('X-Inertia') || ! $request->wantsJson()) {
            return back()->with('success', 'Étape mise à jour avec succès.');
        }

        return response()->json($updated);
    }

    /**
     * Remove a step from a workflow.
     */
    public function destroy(Request $request, WorkflowStep $step): JsonResponse|RedirectResponse
    {
        $this->workflowService->removeStep($request->user(), $step);

        if ($request->header('X-Inertia') || ! $request->wantsJson()) {
            return back()->with('success', 'Étape supprimée avec succès.');
        }

        return response()->json(['message' => 'Step removed successfully.']);
    }

    /**
     * Reorder steps in a workflow.
     */
    public function reorder(ReorderWorkflowStepsRequest $request, Workflow $workflow): JsonResponse|RedirectResponse
    {
        $this->workflowService->reorderSteps($request->user(), $workflow, $request->validated('positions'));

        if ($request->header('X-Inertia') || ! $request->wantsJson()) {
            return back()->with('success', 'Étapes réorganisées avec succès.');
        }

        return response()->json(['message' => 'Steps reordered successfully.']);
    }
}
