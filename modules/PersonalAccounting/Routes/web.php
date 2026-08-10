<?php

use Illuminate\Support\Facades\Route;
use Modules\PersonalAccounting\Http\Controllers\AccountController;
use Modules\PersonalAccounting\Http\Controllers\BudgetController;
use Modules\PersonalAccounting\Http\Controllers\ContactController;
use Modules\PersonalAccounting\Http\Controllers\DashboardController;
use Modules\PersonalAccounting\Http\Controllers\GoalController;
use Modules\PersonalAccounting\Http\Controllers\LoanController;
use Modules\PersonalAccounting\Http\Controllers\ReportController;
use Modules\PersonalAccounting\Http\Controllers\TransactionController;

Route::middleware(['web', 'auth', 'verified', 'can:personal-accounting.view'])->prefix('personal')->name('personal.')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
    Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');
    Route::post('/transactions/bulk-delete', [TransactionController::class, 'bulkDestroy'])->name('transactions.bulk-destroy');

    Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
    Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');
    Route::get('/accounts/{account}', [AccountController::class, 'show'])->name('accounts.show');
    Route::delete('/accounts/{account}', [AccountController::class, 'destroy'])->name('accounts.destroy');

    Route::get('/budgets', [BudgetController::class, 'index'])->name('budgets.index');
    Route::post('/budgets', [BudgetController::class, 'store'])->name('budgets.store');
    Route::delete('/budgets/{budget}', [BudgetController::class, 'destroy'])->name('budgets.destroy');

    Route::get('/goals', [GoalController::class, 'index'])->name('goals.index');
    Route::post('/goals', [GoalController::class, 'store'])->name('goals.store');
    Route::post('/goals/{goal}/contribute', [GoalController::class, 'contribute'])->name('goals.contribute');
    Route::post('/goals/{goal}/withdraw', [GoalController::class, 'withdraw'])->name('goals.withdraw');
    Route::delete('/goals/{goal}', [GoalController::class, 'destroy'])->name('goals.destroy');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    Route::middleware('can:personal-accounting.loans.view')->group(function (): void {
        Route::get('/loans', [LoanController::class, 'index'])->name('loans.index');
        Route::post('/loans', [LoanController::class, 'store'])->middleware('can:personal-accounting.loans.manage')->name('loans.store');
        Route::post('/loans/{loan}/pay', [LoanController::class, 'pay'])->middleware('can:personal-accounting.loans.manage')->name('loans.pay');
        Route::delete('/loans/{loan}', [LoanController::class, 'destroy'])->middleware('can:personal-accounting.loans.manage')->name('loans.destroy');
    });

    Route::middleware('can:personal-accounting.contacts.view')->group(function (): void {
        Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');
        Route::post('/contacts', [ContactController::class, 'store'])->middleware('can:personal-accounting.contacts.manage')->name('contacts.store');
        Route::delete('/contacts/{contact}', [ContactController::class, 'destroy'])->middleware('can:personal-accounting.contacts.manage')->name('contacts.destroy');
    });
});
