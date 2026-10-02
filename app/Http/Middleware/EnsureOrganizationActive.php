<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationActive
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('web') ?: $request->user();

        if ($user instanceof User && $user->organization_id) {
            $org = Organization::find($user->organization_id);
            if ($org && $org->isSuspended()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Votre organisation a été suspendue par la plateforme GEDAPP. Veuillez contacter le support.',
                    ], 403);
                }

                abort(403, 'Votre organisation a été suspendue par la plateforme GEDAPP. Veuillez contacter le support.');
            }
        }

        return $next($request);
    }
}
