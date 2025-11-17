<?php

use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\LoginController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store'])->middleware('throttle:wallet-transfers');
});

Route::post('/login', LoginController::class)->name('api.login');
