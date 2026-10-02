<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\PlatformUser;
use App\Models\Subscription;
use App\Services\Billing\Contracts\BillingProviderInterface;
use App\Services\Billing\Providers\ManualBillingProvider;

class PlatformOrganizationService
{
    public function __construct(
        protected PlatformAuditService $auditService,
        protected ?BillingProviderInterface $billingProvider = null
    ) {
        $this->billingProvider = $this->billingProvider ?? new ManualBillingProvider;
    }

    /**
     * Suspend an organization without deleting any data.
     */
    public function suspendOrganization(Organization $organization, string $reason, ?PlatformUser $actor = null): Organization
    {
        $oldValues = ['status' => $organization->status];

        $organization->update(['status' => 'suspended']);

        // Also mark active subscription as suspended
        if ($organization->currentSubscription) {
            $organization->currentSubscription->update(['status' => 'suspended']);
        }

        $this->auditService->log(
            action: 'platform.organization.suspended',
            description: "Organisation '{$organization->name}' suspendue. Raison : {$reason}",
            target: $organization,
            oldValues: $oldValues,
            newValues: ['status' => 'suspended', 'reason' => $reason],
            organizationId: $organization->id,
            actor: $actor
        );

        return $organization->fresh(['currentSubscription.plan']);
    }

    /**
     * Reactivate a suspended organization.
     */
    public function reactivateOrganization(Organization $organization, ?PlatformUser $actor = null): Organization
    {
        $oldValues = ['status' => $organization->status];

        $organization->update(['status' => 'active']);

        // Reactivate subscription if it was suspended
        if ($organization->currentSubscription && $organization->currentSubscription->status === 'suspended') {
            $sub = $organization->currentSubscription;
            $newStatus = ($sub->trial_ends_at && $sub->trial_ends_at->isFuture()) ? 'trialing' : 'active';
            $sub->update(['status' => $newStatus]);
        }

        $this->auditService->log(
            action: 'platform.organization.reactivated',
            description: "Organisation '{$organization->name}' réactivée.",
            target: $organization,
            oldValues: $oldValues,
            newValues: ['status' => 'active'],
            organizationId: $organization->id,
            actor: $actor
        );

        return $organization->fresh(['currentSubscription.plan']);
    }

    /**
     * Extend a trial period for an organization.
     */
    public function extendTrial(Organization $organization, int $days, ?PlatformUser $actor = null): Subscription
    {
        $subscription = $organization->currentSubscription;
        if (! $subscription) {
            throw new \RuntimeException('Aucun abonnement trouvé pour cette organisation.');
        }

        $oldDate = $subscription->trial_ends_at;
        $baseDate = ($subscription->trial_ends_at && $subscription->trial_ends_at->isFuture())
            ? $subscription->trial_ends_at
            : now();

        $newTrialEnd = (clone $baseDate)->addDays($days);

        $subscription->update([
            'status' => 'trialing',
            'trial_ends_at' => $newTrialEnd,
            'current_period_ends_at' => $newTrialEnd,
        ]);

        $this->auditService->log(
            action: 'platform.trial.extended',
            description: "Période d'essai prolongée de {$days} jours pour '{$organization->name}'.",
            target: $subscription,
            oldValues: ['trial_ends_at' => $oldDate?->toIso8601String()],
            newValues: ['trial_ends_at' => $newTrialEnd->toIso8601String(), 'additional_days' => $days],
            organizationId: $organization->id,
            actor: $actor
        );

        return $subscription->fresh(['plan']);
    }

    /**
     * End a trial period immediately.
     */
    public function endTrial(Organization $organization, ?PlatformUser $actor = null): Subscription
    {
        $subscription = $organization->currentSubscription;
        if (! $subscription) {
            throw new \RuntimeException('Aucun abonnement trouvé pour cette organisation.');
        }

        $oldDate = $subscription->trial_ends_at;
        $now = now();

        $subscription->update([
            'status' => 'expired',
            'trial_ends_at' => $now,
            'current_period_ends_at' => $now,
        ]);

        $this->auditService->log(
            action: 'platform.trial.ended',
            description: "Période d'essai terminée immédiatement pour '{$organization->name}'.",
            target: $subscription,
            oldValues: ['trial_ends_at' => $oldDate?->toIso8601String(), 'status' => $subscription->status],
            newValues: ['trial_ends_at' => $now->toIso8601String(), 'status' => 'expired'],
            organizationId: $organization->id,
            actor: $actor
        );

        return $subscription->fresh(['plan']);
    }

    /**
     * Change plan and billing cycle for an organization.
     */
    public function changePlan(Organization $organization, Plan $newPlan, string $cycle = 'monthly', ?PlatformUser $actor = null): Subscription
    {
        $subscription = $organization->currentSubscription;
        $oldPlan = $subscription?->plan;

        if ($subscription) {
            $updated = $this->billingProvider->changeSubscription($subscription, $newPlan, $cycle);
        } else {
            $now = now();
            $endsAt = $cycle === 'annual' ? (clone $now)->addYear() : (clone $now)->addMonth();
            $updated = Subscription::create([
                'organization_id' => $organization->id,
                'plan_id' => $newPlan->id,
                'billing_cycle' => in_array($cycle, ['monthly', 'annual'], true) ? $cycle : 'monthly',
                'status' => 'active',
                'starts_at' => $now,
                'current_period_starts_at' => $now,
                'current_period_ends_at' => $endsAt,
                'auto_renew' => true,
                'provider' => 'manual',
            ]);
        }

        $this->auditService->log(
            action: 'platform.subscription.changed',
            description: "Plan changé vers '{$newPlan->name}' ({$cycle}) pour '{$organization->name}'.",
            target: $updated,
            oldValues: ['plan_id' => $oldPlan?->id, 'plan_name' => $oldPlan?->name],
            newValues: ['plan_id' => $newPlan->id, 'plan_name' => $newPlan->name, 'cycle' => $cycle],
            organizationId: $organization->id,
            actor: $actor
        );

        return $updated->fresh(['plan', 'organization']);
    }
}
