<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetSecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Global security headers + Inertia helpers on every web response.
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            SetSecurityHeaders::class,
        ]);

        // The SSLCommerz IPN webhook is a server-to-server POST (not from the
        // browser), so it must be exempt from CSRF protection. Requests are
        // authenticated via the gateway signature instead.
        $middleware->validateCsrfTokens(except: [
            'checkout/ipn',
        ]);

        // Guard sensitive/guest endpoints against brute-force and abuse.
        $middleware->throttleApi('api');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
