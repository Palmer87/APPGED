<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentShareResource extends JsonResource
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
            'document_id' => $this->document_id,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'group_id' => $this->group_id,
            'group' => $this->whenLoaded('group', fn () => [
                'id' => $this->group->id,
                'name' => $this->group->name,
                'description' => $this->group->description,
            ]),
            'shared_by' => $this->shared_by,
            'shared_by_user' => new UserResource($this->whenLoaded('sharedBy')),
            'permission' => $this->permission,
            'expires_at' => $this->expires_at?->toISOString(),
            'is_expired' => $this->expires_at ? $this->expires_at->isPast() : false,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
