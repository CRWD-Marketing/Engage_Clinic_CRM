<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Package\PackageController;

Route::middleware(['auth', 'feature:packages'])
    ->prefix('packages')
    ->name('packages.')
    ->group(function () {

        Route::get('/', [PackageController::class, 'index'])->name('index');
        Route::post('/', [PackageController::class, 'store'])->name('store');
        Route::put('{package}', [PackageController::class, 'update'])->name('update');
        Route::delete('{package}', [PackageController::class, 'destroy'])->name('destroy');

    });
