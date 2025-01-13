<?php

use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {

    Route::name('auth.')->group(function () {

        Route::post('/register', [RegisterController::class, 'register'])->name('register');
    });

    
});
