<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowInstanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'workflow_id' => $this->workflow_id,
            'workflow' => new WorkflowResource($this->whenLoaded('workflow')),
            'document_id' => $this->document_id,
            'document' => new DocumentResource($this->whenLoaded('document')),
            'document_version_id' => $this->document_version_id,
            'current_step_id' => $this->current_step_id,
            'current_step' => $this->whenLoaded('currentStep', fn () => $this->currentStep ? [
                'id' => $this->currentStep->id,
                'name' => $this->currentStep->name,
                'position' => $this->currentStep->position,
                'approver_type' => $this->currentStep->approver_type instanceof \BackedEnum ? $this->currentStep->approver_type->value : $this->currentStep->approver_type,
                'approver_id' => $this->currentStep->approver_id,
            ] : null),
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'started_by' => $this->started_by,
            'started_by_user' => new UserResource($this->whenLoaded('startedBy')),
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'actions' => $this->whenLoaded('actions', fn () => $this->actions->map(fn ($action) => [
                'id' => $action->id,
                'workflow_step_id' => $action->workflow_step_id,
                'user_id' => $action->user_id,
                'user' => $action->relationLoaded('user') && $action->user ? [
                    'id' => $action->user->id,
                    'name' => trim(($action->user->first_name ?? '').' '.($action->user->last_name ?? '')) ?: $action->user->email,
                ] : null,
                'action' => $action->action instanceof \BackedEnum ? $action->action->value : $action->action,
                'comment' => $action->comment,
                'created_at' => $action->created_at?->toISOString(),
            ])),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
