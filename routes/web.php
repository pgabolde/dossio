<?php

use App\Models\Client;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified', 'organization'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::get('clients/{client}', fn (Client $client) => $client->name)->name('clients.show');
});

require __DIR__.'/settings.php';
