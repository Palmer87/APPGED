<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Subscription
 */
class SubscriptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'plan' => new PlanResource($this->whenLoaded('plan', $this->plan)),
            'billing_cycle' => $this->billing_cycle,
            'status' => $this->status,
            'is_active' => $this->isActive(),
            'is_trial' => $this->isTrial(),
            'is_expired' => $this->isExpired(),
            'trial_days_remaining' => $this->trialDaysRemaining(),
            'starts_at' => $this->starts_at?->toISOString(),
            'trial_starts_at' => $this->trial_starts_at?->toISOString(),
            'trial_ends_at' => $this->trial_ends_at?->toISOString(),
            'current_period_starts_at' => $this->current_period_starts_at?->toISOString(),
            'current_period_ends_at' => $this->current_period_ends_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'ended_at' => $this->ended_at?->toISOString(),
            'auto_renew' => (bool) $this->auto_renew,
            'provider' => $this->provider,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
