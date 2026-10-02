<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\PlatformAuditLog;
use App\Models\Subscription;
use App\Models\SupportTicket;
use App\Services\QuotaService;
use Inertia\Inertia;
use Inertia\Response;

class PlatformDashboardWebController extends Controller
{
    public function __construct(
        protected QuotaService $quotaService
    ) {}

    /**
     * Display the SaaS platform executive dashboard.
     */
    public function index(): Response
    {
        $metrics = $this->quotaService->getGlobalUsageMetrics();

        // Subscriptions breakdown
        $activeSubsCount = Subscription::where('status', 'active')->count();
        $trialSubsCount = Subscription::where('status', 'trialing')->count();
        $expiredSubsCount = Subscription::whereIn('status', ['expired', 'past_due'])->count();
        $cancelledSubsCount = Subscription::where('status', 'cancelled')->count();

        // Revenue calculations (MRR / ARR based on active paid subscriptions)
        $activeSubscriptions = Subscription::where('status', 'active')->with('plan')->get();
        $mrr = 0;
        foreach ($activeSubscriptions as $sub) {
            $plan = $sub->plan;
            if (! $plan) {
                continue;
            }
            if ($sub->billing_cycle === 'annual' && $plan->annual_price) {
                $mrr += (int) round($plan->annual_price / 12);
            } elseif ($plan->monthly_price) {
                $mrr += $plan->monthly_price;
            }
        }
        $arr = $mrr * 12;

        $startOfMonth = now()->startOfMonth();
        $revenueThisMonth = (int) Payment::where('status', 'paid')
            ->where('paid_at', '>=', $startOfMonth)
            ->sum('amount');

        if ($revenueThisMonth === 0) {
            // Also check paid invoices as fallback
            $revenueThisMonth = (int) Invoice::where('status', 'paid')
                ->where('paid_at', '>=', $startOfMonth)
                ->sum('amount');
        }

        // Recent organizations
        $recentOrganizations = Organization::with(['currentSubscription.plan', 'users' => fn ($q) => $q->take(3)])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($org) {
                return [
                    'id' => $org->id,
                    'name' => $org->name,
                    'status' => $org->status,
                    'created_at' => $org->created_at?->format('d/m/Y H:i'),
                    'plan_name' => $org->currentSubscription?->plan?->name ?? 'Essentiel',
                    'subscription_status' => $org->currentSubscription?->status ?? 'trialing',
                    'trial_days_remaining' => $org->currentSubscription?->trialDaysRemaining() ?? 0,
                    'users_count' => $org->users()->count(),
                ];
            });

        // Recent tickets
        $recentTickets = SupportTicket::with('organization')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'ticket_number' => $t->ticket_number,
                    'subject' => $t->subject,
                    'priority' => $t->priority,
                    'status' => $t->status,
                    'organization_name' => $t->organization?->name ?? 'N/A',
                    'created_at' => $t->created_at?->diffForHumans(),
                ];
            });

        // Recent audit events
        $recentAuditLogs = PlatformAuditLog::with(['platformUser', 'organization'])
            ->latest('created_at')
            ->take(6)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'description' => $log->description,
                    'user_name' => $log->platformUser?->name ?? 'Système',
                    'organization_name' => $log->organization?->name,
                    'created_at' => $log->created_at?->diffForHumans(),
                ];
            });

        return Inertia::render('Platform/Dashboard', [
            'metrics' => [
                'organizations' => $metrics['organizations'],
                'users' => $metrics['users'],
                'documents' => $metrics['documents'],
                'storage' => $metrics['storage'],
                'ocr' => $metrics['ocr'],
                'subscriptions' => [
                    'active' => $activeSubsCount,
                    'trialing' => $trialSubsCount,
                    'expired' => $expiredSubsCount,
                    'cancelled' => $cancelledSubsCount,
                ],
                'revenue' => [
                    'mrr' => $mrr,
                    'arr' => $arr,
                    'revenue_this_month' => $revenueThisMonth,
                    'currency' => 'XOF',
                ],
            ],
            'recent_organizations' => $recentOrganizations,
            'recent_tickets' => $recentTickets,
            'recent_audit_logs' => $recentAuditLogs,
        ]);
    }
}
