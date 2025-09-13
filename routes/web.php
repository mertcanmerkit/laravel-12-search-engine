<?php

use Illuminate\Support\Facades\Route;

use App\Livewire\ContentSearchTable;
use App\Http\Controllers\DocsController;

Route::middleware(['auth'])->group(function () {
    Route::get('/', ContentSearchTable::class)->name('home');
});

Route::get('/docs', DocsController::class)->name('docs');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');



require __DIR__ . '/auth.php';
