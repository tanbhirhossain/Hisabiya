<?php

use Illuminate\Support\Facades\Route;
use Modules\CORE\Http\Controllers\ActivityLogController;
use Modules\CORE\Http\Controllers\Checkout\BackupController;
use Modules\CORE\Http\Controllers\Checkout\BillingController;
use Modules\CORE\Http\Controllers\Checkout\CheckoutController;
use Modules\CORE\Http\Controllers\Checkout\PaymentGatewayController;
use Modules\CORE\Http\Controllers\DashboardController;
use Modules\CORE\Http\Controllers\PermissionController;
use Modules\CORE\Http\Controllers\RoleController;
use Modules\CORE\Http\Controllers\SubscriptionController;
use Modules\CORE\Http\Controllers\TenantController;
use Modules\CORE\Http\Controllers\UserController;

// Public pricing + checkout (no auth required to browse).
// NOTE: the fixed-segment callback/manual routes MUST be registered before the
// parameterized /checkout/{plan} route so they don't get captured by it.
Route::middleware('web')->group(function (): void {
    Route::get('/pricing', [CheckoutController::class, 'pricing'])->name('pricing');
    Route::get('/terms', [\Modules\CORE\Http\Controllers\LegalController::class, 'terms'])->name('legal.terms');
    Route::get('/privacy', [\Modules\CORE\Http\Controllers\LegalController::class, 'privacy'])->name('legal.privacy');
    Route::get('/refund', [\Modules\CORE\Http\Controllers\LegalController::class, 'refund'])->name('legal.refund');
    Route::post('/checkout/process', [CheckoutController::class, 'process'])->name('checkout.process')->middleware('throttle:checkout');
    Route::post('/checkout/ipn', [CheckoutController::class, 'ipn'])->name('checkout.ipn');
    Route::get('/checkout/callback', [CheckoutController::class, 'callback'])->name('checkout.callback');
    Route::get('/checkout/simulate/{tranId}', [CheckoutController::class, 'simulate'])->name('checkout.simulate');
    Route::get('/checkout/manual/{provider}', [CheckoutController::class, 'manual'])->name('checkout.manual');
    Route::post('/checkout/manual/submit', [CheckoutController::class, 'manualSubmit'])->name('checkout.manual.submit');
    Route::get('/checkout/{plan}', [CheckoutController::class, 'checkout'])->name('checkout');
});

Route::middleware(['web', 'auth', 'verified'])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/modules', [DashboardController::class, 'modules'])->name('modules.index');
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::get('/billing/browse', [CheckoutController::class, 'browse'])->name('billing.browse');
    Route::get('/billing/{payment}/download', [BillingController::class, 'download'])->name('billing.download');
    Route::post('/billing/{payment}/pay', [BillingController::class, 'pay'])->name('billing.pay');

    Route::middleware('can:tenant.view')->group(function (): void {
        Route::get('admin/tenants', [TenantController::class, 'index'])->name('tenants.index');
        Route::get('admin/tenants/create', [TenantController::class, 'create'])->name('tenants.create')->middleware('can:tenant.create');
        Route::post('admin/tenants', [TenantController::class, 'store'])->name('tenants.store')->middleware('can:tenant.create');
        Route::get('admin/tenants/{tenant}/edit', [TenantController::class, 'edit'])->name('tenants.edit')->middleware('can:tenant.update');
        Route::put('admin/tenants/{tenant}', [TenantController::class, 'update'])->name('tenants.update')->middleware('can:tenant.update');
        Route::delete('admin/tenants/{tenant}', [TenantController::class, 'destroy'])->name('tenants.destroy')->middleware('can:tenant.delete');
    });

    Route::middleware('can:user.view')->group(function (): void {
        Route::get('admin/users', [UserController::class, 'index'])->name('users.index');
        Route::get('admin/users/create', [UserController::class, 'create'])->name('users.create')->middleware('can:user.create');
        Route::post('admin/users', [UserController::class, 'store'])->name('users.store')->middleware('can:user.create');
        Route::get('admin/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit')->middleware('can:user.update');
        Route::put('admin/users/{user}', [UserController::class, 'update'])->name('users.update')->middleware('can:user.update');
        Route::delete('admin/users/{user}', [UserController::class, 'destroy'])->name('users.destroy')->middleware('can:user.delete');
    });

    Route::middleware('can:role.view')->group(function (): void {
        Route::get('admin/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('admin/roles/create', [RoleController::class, 'create'])->name('roles.create')->middleware('can:role.create');
        Route::post('admin/roles', [RoleController::class, 'store'])->name('roles.store')->middleware('can:role.create');
        Route::get('admin/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit')->middleware('can:role.update');
        Route::put('admin/roles/{role}', [RoleController::class, 'update'])->name('roles.update')->middleware('can:role.update');
        Route::delete('admin/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy')->middleware('can:role.delete');
    });

    Route::middleware('can:permission.view')->group(function (): void {
        Route::get('admin/permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::get('admin/permissions/create', [PermissionController::class, 'create'])->name('permissions.create')->middleware('can:permission.create');
        Route::post('admin/permissions', [PermissionController::class, 'store'])->name('permissions.store')->middleware('can:permission.create');
        Route::get('admin/permissions/{permission}/edit', [PermissionController::class, 'edit'])->name('permissions.edit')->middleware('can:permission.update');
        Route::put('admin/permissions/{permission}', [PermissionController::class, 'update'])->name('permissions.update')->middleware('can:permission.update');
        Route::delete('admin/permissions/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy')->middleware('can:permission.delete');
    });

    Route::get('admin/activity-logs', [ActivityLogController::class, 'index'])
        ->middleware('can:activity-log.view')
        ->name('activity-logs.index');

    Route::middleware('can:permission.view')->group(function (): void {
        Route::get('admin/subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::get('admin/subscriptions/plans/create', [SubscriptionController::class, 'createPlan'])->name('subscriptions.plans.create');
        Route::post('admin/subscriptions/plans', [SubscriptionController::class, 'storePlan'])->name('subscriptions.plans.store');
        Route::get('admin/subscriptions/plans/{plan}/edit', [SubscriptionController::class, 'editPlan'])->name('subscriptions.plans.edit');
        Route::put('admin/subscriptions/plans/{plan}', [SubscriptionController::class, 'updatePlan'])->name('subscriptions.plans.update');
        Route::delete('admin/subscriptions/plans/{plan}', [SubscriptionController::class, 'destroyPlan'])->name('subscriptions.plans.destroy');
        Route::post('admin/subscriptions/assign', [SubscriptionController::class, 'assign'])->name('subscriptions.assign');
        Route::post('admin/subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
        Route::post('admin/subscriptions/{subscription}/downgrade', [SubscriptionController::class, 'downgrade'])->name('subscriptions.downgrade');
        Route::post('admin/payments/{payment}/approve', [SubscriptionController::class, 'approvePayment'])->name('subscriptions.payments.approve');
        Route::post('admin/payments/{payment}/reject', [SubscriptionController::class, 'rejectPayment'])->name('subscriptions.payments.reject');
        Route::post('admin/payments/{payment}/refund', [SubscriptionController::class, 'refundPayment'])->name('subscriptions.payments.refund');

        // Payment gateway settings.
        Route::get('admin/settings/payment-gateways', [PaymentGatewayController::class, 'index'])->name('settings.payment-gateways');
        Route::post('admin/settings/payment-gateways', [PaymentGatewayController::class, 'update'])->name('settings.payment-gateways.update');

        // Mail (SMTP) settings.
        Route::get('admin/settings/mail', [\Modules\CORE\Http\Controllers\Checkout\MailSettingsController::class, 'index'])->name('settings.mail');
        Route::post('admin/settings/mail', [\Modules\CORE\Http\Controllers\Checkout\MailSettingsController::class, 'update'])->name('settings.mail.update');
        Route::post('admin/settings/mail/test', [\Modules\CORE\Http\Controllers\Checkout\MailSettingsController::class, 'test'])->name('settings.mail.test');

        // Backup center.
        Route::get('admin/backups', [BackupController::class, 'index'])->name('backup.index');
        Route::post('admin/backups/all', [BackupController::class, 'backupAll'])->name('backup.all');
        Route::post('admin/backups/tenant', [BackupController::class, 'backupTenant'])->name('backup.tenant');
        Route::post('admin/backups/restore', [BackupController::class, 'restore'])->name('backup.restore');
        Route::post('admin/backups/restore-upload', [BackupController::class, 'restoreUpload'])->name('backup.restore-upload');
        Route::get('admin/backups/{file}/download', [BackupController::class, 'download'])->name('backup.download');
    });
});
