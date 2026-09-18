<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkflowActionRequest;
use App\Models\Document;
use App\Models\Workflow;
use App\Models\WorkflowInstance;
use App\Services\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WorkflowInstanceController extends Controller
{
    public function __construct(
        protected WorkflowService $workflowService
    ) {}

    /**
     * List workflow instances in the user's organization.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = max(1, min((int) $request->input('per_page', 25), 100));

        $query = WorkflowInstance::where('organization_id', $user->organization_id)
            ->with(['workflow', 'document', 'startedBy', 'currentStep'])
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('document_id')) {
            $query->where('document_id', $request->input('document_id'));
        }

        return response()->json($query->paginate($perPage));
    }

    /**
     * Show a workflow instance.
     */
    public function show(Request $request, WorkflowInstance $instance): JsonResponse
    {
        Gate::forUser($request->user())->authorize('view', $instance);

        $instance->load([
            'workflow',
            'document',
            'documentVersion',
            'startedBy',
            'currentStep.approverUser',
            'currentStep.approverGroup',
            'actions.user',
            'actions.step',
            'actions.version',
        ]);

        return response()->json($instance);
    }

    /**
     * Start a workflow for a document.
     */
    public function start(Request $request, Document $document, Workflow $workflow): JsonResponse
    {
        $instance = $this->workflowService->start($request->user(), $document, $workflow);

        return response()->json($instance, 201);
    }

    /**
     * Approve the current step of an instance.
     */
    public function approve(WorkflowActionRequest $request, WorkflowInstance $instance): JsonResponse|RedirectResponse
    {
        $action = $this->workflowService->approve($request->user(), $instance, $request->validated('comment'));

        if ($request->header('X-Inertia')) {
            return back()->with('success', 'Étape approuvée avec succès.');
        }

        return response()->json([
            'message' => 'Step approved successfully.',
            'action' => $action,
        ]);
    }

    /**
     * Reject a workflow instance.
     */
    public function reject(WorkflowActionRequest $request, WorkflowInstance $instance): JsonResponse|RedirectResponse
    {
        $action = $this->workflowService->reject($request->user(), $instance, $request->validated('comment'));

        if ($request->header('X-Inertia')) {
            return back()->with('success', 'Workflow rejeté.');
        }

        return response()->json([
            'message' => 'Workflow rejected.',
            'action' => $action,
        ]);
    }

    /**
     * Request correction on a workflow instance.
     */
    public function requestCorrection(WorkflowActionRequest $request, WorkflowInstance $instance): JsonResponse|RedirectResponse
    {
        $action = $this->workflowService->requestCorrection($request->user(), $instance, $request->validated('comment'));

        if ($request->header('X-Inertia')) {
            return back()->with('success', 'Demande de correction transmise.');
        }

        return response()->json([
            'message' => 'Correction requested.',
            'action' => $action,
        ]);
    }

    /**
     * Resubmit a workflow instance after corrections.
     */
    public function resubmit(WorkflowActionRequest $request, WorkflowInstance $instance): JsonResponse|RedirectResponse
    {
        $action = $this->workflowService->resubmit($request->user(), $instance, $request->validated('comment'));

        if ($request->header('X-Inertia')) {
            return back()->with('success', 'Workflow soumis à nouveau.');
        }

        return response()->json([
            'message' => 'Workflow resubmitted.',
            'action' => $action,
        ]);
    }

    /**
     * Cancel an active workflow instance.
     */
    public function cancel(WorkflowActionRequest $request, WorkflowInstance $instance): JsonResponse|RedirectResponse
    {
        $action = $this->workflowService->cancel($request->user(), $instance, $request->validated('comment'));

        if ($request->header('X-Inertia')) {
            return back()->with('success', 'Workflow annulé.');
        }

        return response()->json([
            'message' => 'Workflow cancelled.',
            'action' => $action,
        ]);
    }

    /**
     * Get the complete action history for a workflow instance.
     */
    public function history(Request $request, WorkflowInstance $instance): JsonResponse
    {
        Gate::forUser($request->user())->authorize('view', $instance);

        $history = $this->workflowService->getInstanceHistory($instance);

        return response()->json($history);
    }
}
