<?php

use App\Http\Controllers\ClientController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified', 'organization'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::resource('clients', ClientController::class);
});

require __DIR__.'/settings.php';
