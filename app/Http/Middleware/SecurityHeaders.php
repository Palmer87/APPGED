<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request and apply security response headers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // 1. Anti-MIME-sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // 2. Anti-clickjacking (SAMEORIGIN allows in-app iframe previews for PDFs on ged.laravel.cloud)
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // 3. Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // 4. Permissions Policy (restrict sensitive hardware features)
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        // 5. HSTS (Strict-Transport-Security) - enforce over HTTPS or in production
        if ($request->isSecure() || $request->server('HTTPS') === 'on' || $request->header('X-Forwarded-Proto') === 'https' || app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        // 6. Content Security Policy (CSP)
        $csp = $this->buildCsp($request);
        $response->headers->set('Content-Security-Policy', $csp);

        // 7. Cache-Control hardening for authenticated tenant & platform sessions
        if ($request->user() || $request->user('platform') || $request->is('platform*') || $request->is('documents*') || $request->is('dashboard*')) {
            $response->headers->set('Cache-Control', 'no-store, private, max-age=0, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
        }

        return $response;
    }

    /**
     * Build Content Security Policy tailored for APPGED architecture.
     */
    protected function buildCsp(Request $request): string
    {
        $isLocal = app()->environment('local');

        // Local development allows Vite HMR client (IPv4/IPv6 [::1]), React Refresh inline scripts, stylesheets and websockets
        $scriptSrc = $isLocal
            ? "'self' 'unsafe-eval' 'unsafe-inline' http: ws:"
            : "'self'";

        $styleSrc = $isLocal
            ? "'self' 'unsafe-inline' https://fonts.bunny.net https://fonts.googleapis.com http:"
            : "'self' 'unsafe-inline' https://fonts.bunny.net https://fonts.googleapis.com";

        $connectSrc = $isLocal
            ? "'self' https://fonts.bunny.net https://fonts.googleapis.com http: ws:"
            : "'self' https://fonts.bunny.net https://fonts.googleapis.com";

        // Directives breakdown:
        // default-src: restrict fallback to same origin
        // script-src: 'self' in production (no unsafe-eval, no arbitrary CDNs)
        // style-src: allows local stylesheets, Google & Bunny fonts CSS, and 'unsafe-inline' for React dynamic style props
        // font-src: allows local bundled fonts, Bunny fonts, Google fonts, and data: URIs
        // img-src: allows local assets, data URIs, blob URIs, and HTTPS image sources (e.g., Cloudflare R2 / avatars)
        // media-src: allows audio/video from same origin and blobs
        // frame-src: allows 'self' and blob: for in-browser PDF previews inside <iframe>
        // frame-ancestors: restricts framing exclusively to 'self' (prevents third-party clickjacking)
        // object-src: 'none' blocks legacy Flash / Java / browser plugins
        // base-uri: 'self' prevents base tag hijacking
        // form-action: 'self' prevents form exfiltration
        $directives = [
            "default-src 'self'",
            "script-src {$scriptSrc}",
            "style-src {$styleSrc}",
            "font-src 'self' https://fonts.bunny.net https://fonts.gstatic.com data:",
            "img-src 'self' data: blob: https:",
            "media-src 'self' data: blob:",
            "connect-src {$connectSrc}",
            "frame-src 'self' blob:",
            "frame-ancestors 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ];

        if ($request->isSecure() || app()->environment('production')) {
            $directives[] = 'upgrade-insecure-requests';
        }

        return implode('; ', $directives);
    }
}
