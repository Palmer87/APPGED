<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\WorkflowInstanceResource;
use App\Http\Resources\Api\V1\WorkflowResource;
use App\Models\Document;
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
     * List all workflows for the organization.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->can('workflows.view')) {
            abort(403, 'Unauthorized to view workflows.');
        }

        $workflows = Workflow::where('organization_id', $user->organization_id)
            ->with(['creator', 'steps'])
            ->withCount('steps')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => WorkflowResource::collection($workflows),
        ]);
    }

    /**
     * Create a new workflow definition.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        Gate::authorize('create', Workflow::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'steps' => ['nullable', 'array'],
            'steps.*.name' => ['required', 'string', 'max:255'],
            'steps.*.approver_type' => ['required', 'string', 'in:user,group'],
            'steps.*.approver_id' => ['nullable', 'integer'],
            'steps.*.approver_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'steps.*.approver_group_id' => ['nullable', 'integer', 'exists:groups,id'],
            'steps.*.timeout_hours' => ['nullable', 'integer'],
        ]);

        $workflow = $this->workflowService->createWorkflow($user, $validated);

        if (! empty($validated['steps'])) {
            foreach ($validated['steps'] as $position => $stepData) {
                $stepData['position'] = $position + 1;
                if (! empty($stepData['approver_id'])) {
                    if ($stepData['approver_type'] === 'user') {
                        $stepData['approver_user_id'] = $stepData['approver_id'];
                    } elseif ($stepData['approver_type'] === 'group') {
                        $stepData['approver_group_id'] = $stepData['approver_id'];
                    }
                    unset($stepData['approver_id']);
                }
                $this->workflowService->addStep($user, $workflow, $stepData);
            }
        }

        return response()->json([
            'data' => new WorkflowResource($workflow->fresh(['creator', 'steps'])),
            'message' => 'Workflow created successfully.',
        ], 201);
    }

    /**
     * Show workflow details and steps.
     */
    public function show(Workflow $workflow): JsonResponse
    {
        Gate::authorize('view', $workflow);

        $workflow->load(['creator', 'steps']);

        return response()->json([
            'data' => new WorkflowResource($workflow),
        ]);
    }

    /**
     * Start a workflow on a document.
     */
    public function start(Request $request, Document $document, Workflow $workflow): JsonResponse
    {
        $user = $request->user();
        Gate::authorize('view', $document);

        $instance = $this->workflowService->start($user, $document, $workflow);

        return response()->json([
            'data' => new WorkflowInstanceResource($instance->load(['workflow', 'document', 'currentStep', 'startedBy'])),
            'message' => 'Workflow started successfully.',
        ], 201);
    }
}
