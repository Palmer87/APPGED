<?php

namespace App\Services\Billing\Contracts;

use App\Models\Plan;
use App\Models\Subscription;

interface BillingProviderInterface
{
    /**
     * Get the identifier name of the provider (e.g. 'manual', 'stripe', 'cinetpay', 'flutterwave').
     */
    public function getName(): string;

    /**
     * Create a checkout session or transaction intent for a plan subscription.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function createCheckout(Subscription $subscription, Plan $plan, string $cycle, array $options = []): array;

    /**
     * Change or switch the subscription to a new plan or cycle.
     */
    public function changeSubscription(Subscription $subscription, Plan $newPlan, string $cycle): Subscription;

    /**
     * Cancel an active subscription.
     */
    public function cancelSubscription(Subscription $subscription, bool $immediately = false): Subscription;

    /**
     * Resume a cancelled subscription before expiration.
     */
    public function resumeSubscription(Subscription $subscription): Subscription;

    /**
     * Fetch subscription status and details from provider.
     *
     * @return array<string, mixed>|null
     */
    public function getSubscription(string $providerSubscriptionId): ?array;
}
