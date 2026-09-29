<?php

namespace App\Models;

use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['name', 'slug', 'logo', 'email', 'phone', 'address', 'activity', 'country', 'city', 'storage_limit', 'status'])]
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    public static bool $createDefaultSubscriptionOnBoot = true;

    protected static function booted(): void
    {
        static::created(function (Organization $organization) {
            // Automatically initiate 14-day Essential trial if not already attached and boot creation is active
            if (static::$createDefaultSubscriptionOnBoot && ! $organization->subscriptions()->exists()) {
                $defaultPlan = Plan::where('slug', 'essential')->first();
                if ($defaultPlan) {
                    $now = now();
                    Subscription::create([
                        'organization_id' => $organization->id,
                        'plan_id' => $defaultPlan->id,
                        'billing_cycle' => 'monthly',
                        'status' => 'trialing',
                        'starts_at' => $now,
                        'trial_starts_at' => $now,
                        'trial_ends_at' => (clone $now)->addDays(14),
                        'current_period_starts_at' => $now,
                        'current_period_ends_at' => (clone $now)->addDays(14),
                        'auto_renew' => true,
                        'provider' => 'manual',
                    ]);
                }
            }
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function currentSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
