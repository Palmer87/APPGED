<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiTeamContext
{
    /**
     * Handle an incoming request.
     *
     * Sets Spatie permissions team context to the authenticated user's organization.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->organization_id) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($user->organization_id);
        }

        return $next($request);
    }
}
