<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\BillingController;


Route::middleware(['auth'])
    ->group(function () {

        Route::get('/billing', [BillingController::class, 'index'])
            ->name('billing.index');

    });