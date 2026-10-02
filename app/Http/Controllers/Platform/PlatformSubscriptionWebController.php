<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlatformUser;
use App\Models\Subscription;
use App\Services\PlatformAuditService;
use App\Services\PlatformOrganizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PlatformSubscriptionWebController extends Controller
{
    public function __construct(
        protected PlatformOrganizationService $orgService,
        protected PlatformAuditService $auditService
    ) {}

    /**
     * Display a listing of all subscriptions.
     */
    public function index(Request $request): Response
    {
        $query = Subscription::with(['organization', 'plan']);

        if ($search = $request->input('search')) {
            $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->whereHas('organization', function ($q) use ($search, $like) {
                $q->where('name', $like, "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($planId = $request->input('plan_id')) {
            $query->where('plan_id', $planId);
        }

        $subscriptions = $query->latest()->paginate(15)->withQueryString()->through(function ($sub) {
            return [
                'id' => $sub->id,
                'organization_id' => $sub->organization_id,
                'organization_name' => $sub->organization?->name ?? 'N/A',
                'plan_name' => $sub->plan?->name ?? 'Essentiel',
                'billing_cycle' => $sub->billing_cycle,
                'status' => $sub->status,
                'starts_at' => $sub->starts_at?->format('d/m/Y'),
                'current_period_ends_at' => $sub->current_period_ends_at?->format('d/m/Y'),
                'trial_ends_at' => $sub->trial_ends_at?->format('d/m/Y'),
                'trial_days_remaining' => $sub->trialDaysRemaining(),
                'is_trial' => $sub->isTrial(),
                'provider' => $sub->provider,
            ];
        });

        $plans = Plan::where('is_active', true)->get(['id', 'name']);

        return Inertia::render('Platform/Subscriptions/Index', [
            'subscriptions' => $subscriptions,
            'plans' => $plans,
            'filters' => $request->only(['search', 'status', 'plan_id']),
        ]);
    }

    /**
     * Display details of a subscription.
     */
    public function show(Subscription $subscription): Response
    {
        $subscription->load([
            'organization.users',
            'plan',
            'invoices' => fn ($q) => $q->latest(),
            'payments' => fn ($q) => $q->latest(),
        ]);

        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();

        return Inertia::render('Platform/Subscriptions/Show', [
            'subscription' => $subscription,
            'plans' => $plans,
        ]);
    }

    /**
     * Cancel a subscription.
     */
    public function cancel(Request $request, Subscription $subscription): RedirectResponse
    {
        /** @var PlatformUser $actor */
        $actor = Auth::guard('platform')->user();

        if (! $actor->canManageSubscriptions()) {
            abort(403, 'Action non autorisée.');
        }

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'auto_renew' => false,
        ]);

        $this->auditService->log(
            action: 'platform.subscription.cancelled',
            description: "Abonnement #{$subscription->id} résilié pour '{$subscription->organization?->name}'.",
            target: $subscription,
            organizationId: $subscription->organization_id,
            actor: $actor
        );

        return back()->with('success', 'Abonnement résilié avec succès.');
    }

    /**
     * Extend a subscription trial.
     */
    public function extendTrial(Request $request, Subscription $subscription): RedirectResponse
    {
        $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:90'],
        ]);

        /** @var PlatformUser $actor */
        $actor = Auth::guard('platform')->user();

        if (! $actor->canManageSubscriptions()) {
            abort(403, 'Action non autorisée.');
        }

        $this->orgService->extendTrial($subscription->organization, (int) $request->input('days'), $actor);

        return back()->with('success', "Période d'essai prolongée de {$request->input('days')} jours.");
    }

    /**
     * End a subscription trial immediately.
     */
    public function endTrial(Subscription $subscription): RedirectResponse
    {
        /** @var PlatformUser $actor */
        $actor = Auth::guard('platform')->user();

        if (! $actor->canManageSubscriptions()) {
            abort(403, 'Action non autorisée.');
        }

        $this->orgService->endTrial($subscription->organization, $actor);

        return back()->with('success', "Période d'essai terminée immédiatement.");
    }
}
