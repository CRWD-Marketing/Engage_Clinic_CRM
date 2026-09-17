<?php

use App\Http\Controllers\User\RoleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'feature:roles_access'])
    ->prefix('roles-access')
    ->name('roles.')
    ->group(function () {

        Route::get('/', [RoleController::class, 'index'])->name('index');

        Route::post('/templates', [RoleController::class, 'storeTemplate'])->name('templates.store');
        Route::put('/templates/{template}', [RoleController::class, 'updateTemplate'])->name('templates.update');
        Route::delete('/templates/{template}', [RoleController::class, 'destroyTemplate'])->name('templates.destroy');

        Route::post('/users', [RoleController::class, 'storeUser'])->name('users.store');
        Route::put('/users/{user}', [RoleController::class, 'updateUser'])->name('users.update');
        Route::put('/users/{user}/template', [RoleController::class, 'updateUserTemplate'])->name('users.template');
        Route::put('/users/{user}/access', [RoleController::class, 'updateUserAccess'])->name('users.access');
        Route::put('/users/{user}/suspend', [RoleController::class, 'toggleSuspend'])->name('users.suspend');
        Route::delete('/users/{user}', [RoleController::class, 'destroyUser'])->name('users.destroy');

    });
