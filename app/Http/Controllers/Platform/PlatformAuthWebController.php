<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformUser;
use App\Services\PlatformAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PlatformAuthWebController extends Controller
{
    public function __construct(
        protected PlatformAuditService $auditService
    ) {}

    /**
     * Show the platform login page.
     */
    public function create(): Response|RedirectResponse
    {
        if (Auth::guard('platform')->check()) {
            return redirect()->route('platform.dashboard');
        }

        return Inertia::render('Platform/Login');
    }

    /**
     * Handle platform login attempt.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        $credentials = $request->only('email', 'password');
        $remember = (bool) $request->input('remember', false);

        if (! Auth::guard('platform')->attempt($credentials, $remember)) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                'email' => __('Les identifiants fournis ne correspondent à aucun compte administrateur plateforme.'),
            ]);
        }

        RateLimiter::clear($throttleKey);

        /** @var PlatformUser $user */
        $user = Auth::guard('platform')->user();

        if (! $user->is_active) {
            Auth::guard('platform')->logout();

            throw ValidationException::withMessages([
                'email' => __('Ce compte administrateur plateforme est désactivé.'),
            ]);
        }

        $user->update(['last_login_at' => now()]);

        $request->session()->regenerate();

        $this->auditService->log(
            action: 'platform.auth.login',
            description: "Connexion réussie de l'administrateur plateforme '{$user->name}' ({$user->role}).",
            actor: $user
        );

        return redirect()->intended(route('platform.dashboard'));
    }

    /**
     * Handle platform logout.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::guard('platform')->user();

        if ($user) {
            $this->auditService->log(
                action: 'platform.auth.logout',
                description: "Déconnexion de l'administrateur plateforme '{$user->name}'.",
                actor: $user
            );
        }

        Auth::guard('platform')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('platform.login');
    }
}
