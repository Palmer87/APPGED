<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isFavorite = false;
        if ($user) {
            $isFavorite = $this->relationLoaded('favorites')
                ? $this->favorites->contains('user_id', $user->id)
                : $this->favorites()->where('user_id', $user->id)->exists();
        }

        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'folder_id' => $this->folder_id,
            'name' => $this->name,
            'description' => $this->description,
            'file_name' => $this->file_name,
            'mime_type' => $this->mime_type,
            'extension' => $this->extension,
            'size' => $this->size,
            'status' => $this->status,
            'is_favorite' => $isFavorite,
            'uploaded_by' => $this->uploaded_by,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'folder' => new FolderResource($this->whenLoaded('folder')),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'versions_count' => $this->whenCounted('versions'),
            'current_version' => new DocumentVersionResource($this->whenLoaded('currentVersion')),
            'is_deleted' => $this->trashed(),
            'deleted_at' => $this->deleted_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
