<?php

namespace App\Services\Billing\Providers;

use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Billing\Contracts\BillingProviderInterface;
use Illuminate\Support\Str;

class ManualBillingProvider implements BillingProviderInterface
{
    public function getName(): string
    {
        return 'manual';
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function createCheckout(Subscription $subscription, Plan $plan, string $cycle, array $options = []): array
    {
        $amount = $cycle === 'annual' ? ($plan->annual_price ?? 0) : ($plan->monthly_price ?? 0);

        return [
            'checkout_url' => null,
            'reference' => 'MAN-'.strtoupper(Str::random(10)),
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'cycle' => $cycle,
            'amount' => $amount,
            'currency' => $plan->currency ?? 'XOF',
            'status' => 'ready',
            'provider' => $this->getName(),
        ];
    }

    public function changeSubscription(Subscription $subscription, Plan $newPlan, string $cycle): Subscription
    {
        $now = now();
        $endsAt = $cycle === 'annual' ? (clone $now)->addYear() : (clone $now)->addMonth();

        $subscription->update([
            'plan_id' => $newPlan->id,
            'billing_cycle' => in_array($cycle, ['monthly', 'annual'], true) ? $cycle : 'monthly',
            'status' => 'active',
            'starts_at' => $subscription->starts_at ?? $now,
            'current_period_starts_at' => $now,
            'current_period_ends_at' => $endsAt,
            'cancelled_at' => null,
            'ended_at' => null,
            'trial_ends_at' => null,
            'auto_renew' => true,
            'provider' => $this->getName(),
            'provider_subscription_id' => 'SUB-MAN-'.strtoupper(Str::random(8)),
        ]);

        // Generate invoice if price is defined (i.e. not Enterprise / custom without price)
        $amount = $cycle === 'annual' ? $newPlan->annual_price : $newPlan->monthly_price;
        if ($amount !== null && $amount > 0) {
            $invoiceNumber = 'FAC-'.date('Ymd').'-'.strtoupper(Str::random(5));
            Invoice::create([
                'organization_id' => $subscription->organization_id,
                'subscription_id' => $subscription->id,
                'invoice_number' => $invoiceNumber,
                'amount' => $amount,
                'currency' => $newPlan->currency ?? 'XOF',
                'tax' => 0,
                'subtotal' => $amount,
                'total' => $amount,
                'status' => 'paid',
                'paid_at' => $now,
                'due_at' => $now,
                'provider' => $this->getName(),
                'provider_payment_id' => 'PAY-MAN-'.strtoupper(Str::random(8)),
                'metadata' => [
                    'plan_name' => $newPlan->name,
                    'cycle' => $cycle,
                    'description' => "Souscription {$newPlan->name} ({$cycle})",
                ],
            ]);
        }

        return $subscription->fresh(['plan', 'organization']);
    }

    public function cancelSubscription(Subscription $subscription, bool $immediately = false): Subscription
    {
        $now = now();

        $subscription->update([
            'cancelled_at' => $now,
            'auto_renew' => false,
            'status' => $immediately ? 'cancelled' : $subscription->status,
            'ended_at' => $immediately ? $now : $subscription->ended_at,
        ]);

        return $subscription->fresh();
    }

    public function resumeSubscription(Subscription $subscription): Subscription
    {
        $subscription->update([
            'cancelled_at' => null,
            'auto_renew' => true,
            'status' => 'active',
            'ended_at' => null,
        ]);

        return $subscription->fresh();
    }

    public function getSubscription(string $providerSubscriptionId): ?array
    {
        return [
            'id' => $providerSubscriptionId,
            'provider' => $this->getName(),
            'status' => 'active',
        ];
    }
}
