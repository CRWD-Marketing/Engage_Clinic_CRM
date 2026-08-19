<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Billing\BillingController;
use App\Http\Controllers\Billing\InvoiceController;


Route::middleware(['auth'])
    ->group(function () {

        Route::get('/billing', [BillingController::class, 'index'])
            ->name('billing.index');

        Route::post('/billing/invoices', [InvoiceController::class, 'store'])
            ->name('billing.invoices.store');

        Route::get('/billing/invoices/{invoice}', [InvoiceController::class, 'show'])
            ->name('billing.invoices.show');

        Route::patch('/billing/invoices/{invoice}', [InvoiceController::class, 'update'])
            ->name('billing.invoices.update');

    });