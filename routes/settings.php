<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Settings\SettingsController;

Route::middleware(['auth', 'feature:settings'])
    ->prefix('settings')
    ->name('settings.')
    ->group(function () {

        Route::get('/', [SettingsController::class, 'index'])->name('index');

        Route::post('services', [SettingsController::class, 'storeService'])->name('services.store');
        Route::put('services/{service}', [SettingsController::class, 'updateService'])->name('services.update');
        Route::patch('services/{service}/toggle', [SettingsController::class, 'toggleService'])->name('services.toggle');
        Route::delete('services/{service}', [SettingsController::class, 'destroyService'])->name('services.destroy');

        Route::post('locations', [SettingsController::class, 'storeLocation'])->name('locations.store');
        Route::put('locations/{location}', [SettingsController::class, 'updateLocation'])->name('locations.update');
        Route::patch('locations/{location}/toggle', [SettingsController::class, 'toggleLocation'])->name('locations.toggle');
        Route::delete('locations/{location}', [SettingsController::class, 'destroyLocation'])->name('locations.destroy');

        Route::post('insurances', [SettingsController::class, 'storeInsurance'])->name('insurances.store');
        Route::put('insurances/{insurance}', [SettingsController::class, 'updateInsurance'])->name('insurances.update');
        Route::patch('insurances/{insurance}/toggle', [SettingsController::class, 'toggleInsurance'])->name('insurances.toggle');
        Route::delete('insurances/{insurance}', [SettingsController::class, 'destroyInsurance'])->name('insurances.destroy');

    });
