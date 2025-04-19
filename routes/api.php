<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\UpdatePasswordController;
use App\Http\Controllers\CollaboratorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskUserController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {

    Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::post('reset-password', [ResetPasswordController::class, 'reset'])->name('password.reset');

    Route::name('auth.')->group(function () {

        Route::post('/register', [RegisterController::class, 'register'])->name('register');
        Route::post('/login', [LoginController::class, 'login'])->name('login');
        Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth:sanctum')->name('logout');
        Route::post('/update-password', [UpdatePasswordController::class, 'updatePassword'])
            ->name('update.password')->middleware('auth:sanctum');
    });

    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

        Route::apiResource('users', UserController::class)->except(['destroy', 'store']);
        Route::get('users/{user}/notifications', [UserController::class, 'notifications'])->name('users.notifications');
        Route::post('users/{user}/notifications/{notification}/mark-as-read', [UserController::class, 'markAsRead'])->name('users.notifications.markAsRead');

        Route::apiResource('projects', ProjectController::class);

        Route::get('project/{project}/tasks', [TaskController::class, 'index'])->name('tasks.index');
        Route::apiResource('tasks', TaskController::class)->except('index');
        Route::match(['put', 'patch'], '{task}/mark-completed', [TaskController::class, 'markAsCompleted']);
        Route::match(['put', 'patch'], '{task}/mark-pending', [TaskController::class, 'markAsPending']);

        Route::prefix('projects')->name('collaborators.')->group(function () {

            Route::get('{project}/collaborators', [CollaboratorController::class, 'index'])->name('index');
            Route::post('{project}/collaborators/invite', [CollaboratorController::class, 'invite'])->name('invite');
            Route::post('{project}/collaborators/{user}', [CollaboratorController::class, 'store'])->name('store');
            Route::delete('{project}/collaborators/{user}', [CollaboratorController::class, 'destroy'])->name('destroy');
            Route::match(['put', 'patch'], '{project}/mark-as-completed', [ProjectController::class, 'markAsCompleted'])->name('markAsCompleted');
        });

        Route::prefix('tasks')->name('task-users.')->group(function () {

            Route::get('{task}/task-users', [TaskUserController::class, 'index'])->name('index');
            Route::post('{task}/task-users', [TaskUserController::class, 'store'])->name('store');
            Route::delete('{task}/task-users/{user}', [TaskUserController::class, 'destroy'])->name('destroy');
        });
    });

});
