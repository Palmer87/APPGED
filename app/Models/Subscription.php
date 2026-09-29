<?php

namespace App\Models;

use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'organization_id',
    'plan_id',
    'billing_cycle',
    'status',
    'starts_at',
    'trial_starts_at',
    'trial_ends_at',
    'current_period_starts_at',
    'current_period_ends_at',
    'cancelled_at',
    'ended_at',
    'auto_renew',
    'provider',
    'provider_subscription_id',
    'metadata',
])]
class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'starts_at' => 'datetime',
        'trial_starts_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'current_period_starts_at' => 'datetime',
        'current_period_ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'ended_at' => 'datetime',
        'auto_renew' => 'boolean',
        'metadata' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Check if the subscription is actively usable (active or ongoing trial).
     */
    public function isActive(): bool
    {
        if ($this->status === 'active') {
            if ($this->current_period_ends_at && $this->current_period_ends_at->isPast()) {
                return false;
            }

            return true;
        }

        if ($this->status === 'trialing') {
            return ! $this->isTrialExpired();
        }

        return false;
    }

    /**
     * Check if the subscription is currently in trial mode.
     */
    public function isTrial(): bool
    {
        return $this->status === 'trialing';
    }

    /**
     * Check if trial has expired.
     */
    public function isTrialExpired(): bool
    {
        if (! $this->trial_ends_at) {
            return false;
        }

        return $this->trial_ends_at->isPast();
    }

    /**
     * Check if subscription is expired or trial expired.
     */
    public function isExpired(): bool
    {
        if ($this->status === 'expired') {
            return true;
        }

        if ($this->isTrial() && $this->isTrialExpired()) {
            return true;
        }

        if ($this->status === 'cancelled' && $this->current_period_ends_at && $this->current_period_ends_at->isPast()) {
            return true;
        }

        return false;
    }

    /**
     * Get remaining days in trial.
     */
    public function trialDaysRemaining(): int
    {
        if (! $this->trial_ends_at || $this->trial_ends_at->isPast()) {
            return 0;
        }

        return max(0, (int) ceil(now()->floatDiffInDays($this->trial_ends_at, false)));
    }

    /**
     * Check if the current plan has a given feature enabled.
     */
    public function hasFeature(string $feature): bool
    {
        if (! $this->plan) {
            return false;
        }

        $property = str_starts_with($feature, 'has_') ? $feature : 'has_'.$feature;

        return (bool) ($this->plan->{$property} ?? false);
    }

    /**
     * Scope for active subscriptions.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('status', 'active')
                ->orWhere(function (Builder $trialQ) {
                    $trialQ->where('status', 'trialing')
                        ->where(function ($sub) {
                            $sub->whereNull('trial_ends_at')
                                ->orWhere('trial_ends_at', '>', now());
                        });
                });
        });
    }

    /**
     * Scope for trialing subscriptions.
     */
    public function scopeTrialing(Builder $query): Builder
    {
        return $query->where('status', 'trialing');
    }
}
