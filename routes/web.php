<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Admin dashboard is owned by the CORE module (modules/CORE/Routes/web.php).

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
