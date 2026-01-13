<?php

use App\Livewire\VideoDownloader;
use Illuminate\Support\Facades\Route;

Route::get('/', VideoDownloader::class);

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
