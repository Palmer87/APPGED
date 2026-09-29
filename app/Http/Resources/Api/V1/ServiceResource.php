<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Service
 */
class ServiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'direction_id' => $this->direction_id,
            'direction_name' => $this->direction?->name,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'folder_id' => $this->folder_id,
            'is_active' => (bool) $this->is_active,
            'users_count' => $this->users_count ?? $this->whenLoaded('users', fn () => $this->users->count()),
            'direction' => new DirectionResource($this->whenLoaded('direction')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
