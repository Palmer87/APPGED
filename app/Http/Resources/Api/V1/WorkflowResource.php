<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'created_by' => $this->created_by,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'steps' => $this->whenLoaded('steps', fn () => $this->steps->map(fn ($step) => [
                'id' => $step->id,
                'name' => $step->name,
                'description' => $step->description,
                'position' => $step->position,
                'approver_type' => $step->approver_type instanceof \BackedEnum ? $step->approver_type->value : $step->approver_type,
                'approver_id' => $step->approver_id,
                'is_final_step' => (bool) $step->is_final_step,
                'timeout_hours' => $step->timeout_hours,
            ])),
            'steps_count' => $this->whenCounted('steps'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
