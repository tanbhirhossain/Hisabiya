<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->defineRateLimiters();
    }

    /**
     * Named rate limiters referenced by route middleware (throttle:auth, etc.).
     * Defined in a service provider so they're reliably registered for both web
     * requests and tests.
     */
    private function defineRateLimiters(): void
    {
        RateLimiter::for('auth', function () {
            return Limit::perMinute(10)->by(request()->ip());
        });

        RateLimiter::for('checkout', function () {
            return Limit::perMinute(20)->by(request()->ip());
        });

        RateLimiter::for('two-factor', function () {
            return Limit::perMinute(10)->by(request()->ip());
        });

        RateLimiter::for('api', function () {
            return Limit::perMinute(60)->by(request()->user()?->id ?: request()->ip());
        });
    }
}
