<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds sensible security headers to every response. These reduce the risk of
 * clickjacking (X-Frame-Options), MIME sniffing, mixed-content and supply the
 * browser with a content security policy and referrer policy.
 */
class SetSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // HSTS only over HTTPS so local HTTP development isn't broken.
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $response->headers->set('Content-Security-Policy', $this->csp());

        return $response;
    }

    private function csp(): string
    {
        $self = "'self'";
        $inline = "'unsafe-inline'";
        $data = 'data:';
        $blob = 'blob:';

        // The font stylesheet + font files come from Bunny CDN.
        $bunny = 'https://fonts.bunny.net';

        // In development, Vite serves modules/HMR from a dev server. Allow its
        // origins so the app loads in dev. Production builds use only 'self'.
        $viteDev = '';
        $viteWs = '';
        if (Vite::isRunningHot()) {
            // Only IPv4 / localhost origins: CSP does not accept bracketed
            // IPv6 (e.g. http://[::1]:5173) in source lists.
            $viteDev = ' http://localhost:5173 http://127.0.0.1:5173';
            $viteWs = ' ws://localhost:5173 ws://127.0.0.1:5173';
        }

        return implode('; ', [
            "default-src {$self}",
            "script-src {$self} {$inline} https://cdn.jsdelivr.net{$viteDev}",
            "style-src {$self} {$inline} {$bunny}{$viteDev}",
            "img-src {$self} {$data} https: {$blob}",
            "font-src {$self} {$data} {$bunny}",
            "connect-src {$self} https: ws: wss:{$viteWs}",
            "frame-src {$self}",
            "object-src 'none'",
            "base-uri {$self}",
            "form-action {$self}",
        ]);
    }
}
