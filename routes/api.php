<?php

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {

    Route::name('auth.')->group(function () {

        Route::post('/register', [RegisterController::class, 'register'])->name('register');
    });

    Route::middleware('auth:sanctum')->group(function () {

        Route::apiResource('users', UserController::class)->only('update');
    });

    
});
