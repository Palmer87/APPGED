<?php

namespace App\Services;

use App\Exceptions\SubscriptionLimitExceededException;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\SubscriptionActivatedNotification;
use App\Notifications\SubscriptionCancelledNotification;
use App\Notifications\SubscriptionExpiredNotification;
use App\Notifications\SubscriptionPlanChangedNotification;
use App\Services\Billing\Contracts\BillingProviderInterface;
use App\Services\Billing\Providers\ManualBillingProvider;
use Illuminate\Support\Facades\Notification;

class BillingService
{
    public function __construct(
        protected SubscriptionUsageService $usageService,
        protected AuditService $auditService,
        protected ?BillingProviderInterface $provider = null
    ) {
        $this->provider = $this->provider ?? new ManualBillingProvider;
    }

    public function setProvider(BillingProviderInterface $provider): static
    {
        $this->provider = $provider;

        return $this;
    }

    public function getProvider(): BillingProviderInterface
    {
        return $this->provider ?? new ManualBillingProvider;
    }

    /**
     * Resolve the target organization instance safely.
     */
    public function resolveOrganization(Organization|int|null $organization = null): ?Organization
    {
        if ($organization instanceof Organization) {
            return $organization;
        }

        if (is_int($organization)) {
            return Organization::with(['currentSubscription.plan'])->find($organization);
        }

        $user = auth()->user();
        if ($user && $user->organization_id) {
            return Organization::with(['currentSubscription.plan'])->find($user->organization_id);
        }

        return null;
    }

    /**
     * Get the current active, trialing, or latest subscription for an organization.
     */
    public function getCurrentSubscription(Organization|int|null $organization = null): ?Subscription
    {
        $org = $this->resolveOrganization($organization);
        if (! $org) {
            return null;
        }

        // Return latest active or trialing subscription, or fallback to latest subscription
        $subscription = Subscription::with('plan')
            ->where('organization_id', $org->id)
            ->latest('id')
            ->first();

        // If no subscription exists, auto-initialize a 14-day Essential trial
        if (! $subscription) {
            $essential = Plan::where('slug', 'essential')->first();
            if ($essential) {
                $subscription = $this->startTrial($org, $essential, 14);
            }
        }

        return $subscription;
    }

    /**
     * Get the current plan for an organization.
     */
    public function getCurrentPlan(Organization|int|null $organization = null): ?Plan
    {
        $subscription = $this->getCurrentSubscription($organization);

        return $subscription?->plan;
    }

    /**
     * Check if organization subscription is in trial.
     */
    public function isTrial(Organization|int|null $organization = null): bool
    {
        $subscription = $this->getCurrentSubscription($organization);

        return $subscription ? $subscription->isTrial() : false;
    }

    /**
     * Check if organization subscription is active.
     */
    public function isActive(Organization|int|null $organization = null): bool
    {
        $subscription = $this->getCurrentSubscription($organization);

        return $subscription ? $subscription->isActive() : false;
    }

    /**
     * Check if organization subscription or trial is expired.
     */
    public function isExpired(Organization|int|null $organization = null): bool
    {
        $subscription = $this->getCurrentSubscription($organization);

        return $subscription ? $subscription->isExpired() : true;
    }

    /**
     * Check if organization's plan grants access to a specific feature.
     */
    public function hasFeature(string $feature, Organization|int|null $organization = null): bool
    {
        $plan = $this->getCurrentPlan($organization);
        if (! $plan) {
            return false;
        }

        $property = str_starts_with($feature, 'has_') ? $feature : 'has_'.$feature;

        return (bool) ($plan->{$property} ?? false);
    }

    /**
     * Get full usage statistics for the organization.
     *
     * @return array<string, mixed>
     */
    public function getUsage(Organization|int|null $organization = null): array
    {
        $org = $this->resolveOrganization($organization);
        if (! $org) {
            return [];
        }

        $subscription = $this->getCurrentSubscription($org);

        return $this->usageService->getUsage($org, $subscription?->plan);
    }

    /**
     * Generic limit check.
     */
    public function checkLimit(string $limitType, int $increment = 1, Organization|int|null $organization = null): bool
    {
        return match ($limitType) {
            'users' => $this->canAddUser($increment, $organization),
            'storage' => $this->canAddStorage($increment, $organization),
            'directions' => $this->canAddDirection($increment, $organization),
            'document_types' => $this->canAddDocumentType($increment, $organization),
            'ocr_pages' => $this->canProcessOcrPages($increment, $organization),
            default => true,
        };
    }

    public function canAddUser(int $count = 1, Organization|int|null $organization = null): bool
    {
        $org = $this->resolveOrganization($organization);
        $plan = $this->getCurrentPlan($org);
        if (! $org || ! $plan) {
            return true;
        }

        if ($plan->isUnlimitedUsers()) {
            return true;
        }

        $currentCount = $this->usageService->getUsersCount($org);

        return ($currentCount + $count) <= $plan->max_users;
    }

    public function canAddStorage(int $bytes = 0, Organization|int|null $organization = null): bool
    {
        $org = $this->resolveOrganization($organization);
        $plan = $this->getCurrentPlan($org);
        if (! $org || ! $plan) {
            return true;
        }

        if ($plan->isUnlimitedStorage()) {
            return true;
        }

        $currentBytes = $this->usageService->getStorageBytesUsed($org);

        return ($currentBytes + $bytes) <= $plan->max_storage_bytes;
    }

    public function canAddDirection(int $count = 1, Organization|int|null $organization = null): bool
    {
        $org = $this->resolveOrganization($organization);
        $plan = $this->getCurrentPlan($org);
        if (! $org || ! $plan) {
            return true;
        }

        if ($plan->isUnlimitedDirections()) {
            return true;
        }

        $currentCount = $this->usageService->getDirectionsCount($org);

        return ($currentCount + $count) <= $plan->max_directions;
    }

    public function canAddDocumentType(int $count = 1, Organization|int|null $organization = null): bool
    {
        $org = $this->resolveOrganization($organization);
        $plan = $this->getCurrentPlan($org);
        if (! $org || ! $plan) {
            return true;
        }

        if ($plan->isUnlimitedDocumentTypes()) {
            return true;
        }

        $currentCount = $this->usageService->getDocumentTypesCount($org);

        return ($currentCount + $count) <= $plan->max_document_types;
    }

    public function canProcessOcrPages(int $pages = 1, Organization|int|null $organization = null): bool
    {
        $org = $this->resolveOrganization($organization);
        $plan = $this->getCurrentPlan($org);
        if (! $org || ! $plan) {
            return true;
        }

        if ($plan->isUnlimitedOcr()) {
            return true;
        }

        $currentPages = $this->usageService->getOcrPagesThisMonth($org);

        return ($currentPages + $pages) <= $plan->max_ocr_pages_month;
    }

    public function assertCanAddUser(int $count = 1, Organization|int|null $organization = null): void
    {
        if (! $this->canAddUser($count, $organization)) {
            $org = $this->resolveOrganization($organization);
            $plan = $this->getCurrentPlan($org);
            $current = $org ? $this->usageService->getUsersCount($org) : 0;

            throw new SubscriptionLimitExceededException(
                limitType: 'users',
                currentUsage: $current,
                limit: $plan?->max_users ?? 0,
                planName: $plan?->name ?? 'Essentiel'
            );
        }
    }

    public function assertCanAddStorage(int $bytes = 0, Organization|int|null $organization = null): void
    {
        if (! $this->canAddStorage($bytes, $organization)) {
            $org = $this->resolveOrganization($organization);
            $plan = $this->getCurrentPlan($org);
            $current = $org ? $this->usageService->getStorageBytesUsed($org) : 0;

            throw new SubscriptionLimitExceededException(
                limitType: 'storage',
                currentUsage: $current,
                limit: $plan?->max_storage_bytes ?? 0,
                planName: $plan?->name ?? 'Essentiel'
            );
        }
    }

    public function assertCanAddDirection(int $count = 1, Organization|int|null $organization = null): void
    {
        if (! $this->canAddDirection($count, $organization)) {
            $org = $this->resolveOrganization($organization);
            $plan = $this->getCurrentPlan($org);
            $current = $org ? $this->usageService->getDirectionsCount($org) : 0;

            throw new SubscriptionLimitExceededException(
                limitType: 'directions',
                currentUsage: $current,
                limit: $plan?->max_directions ?? 0,
                planName: $plan?->name ?? 'Essentiel'
            );
        }
    }

    public function assertCanAddDocumentType(int $count = 1, Organization|int|null $organization = null): void
    {
        if (! $this->canAddDocumentType($count, $organization)) {
            $org = $this->resolveOrganization($organization);
            $plan = $this->getCurrentPlan($org);
            $current = $org ? $this->usageService->getDocumentTypesCount($org) : 0;

            throw new SubscriptionLimitExceededException(
                limitType: 'document_types',
                currentUsage: $current,
                limit: $plan?->max_document_types ?? 0,
                planName: $plan?->name ?? 'Essentiel'
            );
        }
    }

    public function assertCanProcessOcr(int $pages = 1, Organization|int|null $organization = null): void
    {
        if (! $this->canProcessOcrPages($pages, $organization)) {
            $org = $this->resolveOrganization($organization);
            $plan = $this->getCurrentPlan($org);
            $current = $org ? $this->usageService->getOcrPagesThisMonth($org) : 0;

            throw new SubscriptionLimitExceededException(
                limitType: 'ocr_pages',
                currentUsage: $current,
                limit: $plan?->max_ocr_pages_month ?? 0,
                planName: $plan?->name ?? 'Essentiel'
            );
        }
    }

    /**
     * Start a trial for an organization.
     */
    public function startTrial(Organization $organization, ?Plan $plan = null, int $days = 14, ?User $actor = null): Subscription
    {
        $targetPlan = $plan ?? Plan::where('slug', 'essential')->firstOrFail();
        $now = now();
        $trialEndsAt = (clone $now)->addDays($days);

        $subscription = Subscription::create([
            'organization_id' => $organization->id,
            'plan_id' => $targetPlan->id,
            'billing_cycle' => 'monthly',
            'status' => 'trialing',
            'starts_at' => $now,
            'trial_starts_at' => $now,
            'trial_ends_at' => $trialEndsAt,
            'current_period_starts_at' => $now,
            'current_period_ends_at' => $trialEndsAt,
            'auto_renew' => true,
            'provider' => 'manual',
        ]);

        $this->auditService->success(
            action: 'subscription.trial_started',
            auditable: $subscription,
            newValues: [
                'plan' => $targetPlan->name,
                'days' => $days,
                'trial_ends_at' => $trialEndsAt->toISOString(),
            ],
            user: $actor,
            organizationId: $organization->id,
            description: "Démarrage de la période d'essai de {$days} jours pour le plan {$targetPlan->name}"
        );

        return $subscription;
    }

    /**
     * Change plan and billing cycle for an organization.
     */
    public function changePlan(Organization $organization, Plan $newPlan, string $cycle = 'monthly', ?User $actor = null): Subscription
    {
        $subscription = $this->getCurrentSubscription($organization);
        $oldPlan = $subscription?->plan;
        $oldCycle = $subscription?->billing_cycle;

        if (! $subscription) {
            $subscription = $this->startTrial($organization, $newPlan, 14, $actor);
        }

        $updated = $this->getProvider()->changeSubscription($subscription, $newPlan, $cycle);

        $this->auditService->success(
            action: 'subscription.plan_changed',
            auditable: $updated,
            oldValues: [
                'plan' => $oldPlan?->name,
                'billing_cycle' => $oldCycle,
            ],
            newValues: [
                'plan' => $newPlan->name,
                'billing_cycle' => $cycle,
                'status' => $updated->status,
            ],
            user: $actor,
            organizationId: $organization->id,
            description: "Changement d'abonnement vers le plan {$newPlan->name} ({$cycle})"
        );

        // Notify organization administrators
        $this->notifyAdmins(
            $organization,
            $oldPlan && $oldPlan->id !== $newPlan->id
                ? new SubscriptionPlanChangedNotification($updated, $oldPlan, $newPlan)
                : new SubscriptionActivatedNotification($updated, $newPlan)
        );

        return $updated;
    }

    /**
     * Cancel the subscription.
     */
    public function cancelSubscription(Organization $organization, bool $immediately = false, ?User $actor = null): Subscription
    {
        $subscription = $this->getCurrentSubscription($organization);
        if (! $subscription) {
            throw new \RuntimeException('Aucun abonnement trouvé pour cette organisation.');
        }

        $cancelled = $this->getProvider()->cancelSubscription($subscription, $immediately);

        $this->auditService->success(
            action: 'subscription.cancelled',
            auditable: $cancelled,
            user: $actor,
            organizationId: $organization->id,
            description: $immediately ? "Résiliation immédiate de l'abonnement" : "Annulation du renouvellement automatique de l'abonnement"
        );

        $this->notifyAdmins($organization, new SubscriptionCancelledNotification($cancelled));

        return $cancelled;
    }

    /**
     * Resume a cancelled subscription before expiration.
     */
    public function resumeSubscription(Organization $organization, ?User $actor = null): Subscription
    {
        $subscription = $this->getCurrentSubscription($organization);
        if (! $subscription) {
            throw new \RuntimeException('Aucun abonnement trouvé pour cette organisation.');
        }

        $resumed = $this->getProvider()->resumeSubscription($subscription);

        $this->auditService->success(
            action: 'subscription.resumed',
            auditable: $resumed,
            user: $actor,
            organizationId: $organization->id,
            description: "Reprise de l'abonnement actif"
        );

        return $resumed;
    }

    /**
     * Extend a trial period for an organization (admin feature).
     */
    public function extendTrial(Organization $organization, int $days, ?User $actor = null): Subscription
    {
        $subscription = $this->getCurrentSubscription($organization);
        if (! $subscription) {
            $subscription = $this->startTrial($organization, null, $days, $actor);

            return $subscription;
        }

        $currentEnd = $subscription->trial_ends_at && $subscription->trial_ends_at->isFuture()
            ? $subscription->trial_ends_at
            : now();

        $newTrialEnd = (clone $currentEnd)->addDays($days);

        $subscription->update([
            'status' => 'trialing',
            'trial_ends_at' => $newTrialEnd,
            'current_period_ends_at' => $newTrialEnd,
        ]);

        $this->auditService->success(
            action: 'subscription.trial_extended',
            auditable: $subscription,
            newValues: ['extended_days' => $days, 'new_trial_ends_at' => $newTrialEnd->toISOString()],
            user: $actor,
            organizationId: $organization->id,
            description: "Prolongation de l'essai de {$days} jours"
        );

        return $subscription->fresh();
    }

    /**
     * Check if subscription has expired and mark it as expired.
     */
    public function checkAndHandleExpiration(Organization $organization): ?Subscription
    {
        $subscription = $this->getCurrentSubscription($organization);
        if (! $subscription) {
            return null;
        }

        if ($subscription->isExpired() && $subscription->status !== 'expired') {
            $subscription->update([
                'status' => 'expired',
                'ended_at' => now(),
            ]);

            $this->auditService->success(
                action: 'subscription.expired',
                auditable: $subscription,
                organizationId: $organization->id,
                description: "Expiration de l'abonnement ou de l'essai"
            );

            $this->notifyAdmins($organization, new SubscriptionExpiredNotification($subscription));
        }

        return $subscription->fresh();
    }

    /**
     * Helper to dispatch notifications to admins in an organization.
     */
    protected function notifyAdmins(Organization $organization, \Illuminate\Notifications\Notification $notification): void
    {
        $admins = User::where('organization_id', $organization->id)
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['admin', 'super-admin']);
            })
            ->get();

        if ($admins->isNotEmpty()) {
            Notification::send($admins, $notification);
        }
    }
}
