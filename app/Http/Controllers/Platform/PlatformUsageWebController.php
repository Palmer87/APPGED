<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\QuotaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PlatformUsageWebController extends Controller
{
    public function __construct(
        protected QuotaService $quotaService
    ) {}

    /**
     * Display SaaS global usage and per-organization quotas breakdown.
     */
    public function index(Request $request): Response
    {
        $globalMetrics = $this->quotaService->getGlobalUsageMetrics();

        $query = Organization::with('currentSubscription.plan');

        if ($search = $request->input('search')) {
            $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where('name', $like, "%{$search}%");
        }

        $organizations = $query->paginate(15)->withQueryString()->through(function ($org) {
            return $this->quotaService->getOrganizationQuotas($org);
        });

        return Inertia::render('Platform/Usage/Index', [
            'globalMetrics' => $globalMetrics,
            'organizations' => $organizations,
            'filters' => $request->only(['search']),
        ]);
    }
}
