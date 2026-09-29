<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Direction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Direction
 */
class DirectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'folder_id' => $this->folder_id,
            'is_active' => (bool) $this->is_active,
            'services_count' => $this->services_count ?? $this->whenLoaded('services', fn () => $this->services->count()),
            'services' => ServiceResource::collection($this->whenLoaded('services')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
