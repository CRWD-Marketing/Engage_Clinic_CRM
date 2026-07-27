<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\DashboardController;


Route::middleware(['auth'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Universal Dashboard
    |--------------------------------------------------------------------------
    | Accessible by all authenticated users.
    | DashboardController handles role-based display:
    | FULL_ADMIN, HR, SALES, CLINICAL, FINANCE, OTHER_STAFF
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

});