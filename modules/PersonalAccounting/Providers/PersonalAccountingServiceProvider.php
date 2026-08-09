<?php

namespace Modules\PersonalAccounting\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\PersonalAccounting\Interfaces\PersonalAccountRepositoryInterface;
use Modules\PersonalAccounting\Interfaces\PersonalBudgetRepositoryInterface;
use Modules\PersonalAccounting\Interfaces\PersonalTransactionRepositoryInterface;
use Modules\PersonalAccounting\Jobs\RecurringTransactionJob;
use Modules\PersonalAccounting\Repositories\PersonalAccountRepository;
use Modules\PersonalAccounting\Repositories\PersonalBudgetRepository;
use Modules\PersonalAccounting\Repositories\PersonalTransactionRepository;

class PersonalAccountingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PersonalTransactionRepositoryInterface::class, PersonalTransactionRepository::class);
        $this->app->bind(PersonalAccountRepositoryInterface::class, PersonalAccountRepository::class);
        $this->app->bind(PersonalBudgetRepositoryInterface::class, PersonalBudgetRepository::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'personal-accounting');
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        // Run due recurring transactions once per day.
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->job(new RecurringTransactionJob)->daily()->withoutOverlapping();
        });

        // A super-admin can always view personal accounting resources.
        Gate::before(function ($user, $ability) {
            if ($user && method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
                return true;
            }

            return null;
        });
    }
}
