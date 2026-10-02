<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\PlatformUser;
use App\Services\PlatformOrganizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PlatformTrialWebController extends Controller
{
    public function __construct(
        protected PlatformOrganizationService $orgService
    ) {}

    /**
     * Display a listing of client trials.
     */
    public function index(Request $request): Response
    {
        $query = Organization::whereHas('currentSubscription', function ($q) {
            $q->where('status', 'trialing')
                ->orWhereNotNull('trial_ends_at');
        })->with(['currentSubscription.plan', 'users' => fn ($q) => $q->take(1)]);

        if ($search = $request->input('search')) {
            $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where('name', $like, "%{$search}%");
        }

        $filter = $request->input('filter', 'all'); // all, active, expired
        if ($filter === 'active') {
            $query->whereHas('currentSubscription', function ($q) {
                $q->where('status', 'trialing')
                    ->where('trial_ends_at', '>', now());
            });
        } elseif ($filter === 'expired') {
            $query->whereHas('currentSubscription', function ($q) {
                $q->where('status', 'expired')
                    ->orWhere(function ($sub) {
                        $sub->where('status', 'trialing')->where('trial_ends_at', '<=', now());
                    });
            });
        }

        $trials = $query->latest()->paginate(15)->withQueryString()->through(function ($org) {
            $sub = $org->currentSubscription;
            $remaining = $sub?->trialDaysRemaining() ?? 0;
            $isExpired = $sub?->isTrialExpired() ?? false;

            return [
                'organization_id' => $org->id,
                'organization_name' => $org->name,
                'admin_name' => $org->users->first()?->name ?? 'N/A',
                'plan_name' => $sub?->plan?->name ?? 'Essentiel',
                'starts_at' => $sub?->trial_starts_at?->format('d/m/Y') ?? $sub?->starts_at?->format('d/m/Y'),
                'ends_at' => $sub?->trial_ends_at?->format('d/m/Y'),
                'days_remaining' => $remaining,
                'is_expired' => $isExpired,
                'status' => $sub?->status,
            ];
        });

        $plans = Plan::where('is_active', true)->get(['id', 'name']);

        return Inertia::render('Platform/Trials/Index', [
            'trials' => $trials,
            'plans' => $plans,
            'filters' => $request->only(['search', 'filter']),
        ]);
    }

    /**
     * Extend a trial period.
     */
    public function extend(Request $request, Organization $organization): RedirectResponse
    {
        $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:90'],
        ]);

        /** @var PlatformUser $actor */
        $actor = Auth::guard('platform')->user();

        $this->orgService->extendTrial($organization, (int) $request->input('days'), $actor);

        return back()->with('success', "Essai prolongé de {$request->input('days')} jours pour '{$organization->name}'.");
    }

    /**
     * End a trial period immediately.
     */
    public function end(Organization $organization): RedirectResponse
    {
        /** @var PlatformUser $actor */
        $actor = Auth::guard('platform')->user();

        $this->orgService->endTrial($organization, $actor);

        return back()->with('success', "Essai terminé immédiatement pour '{$organization->name}'.");
    }

    /**
     * Convert trial to paid subscription.
     */
    public function convert(Request $request, Organization $organization): RedirectResponse
    {
        $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'billing_cycle' => ['required', 'in:monthly,annual'],
        ]);

        /** @var PlatformUser $actor */
        $actor = Auth::guard('platform')->user();

        $plan = Plan::findOrFail($request->input('plan_id'));
        $this->orgService->changePlan($organization, $plan, $request->input('billing_cycle'), $actor);

        return back()->with('success', "L'essai a été converti en abonnement actif pour '{$organization->name}'.");
    }
}
