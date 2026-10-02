<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformUser
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('platform')->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated from platform.'], 401);
            }

            return redirect()->route('platform.login');
        }

        $user = Auth::guard('platform')->user();

        if (! $user->is_active) {
            Auth::guard('platform')->logout();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Ce compte administrateur plateforme est inactif.'], 403);
            }

            return redirect()->route('platform.login')->withErrors([
                'email' => 'Ce compte administrateur plateforme est désactivé.',
            ]);
        }

        return $next($request);
    }
}
