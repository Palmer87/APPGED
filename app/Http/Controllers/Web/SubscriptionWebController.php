<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePlanRequest;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\BillingService;
use App\Services\SubscriptionUsageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionWebController extends Controller
{
    public function __construct(
        protected BillingService $billingService,
        protected SubscriptionUsageService $usageService
    ) {}

    /**
     * Display the organization's subscription overview and usage details.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $org = Organization::findOrFail($user->organization_id);

        $subscription = $this->billingService->getCurrentSubscription($org);
        Gate::authorize('view', $subscription ?? Subscription::class);

        $usage = $this->usageService->getUsage($org, $subscription?->plan);

        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $invoices = Invoice::where('organization_id', $org->id)
            ->latest('id')
            ->take(10)
            ->get()
            ->map(fn (Invoice $inv) => [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'amount' => $inv->amount,
                'currency' => $inv->currency,
                'status' => $inv->status,
                'paid_at' => $inv->paid_at?->format('d/m/Y'),
                'created_at' => $inv->created_at?->format('d/m/Y'),
            ]);

        $canManage = Gate::allows('manage', $subscription);

        return Inertia::render('Subscription/Show', [
            'subscription' => $subscription ? [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'billing_cycle' => $subscription->billing_cycle,
                'is_active' => $subscription->isActive(),
                'is_trial' => $subscription->isTrial(),
                'is_expired' => $subscription->isExpired(),
                'trial_days_remaining' => $subscription->trialDaysRemaining(),
                'trial_ends_at' => $subscription->trial_ends_at?->format('d/m/Y'),
                'current_period_ends_at' => $subscription->current_period_ends_at?->format('d/m/Y'),
                'auto_renew' => (bool) $subscription->auto_renew,
                'cancelled_at' => $subscription->cancelled_at?->format('d/m/Y'),
                'plan' => $subscription->plan ? [
                    'id' => $subscription->plan->id,
                    'name' => $subscription->plan->name,
                    'slug' => $subscription->plan->slug,
                    'description' => $subscription->plan->description,
                    'monthly_price' => $subscription->plan->monthly_price,
                    'annual_price' => $subscription->plan->annual_price,
                    'currency' => $subscription->plan->currency,
                ] : null,
            ] : null,
            'usage' => $usage,
            'invoices' => $invoices,
            'plans' => $plans,
            'can' => [
                'manage' => $canManage,
            ],
        ]);
    }

    /**
     * Display the plan selection interface.
     */
    public function choose(Request $request): Response
    {
        $user = $request->user();
        $org = Organization::findOrFail($user->organization_id);

        $subscription = $this->billingService->getCurrentSubscription($org);
        Gate::authorize('view', $subscription ?? Subscription::class);

        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Subscription/Choose', [
            'currentSubscription' => $subscription ? [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'billing_cycle' => $subscription->billing_cycle,
                'plan_slug' => $subscription->plan?->slug,
            ] : null,
            'plans' => $plans,
            'can' => [
                'manage' => Gate::allows('manage', $subscription),
            ],
        ]);
    }

    /**
     * Handle plan upgrade, downgrade, or cycle change.
     */
    public function changePlan(ChangePlanRequest $request): RedirectResponse
    {
        $user = $request->user();
        $org = Organization::findOrFail($user->organization_id);

        $subscription = $this->billingService->getCurrentSubscription($org);
        Gate::authorize('manage', $subscription ?? Subscription::class);

        $validated = $request->validated();

        $plan = isset($validated['plan_id'])
            ? Plan::findOrFail($validated['plan_id'])
            : Plan::where('slug', $validated['plan_slug'])->firstOrFail();

        $this->billingService->changePlan($org, $plan, $validated['billing_cycle'], $user);

        return redirect()->route('subscription.show')
            ->with('success', "Votre abonnement a été mis à jour vers le plan {$plan->name}.");
    }

    /**
     * Cancel the active subscription (disable auto-renewal).
     */
    public function cancel(Request $request): RedirectResponse
    {
        $user = $request->user();
        $org = Organization::findOrFail($user->organization_id);

        $subscription = $this->billingService->getCurrentSubscription($org);
        Gate::authorize('manage', $subscription ?? Subscription::class);

        $this->billingService->cancelSubscription($org, false, $user);

        return back()->with('success', 'Le renouvellement automatique de votre abonnement a été désactivé.');
    }

    /**
     * Resume a cancelled subscription.
     */
    public function resume(Request $request): RedirectResponse
    {
        $user = $request->user();
        $org = Organization::findOrFail($user->organization_id);

        $subscription = $this->billingService->getCurrentSubscription($org);
        Gate::authorize('manage', $subscription ?? Subscription::class);

        $this->billingService->resumeSubscription($org, $user);

        return back()->with('success', 'Votre abonnement a été réactivé avec succès.');
    }
}
