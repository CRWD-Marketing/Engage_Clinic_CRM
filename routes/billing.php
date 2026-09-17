<?php

use App\Http\Controllers\Billing\BillingController;
use App\Http\Controllers\Billing\BulkRunController;
use App\Http\Controllers\Billing\ClaimController;
use App\Http\Controllers\Billing\InvoiceController;
use App\Http\Controllers\Billing\PreAuthController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'feature:billing'])
    ->prefix('billing')
    ->name('billing.')
    ->group(function () {

        Route::get('/', [BillingController::class, 'index'])->name('index');

        Route::get('/patients/{patient}/ledger', [InvoiceController::class, 'ledger'])->name('ledger');
        Route::post('/patients/{patient}/top-up', [InvoiceController::class, 'topUp'])->name('topup');
        Route::get('/patients/{patient}/statement', [InvoiceController::class, 'statement'])->name('statements.show');

        Route::post('/invoices/preview', [InvoiceController::class, 'preview'])->name('invoices.preview');
        Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::get('/invoices/{invoice}/json', [InvoiceController::class, 'data'])->name('invoices.data');
        Route::post('/invoices/{invoice}/payments', [InvoiceController::class, 'storePayment'])->name('invoices.payments');
        Route::post('/invoices/{invoice}/credit', [InvoiceController::class, 'storeCredit'])->name('invoices.credit');
        Route::post('/invoices/{invoice}/void', [InvoiceController::class, 'void'])->name('invoices.void');
        Route::post('/invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');

        Route::patch('/claims/{claim}', [ClaimController::class, 'update'])->name('claims.update');

        Route::post('/pre-authorizations', [PreAuthController::class, 'store'])->name('preauths.store');
        Route::patch('/pre-authorizations/{preAuthorization}', [PreAuthController::class, 'update'])->name('preauths.update');

        Route::get('/bulk-run', [BulkRunController::class, 'preview'])->name('bulk.preview');
        Route::post('/bulk-run', [BulkRunController::class, 'issue'])->name('bulk.issue');

    });
