<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Plan
 */
class PlanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'monthly_price' => $this->monthly_price,
            'annual_price' => $this->annual_price,
            'currency' => $this->currency,
            'annual_savings' => $this->getAnnualSavings(),
            'limits' => [
                'max_users' => $this->max_users,
                'max_storage_bytes' => $this->max_storage_bytes,
                'max_directions' => $this->max_directions,
                'max_document_types' => $this->max_document_types,
                'max_ocr_pages_month' => $this->max_ocr_pages_month,
            ],
            'features' => [
                'has_api' => (bool) $this->has_api,
                'has_workflows' => (bool) $this->has_workflows,
                'has_advanced_audit' => (bool) $this->has_advanced_audit,
                'has_priority_support' => (bool) $this->has_priority_support,
                'has_dedicated_support' => (bool) $this->has_dedicated_support,
                'has_sla' => (bool) $this->has_sla,
                'has_custom_migration' => (bool) $this->has_custom_migration,
                'has_custom_integrations' => (bool) $this->has_custom_integrations,
            ],
            'is_custom' => (bool) $this->is_custom,
            'is_active' => (bool) $this->is_active,
            'sort_order' => $this->sort_order,
        ];
    }
}
