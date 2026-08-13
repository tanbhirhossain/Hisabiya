<?php

namespace Modules\PersonalAccounting\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Models\User;
use Modules\PersonalAccounting\Interfaces\PersonalAccountRepositoryInterface;
use Modules\PersonalAccounting\Interfaces\PersonalBudgetRepositoryInterface;
use Modules\PersonalAccounting\Interfaces\PersonalTransactionRepositoryInterface;
use Modules\PersonalAccounting\Jobs\RecurringTransactionJob;
use Modules\PersonalAccounting\Models\PersonalLoan;
use Modules\PersonalAccounting\Notifications\LoanPaymentDueNotification;
use Modules\PersonalAccounting\Notifications\MonthlyReportNotification;
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

            // Recalculate rollover amounts for rollover-enabled budgets each month.
            $schedule->call(function (): void {
                \Modules\PersonalAccounting\Models\PersonalBudget::query()
                    ->where('rollover_enabled', true)
                    ->each(function (\Modules\PersonalAccounting\Models\PersonalBudget $budget): void {
                        app(\Modules\PersonalAccounting\Services\PersonalBudgetService::class)->calculateRollover($budget);
                    });
            })->monthlyOn(1, '00:30');

            // Notify users whose loan payment is due in 3 days.
            $schedule->call(function (): void {
                $dueDate = now()->addDays(3)->toDateString();

                PersonalLoan::query()
                    ->where('status', 'active')
                    ->whereNotNull('next_payment_date')
                    ->where('next_payment_date', $dueDate)
                    ->each(function (PersonalLoan $loan): void {
                        if ($loan->user) {
                            $loan->user->notify(new LoanPaymentDueNotification(
                                $loan->name,
                                (float) $loan->payment_amount,
                                $loan->next_payment_date->toDateString(),
                            ));
                        }
                    });
            })->daily();

            // Send the monthly report on the 1st of each month at 08:00.
            $schedule->call(function (): void {
                $monthLabel = now()->subMonth()->format('F Y');
                $from = now()->subMonth()->startOfMonth();
                $to = now()->subMonth()->endOfMonth();

                User::query()
                    ->whereNotNull('tenant_id')
                    ->where('is_active', true)
                    ->where('personal_report_email_enabled', true)
                    ->each(function (User $user) use ($monthLabel, $from, $to): void {
                        $income = \Modules\PersonalAccounting\Models\PersonalTransaction::query()
                            ->where('tenant_id', $user->tenant_id)
                            ->where('user_id', $user->id)
                            ->where('type', 'income')
                            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
                            ->sum('amount');

                        $expense = \Modules\PersonalAccounting\Models\PersonalTransaction::query()
                            ->where('tenant_id', $user->tenant_id)
                            ->where('user_id', $user->id)
                            ->where('type', 'expense')
                            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
                            ->sum('amount');

                        $user->notify(new MonthlyReportNotification([
                            'income' => (float) $income,
                            'expense' => (float) $expense,
                            'net' => round((float) $income - (float) $expense, 2),
                        ], $monthLabel));
                    });
            })->monthlyOn(1, '08:00');
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
