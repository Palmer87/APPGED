<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\PlatformAuditLog;
use App\Models\PlatformUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PlatformAuditWebController extends Controller
{
    /**
     * Display SaaS platform audit logs.
     */
    public function index(Request $request): Response
    {
        $query = PlatformAuditLog::with(['platformUser', 'organization']);

        if ($action = $request->input('action')) {
            $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where('action', $like, "%{$action}%");
        }

        if ($platformUserId = $request->input('platform_user_id')) {
            $query->where('platform_user_id', $platformUserId);
        }

        if ($organizationId = $request->input('organization_id')) {
            $query->where('organization_id', $organizationId);
        }

        if ($from = $request->input('from')) {
            $query->where('created_at', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->where('created_at', '<=', $to.' 23:59:59');
        }

        $logs = $query->latest('created_at')->paginate(20)->withQueryString()->through(function ($log) {
            return [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'platform_user_name' => $log->platformUser?->name ?? 'Système',
                'platform_user_role' => $log->platformUser?->role,
                'organization_name' => $log->organization?->name ?? 'Global',
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at?->format('d/m/Y H:i:s'),
            ];
        });

        $platformUsers = PlatformUser::orderBy('name')->get(['id', 'name']);
        $organizations = Organization::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Platform/Audit/Index', [
            'logs' => $logs,
            'platformUsers' => $platformUsers,
            'organizations' => $organizations,
            'filters' => $request->only(['action', 'platform_user_id', 'organization_id', 'from', 'to']),
        ]);
    }
}
