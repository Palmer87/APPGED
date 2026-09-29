<?php

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;

class SubscriptionPolicy
{
    /**
     * Determine whether the user can view any subscription (dashboard / overview).
     */
    public function viewAny(User $user): bool
    {
        return $user->organization_id !== null;
    }

    /**
     * Determine whether the user can view the specific subscription.
     */
    public function view(User $user, Subscription $subscription): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $subscription->organization_id;
    }

    /**
     * Determine whether the user can manage billing (change plan, cancel, resume, view invoices).
     */
    public function manage(User $user, ?Subscription $subscription = null): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ($subscription && $user->organization_id !== $subscription->organization_id) {
            return false;
        }

        return $user->hasRole('admin') || $user->can('billing.manage');
    }

    public function update(User $user, Subscription $subscription): bool
    {
        return $this->manage($user, $subscription);
    }

    public function cancel(User $user, Subscription $subscription): bool
    {
        return $this->manage($user, $subscription);
    }

    public function resume(User $user, Subscription $subscription): bool
    {
        return $this->manage($user, $subscription);
    }
}
