<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Direction;
use App\Models\Document;
use App\Models\Folder;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\PlatformUser;
use App\Models\Service;
use App\Models\User;
use App\Services\PlatformOrganizationService;
use App\Services\QuotaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PlatformOrganizationWebController extends Controller
{
    public function __construct(
        protected PlatformOrganizationService $orgService,
        protected QuotaService $quotaService
    ) {}

    /**
     * Display a paginated listing of client organizations.
     */
    public function index(Request $request): Response
    {
        $query = Organization::query()
            ->with(['currentSubscription.plan', 'users' => fn ($q) => $q->oldest()])
            ->withCount(['users', 'directions', 'services']);

        // Search filter
        if ($search = $request->input('search')) {
            $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($search, $like) {
                $q->where('name', $like, "%{$search}%")
                    ->orWhere('email', $like, "%{$search}%")
                    ->orWhere('slug', $like, "%{$search}%");
            });
        }

        // Status filter
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Plan filter
        if ($planId = $request->input('plan_id')) {
            $query->whereHas('currentSubscription', function ($q) use ($planId) {
                $q->where('plan_id', $planId);
            });
        }

        // Sorting
        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');
        if (in_array($sort, ['name', 'created_at', 'status'], true)) {
            $query->orderBy($sort, $direction === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        $organizations = $query->paginate(15)->withQueryString()->through(function ($org) {
            $storageBytes = (int) Document::where('organization_id', $org->id)->whereNull('deleted_at')->sum('size');
            $docsCount = Document::where('organization_id', $org->id)->whereNull('deleted_at')->count();
            $sub = $org->currentSubscription;
            $adminUser = $org->users->first();

            return [
                'id' => $org->id,
                'name' => $org->name,
                'slug' => $org->slug,
                'email' => $org->email,
                'status' => $org->status,
                'created_at' => $org->created_at?->format('d/m/Y'),
                'admin_name' => $adminUser ? $adminUser->name : 'N/A',
                'admin_email' => $adminUser ? $adminUser->email : null,
                'plan_name' => $sub?->plan?->name ?? 'Essentiel',
                'billing_cycle' => $sub?->billing_cycle ?? 'monthly',
                'subscription_status' => $sub?->status ?? 'trialing',
                'trial_days_remaining' => $sub?->trialDaysRemaining() ?? 0,
                'is_trial' => $sub?->isTrial() ?? true,
                'users_count' => $org->users_count,
                'documents_count' => $docsCount,
                'storage_bytes' => $storageBytes,
                'storage_formatted' => $this->formatBytes($storageBytes),
            ];
        });

        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'slug']);

        return Inertia::render('Platform/Organizations/Index', [
            'organizations' => $organizations,
            'plans' => $plans,
            'filters' => $request->only(['search', 'status', 'plan_id', 'sort', 'direction']),
        ]);
    }

    /**
     * Display the detailed organization management view.
     */
    public function show(Organization $organization): Response
    {
        $organization->load([
            'currentSubscription.plan',
            'invoices' => fn ($q) => $q->latest()->take(5),
            'payments' => fn ($q) => $q->latest()->take(5),
            'supportTickets' => fn ($q) => $q->latest()->take(5),
            'platformAuditLogs' => fn ($q) => $q->with('platformUser')->latest('created_at')->take(10),
        ]);

        $users = User::where('organization_id', $organization->id)
            ->with(['roles', 'primaryService.direction'])
            ->latest()
            ->paginate(10, ['*'], 'users_page');

        $quotas = $this->quotaService->getOrganizationQuotas($organization);
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();

        $stats = [
            'users_count' => User::where('organization_id', $organization->id)->count(),
            'directions_count' => Direction::where('organization_id', $organization->id)->count(),
            'services_count' => Service::where('organization_id', $organization->id)->count(),
            'document_types_count' => Folder::where('organization_id', $organization->id)->where('folder_type', 'document_type')->count(),
            'documents_count' => Document::where('organization_id', $organization->id)->whereNull('deleted_at')->count(),
            'storage_bytes' => (int) Document::where('organization_id', $organization->id)->whereNull('deleted_at')->sum('size'),
        ];

        return Inertia::render('Platform/Organizations/Show', [
            'organization' => $organization,
            'users' => $users,
            'quotas' => $quotas,
            'stats' => $stats,
            'plans' => $plans,
        ]);
    }

    /**
     * Suspend an organization.
     */
    public function suspend(Request $request, Organization $organization): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        /** @var PlatformUser $actor */
        $actor = Auth::guard('platform')->user();

        if (! $actor->canSuspendOrganizations()) {
            abort(403, 'Vous ne disposez pas des permissions requises pour suspendre une organisation.');
        }

        $this->orgService->suspendOrganization($organization, $request->input('reason'), $actor);

        return back()->with('success', "L'organisation '{$organization->name}' a été suspendue avec succès.");
    }

    /**
     * Reactivate a suspended organization.
     */
    public function reactivate(Organization $organization): RedirectResponse
    {
        /** @var PlatformUser $actor */
        $actor = Auth::guard('platform')->user();

        if (! $actor->canSuspendOrganizations()) {
            abort(403, 'Vous ne disposez pas des permissions requises pour réactiver une organisation.');
        }

        $this->orgService->reactivateOrganization($organization, $actor);

        return back()->with('success', "L'organisation '{$organization->name}' a été réactivée avec succès.");
    }

    /**
     * Change subscription plan for an organization.
     */
    public function updateSubscription(Request $request, Organization $organization): RedirectResponse
    {
        $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'billing_cycle' => ['required', 'in:monthly,annual'],
        ]);

        /** @var PlatformUser $actor */
        $actor = Auth::guard('platform')->user();

        if (! $actor->canManageSubscriptions()) {
            abort(403, 'Permissions insuffisantes pour modifier un abonnement.');
        }

        $plan = Plan::findOrFail($request->input('plan_id'));
        $this->orgService->changePlan($organization, $plan, $request->input('billing_cycle'), $actor);

        return back()->with('success', "L'abonnement de '{$organization->name}' a été mis à jour vers le plan '{$plan->name}'.");
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2, ',', ' ').' Go';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2, ',', ' ').' Mo';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2, ',', ' ').' Ko';
        }

        return $bytes.' o';
    }
}
