<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Spatie\Permission\PermissionRegistrar;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    /**
     * Display the authenticated user's GED dashboard.
     */
    public function index(Request $request): JsonResponse|View|Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated');
        }

        if ($user->organization_id) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($user->organization_id);
        }

        $period = (string) $request->input('period', '30d');

        if (! in_array($period, DashboardService::ALLOWED_PERIODS, true)) {
            $period = '30d';
        }

        $data = $this->dashboardService->getDashboardData($user, $period);

        if ($request->wantsJson() || $request->header('X-Inertia')) {
            if (function_exists('inertia') && $request->header('X-Inertia')) {
                return inertia('Dashboard/Index', $data);
            }

            return response()->json($data);
        }

        if (view()->exists('dashboard')) {
            return view('dashboard', $data);
        }

        return response()->json($data);
    }
}
