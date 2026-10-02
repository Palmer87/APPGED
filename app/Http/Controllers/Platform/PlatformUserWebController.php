<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PlatformUserWebController extends Controller
{
    /**
     * Display a global directory of all client organization users.
     */
    public function index(Request $request): Response
    {
        $query = User::with(['organization', 'roles', 'primaryService.direction']);

        if ($search = $request->input('search')) {
            $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($search, $like) {
                $q->where('first_name', $like, "%{$search}%")
                    ->orWhere('last_name', $like, "%{$search}%")
                    ->orWhere('email', $like, "%{$search}%");
            });
        }

        if ($organizationId = $request->input('organization_id')) {
            $query->where('organization_id', $organizationId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $users = $query->latest()->paginate(15)->withQueryString()->through(function ($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
                'job_title' => $u->job_title,
                'status' => $u->status,
                'organization_id' => $u->organization_id,
                'organization_name' => $u->organization?->name ?? 'N/A',
                'roles' => $u->roles->pluck('name'),
                'service_name' => $u->primaryService?->name,
                'direction_name' => $u->primaryService?->direction?->name,
                'created_at' => $u->created_at?->format('d/m/Y'),
                'last_login_at' => $u->last_login_at?->format('d/m/Y H:i'),
            ];
        });

        $organizations = Organization::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Platform/Users/Index', [
            'users' => $users,
            'organizations' => $organizations,
            'filters' => $request->only(['search', 'organization_id', 'status']),
        ]);
    }
}
