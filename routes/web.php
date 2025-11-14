<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'app')->name('spa');

// Catch-all for SPA routes, but keep /api/* for the API
Route::view('/{any}', 'app')
    ->where('any', '^(?!api).*$')
    ->name('spa.fallback');
