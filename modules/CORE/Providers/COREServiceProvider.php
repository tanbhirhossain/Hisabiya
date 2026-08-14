<?php

namespace Modules\CORE\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class COREServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'core');
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');

        if (file_exists(__DIR__.'/../Routes/api.php')) {
            $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        }

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        // A user holding the super-admin role bypasses every permission gate.
        Gate::before(function ($user, $ability) {
            if ($user && method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
                return true;
            }

            return null;
        });

        // Apply database-configured SMTP settings onto Laravel's mail config so
        // transactional email is actually delivered (not just logged). Applied
        // for both web and console (scheduler) so jobs also send. The try/catch
        // lets migrations/CLI run before the settings table exists.
        try {
            app(\Modules\CORE\Services\MailSettingsService::class)->applyConfig();
        } catch (\Throwable $e) {
            // settings table not ready yet — ignore.
        }
    }
}
