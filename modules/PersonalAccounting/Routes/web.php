<?php

use Illuminate\Support\Facades\Route;
use Modules\PersonalAccounting\Http\Controllers\AccountController;
use Modules\PersonalAccounting\Http\Controllers\BudgetController;
use Modules\PersonalAccounting\Http\Controllers\ContactController;
use Modules\PersonalAccounting\Http\Controllers\DashboardController;
use Modules\PersonalAccounting\Http\Controllers\GoalController;
use Modules\PersonalAccounting\Http\Controllers\LoanController;
use Modules\PersonalAccounting\Http\Controllers\ModuleUserController;
use Modules\PersonalAccounting\Http\Controllers\PersonalBackupController;
use Modules\PersonalAccounting\Http\Controllers\NotificationController;
use Modules\PersonalAccounting\Http\Controllers\PersonalRecurringController;
use Modules\PersonalAccounting\Http\Controllers\ReportController;
use Modules\PersonalAccounting\Http\Controllers\TransactionController;
use Modules\PersonalAccounting\Http\Controllers\TransactionImportController;

Route::middleware(['web', 'auth', 'verified', 'can:personal-accounting.view'])->prefix('personal')->name('personal.')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
    Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');
    Route::post('/transactions/bulk-delete', [TransactionController::class, 'bulkDestroy'])->name('transactions.bulk-destroy');
    Route::post('/transactions/bulk-update', [TransactionController::class, 'bulkUpdate'])->name('transactions.bulk-update');

    Route::middleware('can:personal-accounting.transactions.import')->group(function (): void {
        Route::get('/transactions/import', [TransactionImportController::class, 'showImport'])->name('transactions.import');
        Route::post('/transactions/import/upload', [TransactionImportController::class, 'upload'])->name('transactions.import.upload');
        Route::post('/transactions/import/confirm', [TransactionImportController::class, 'confirm'])->name('transactions.import.confirm');
    });

    Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
    Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');
    Route::get('/accounts/{account}', [AccountController::class, 'show'])->name('accounts.show');
    Route::post('/accounts/{account}/archive', [AccountController::class, 'archive'])->name('accounts.archive');
    Route::get('/accounts/{account}/balance-history', [AccountController::class, 'balanceHistory'])->name('accounts.balance-history');
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
    Route::post('/reports/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.export-pdf');
    Route::post('/reports/email-settings', [ReportController::class, 'updateEmailSettings'])->name('reports.email-settings');

    // Recurring transactions management.
    Route::middleware('can:personal-accounting.transactions.view')->group(function (): void {
        Route::get('/recurring', [PersonalRecurringController::class, 'index'])->name('recurring.index');
        Route::get('/recurring/{recurring}/logs', [PersonalRecurringController::class, 'logs'])->name('recurring.logs');
        Route::post('/recurring/{recurring}/toggle', [PersonalRecurringController::class, 'toggle'])->name('recurring.toggle');
    });

    Route::middleware('can:personal-accounting.loans.view')->group(function (): void {
        Route::get('/loans', [LoanController::class, 'index'])->name('loans.index');
        Route::post('/loans', [LoanController::class, 'store'])->middleware('can:personal-accounting.loans.manage')->name('loans.store');
        Route::post('/loans/{loan}/pay', [LoanController::class, 'pay'])->middleware('can:personal-accounting.loans.manage')->name('loans.pay');
        Route::get('/loans/{loan}/statement', [LoanController::class, 'statement'])->name('loans.statement');
        Route::delete('/loans/{loan}', [LoanController::class, 'destroy'])->middleware('can:personal-accounting.loans.manage')->name('loans.destroy');
    });

    Route::middleware('can:personal-accounting.contacts.view')->group(function (): void {
        Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');
        Route::post('/contacts', [ContactController::class, 'store'])->middleware('can:personal-accounting.contacts.manage')->name('contacts.store');
        Route::delete('/contacts/{contact}', [ContactController::class, 'destroy'])->middleware('can:personal-accounting.contacts.manage')->name('contacts.destroy');
    });

    // Module user panel (Owner, gated by the ACL permission).
    Route::middleware('can:personal-accounting.acl')->group(function (): void {
        Route::get('/settings/users', [ModuleUserController::class, 'index'])->name('settings.users.index');
        Route::post('/settings/users', [ModuleUserController::class, 'store'])->name('settings.users.store');
        Route::put('/settings/users/{membership}', [ModuleUserController::class, 'update'])->name('settings.users.update');
        Route::delete('/settings/users/{membership}', [ModuleUserController::class, 'destroy'])->name('settings.users.destroy');
    });

    // Tenant user's own data backup (PRO-gated).
    Route::middleware('can:personal-accounting.backup')->group(function (): void {
        Route::get('/settings/backup', [PersonalBackupController::class, 'index'])->name('settings.backup.index');
        Route::post('/settings/backup', [PersonalBackupController::class, 'create'])->name('settings.backup.create');
        Route::post('/settings/backup/restore', [PersonalBackupController::class, 'restore'])->name('settings.backup.restore');
        Route::post('/settings/backup/restore-upload', [PersonalBackupController::class, 'restoreUpload'])->name('settings.backup.restore-upload');
        Route::get('/settings/backup/{file}/download', [PersonalBackupController::class, 'download'])->name('settings.backup.download');
    });

    // Notifications (DB + mail).
    Route::middleware('can:personal-accounting.view')->group(function (): void {
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
        Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    });
});
