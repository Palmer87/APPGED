<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'name' => trim(($this->first_name ?? '').' '.($this->last_name ?? '')) ?: $this->email,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'job_title' => $this->job_title,
            'status' => $this->status,
            'last_login_at' => $this->last_login_at?->toISOString(),
            'organization_id' => $this->organization_id,
            'organization' => new OrganizationResource($this->whenLoaded('organization', fn () => $this->organization, $this->organization)),
            'roles' => $this->relationLoaded('roles') ? $this->roles->pluck('name') : $this->getRoleNames(),
            'permissions' => $this->getAllPermissions()->pluck('name')->values(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
