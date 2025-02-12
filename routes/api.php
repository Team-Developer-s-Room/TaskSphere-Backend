<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\UpdatePasswordController;
use App\Http\Controllers\CollaboratorController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskUserController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {

    Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::post('reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');

    Route::name('auth.')->group(function () {

        Route::post('/register', [RegisterController::class, 'register'])->name('register');
        Route::post('/login', [LoginController::class, 'login'])->name('login');
        Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth:sanctum');
        Route::post('/update-password', [UpdatePasswordController::class, 'updatePassword'])
        ->name('update-password')->middleware('auth:sanctum');
    });

    Route::middleware('auth:sanctum')->group(function () {

        Route::apiResource('users', UserController::class)->except(['destroy', 'store']);
        
        Route::apiResource('projects', ProjectController::class);

        Route::get('project/{project}/tasks', [TaskController::class, 'index']);
        Route::apiResource('tasks', TaskController::class)->except('index');
        
        Route::get('projects/{project}/collaborators', [CollaboratorController::class, 'index']);
        Route::post('projects/{project}/collaborators', [CollaboratorController::class, 'store']);
        Route::delete('projects/{project}/collaborators/{user}', [CollaboratorController::class, 'destroy']);
        
        Route::get('tasks/{task}/task-users', [TaskUserController::class, 'index']);
        Route::post('tasks/{task}/task-users', [TaskUserController::class, 'store']);
        Route::delete('tasks/{task}/task-users/{user}', [TaskUserController::class, 'destroy']);
    });

    
});
