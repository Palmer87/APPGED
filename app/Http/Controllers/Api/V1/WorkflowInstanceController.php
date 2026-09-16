<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\WorkflowInstanceResource;
use App\Models\WorkflowInstance;
use App\Services\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WorkflowInstanceController extends Controller
{
    public function __construct(
        protected WorkflowService $workflowService
    ) {}

    /**
     * List workflow instances.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = max(1, min((int) $request->input('per_page', 25), 100));

        $query = WorkflowInstance::where('organization_id', $user->organization_id)
            ->with(['workflow', 'document', 'currentStep', 'startedBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('document_id')) {
            $query->where('document_id', (int) $request->input('document_id'));
        }

        $instances = $query->latest('started_at')->paginate($perPage);

        return response()->json([
            'data' => WorkflowInstanceResource::collection($instances),
            'meta' => [
                'current_page' => $instances->currentPage(),
                'per_page' => $instances->perPage(),
                'total' => $instances->total(),
                'last_page' => $instances->lastPage(),
            ],
        ]);
    }

    /**
     * Show workflow instance details.
     */
    public function show(WorkflowInstance $instance): JsonResponse
    {
        Gate::authorize('view', $instance);

        $instance->load(['workflow', 'document', 'currentStep', 'startedBy', 'actions.user']);

        return response()->json([
            'data' => new WorkflowInstanceResource($instance),
        ]);
    }

    /**
     * Approve the current step in the workflow instance.
     */
    public function approve(Request $request, WorkflowInstance $instance): JsonResponse
    {
        $user = $request->user();
        Gate::authorize('approve', $instance);

        $validated = $request->validate([
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->workflowService->approve($user, $instance, $validated['comment'] ?? null);

        return response()->json([
            'data' => new WorkflowInstanceResource($instance->fresh(['workflow', 'document', 'currentStep', 'startedBy', 'actions.user'])),
            'message' => 'Step approved successfully.',
        ]);
    }

    /**
     * Reject the current step in the workflow instance.
     */
    public function reject(Request $request, WorkflowInstance $instance): JsonResponse
    {
        $user = $request->user();
        Gate::authorize('reject', $instance);

        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:1000'],
        ]);

        $this->workflowService->reject($user, $instance, $validated['comment']);

        return response()->json([
            'data' => new WorkflowInstanceResource($instance->fresh(['workflow', 'document', 'currentStep', 'startedBy', 'actions.user'])),
            'message' => 'Workflow step rejected.',
        ]);
    }

    /**
     * Cancel a running workflow instance.
     */
    public function cancel(Request $request, WorkflowInstance $instance): JsonResponse
    {
        $user = $request->user();
        Gate::authorize('cancel', $instance);

        $validated = $request->validate([
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->workflowService->cancel($user, $instance, $validated['comment'] ?? null);

        return response()->json([
            'data' => new WorkflowInstanceResource($instance->fresh(['workflow', 'document', 'currentStep', 'startedBy', 'actions.user'])),
            'message' => 'Workflow cancelled successfully.',
        ]);
    }
}
