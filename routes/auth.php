<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\CreateAccount;
use App\Http\Controllers\Auth\ForgotPasswordController;

/*
|--------------------------------------------------------------------------
| Guest Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {

    // Authentication
    Route::get('/login', [LoginController::class, 'showLoginForm'])
        ->name('login');

    Route::post('/login', [LoginController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('login.submit');

    // Password reset
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])
        ->name('password.request');

    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])
        ->middleware('throttle:5,1')
        ->name('password.email');

    Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'showResetForm'])
        ->name('password.reset');

    Route::post('/reset-password', [ForgotPasswordController::class, 'reset'])
        ->middleware('throttle:5,1')
        ->name('password.update');

});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    // Authentication
    Route::post('/logout', [LoginController::class, 'logout'])
        ->name('logout');

    // User Management
    Route::get('/users', [CreateAccount::class, 'index'])->name('users.index');
    Route::get('/users/create', [CreateAccount::class, 'create'])->name('users.create');
    Route::post('/users', [CreateAccount::class, 'store'])->name('users.store');
    Route::get('/users/{user}', [CreateAccount::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [CreateAccount::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [CreateAccount::class, 'update'])->name('users.update');
    Route::patch('/users/{user}', [CreateAccount::class, 'update']);
    Route::delete('/users/{user}', [CreateAccount::class, 'destroy'])->name('users.destroy');

});