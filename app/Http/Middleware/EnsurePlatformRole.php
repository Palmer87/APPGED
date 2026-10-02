<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = Auth::guard('platform')->user();

        if (! $user) {
            abort(401, 'Non authentifié.');
        }

        if ($user->isOwner()) {
            return $next($request);
        }

        if (empty($roles)) {
            return $next($request);
        }

        if (! $user->hasRole($roles)) {
            abort(403, 'Action non autorisée pour votre rôle plateforme.');
        }

        return $next($request);
    }
}
