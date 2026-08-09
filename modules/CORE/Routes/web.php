<?php

use Illuminate\Support\Facades\Route;
use Modules\CORE\Http\Controllers\ActivityLogController;
use Modules\CORE\Http\Controllers\DashboardController;
use Modules\CORE\Http\Controllers\PermissionController;
use Modules\CORE\Http\Controllers\RoleController;
use Modules\CORE\Http\Controllers\TenantController;
use Modules\CORE\Http\Controllers\UserController;

Route::middleware(['web', 'auth', 'verified'])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

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
});
