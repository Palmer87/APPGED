<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentCommentResource extends JsonResource
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
            'document_id' => $this->document_id,
            'document_version_id' => $this->document_version_id,
            'parent_id' => $this->parent_id,
            'content' => $this->content,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'replies' => DocumentCommentResource::collection($this->whenLoaded('replies')),
            'replies_count' => $this->whenCounted('replies'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
