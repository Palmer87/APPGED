<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FolderResource extends JsonResource
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
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'description' => $this->description,
            'path' => $this->path,
            'created_by' => $this->created_by,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'parent' => new FolderResource($this->whenLoaded('parent')),
            'children' => FolderResource::collection($this->whenLoaded('children')),
            'children_count' => $this->whenCounted('children'),
            'is_archived' => (bool) $this->is_archived,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
