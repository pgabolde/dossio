<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\DocumentController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified', 'organization'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::resource('clients', ClientController::class);
    Route::resource('clients.documents', DocumentController::class)->only(['store']);
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
});

require __DIR__.'/settings.php';
