<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionUsageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'plan_name' => $this->resource['plan_name'] ?? null,
            'plan_slug' => $this->resource['plan_slug'] ?? null,
            'is_any_exceeded' => (bool) ($this->resource['is_any_exceeded'] ?? false),
            'warnings' => $this->resource['warnings'] ?? [],
            'metrics' => $this->resource['metrics'] ?? [],
        ];
    }
}
