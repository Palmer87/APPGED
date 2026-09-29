<?php

namespace App\Http\Resources\Api\V1;

use App\Models\AccessScope;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AccessScope
 */
class AccessScopeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'user_id' => $this->user_id,
            'scope_type' => $this->scope_type->value,
            'scope_type_label' => $this->scope_type->label(),
            'direction_id' => $this->direction_id,
            'service_id' => $this->service_id,
            'folder_id' => $this->folder_id,
            'document_id' => $this->document_id,
            'target_name' => $this->target_name,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
