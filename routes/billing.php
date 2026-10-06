<?php

use App\Http\Controllers\Billing\BillingController;
use App\Http\Controllers\Billing\BulkRunController;
use App\Http\Controllers\Billing\ClaimController;
use App\Http\Controllers\Billing\InvoiceController;
use App\Http\Controllers\Billing\PreAuthController;
use App\Http\Controllers\Billing\QuotationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'feature:billing'])
    ->prefix('billing')
    ->name('billing.')
    ->group(function () {

        Route::get('/', [BillingController::class, 'index'])->name('index');

        Route::get('/patients/{patient}/ledger', [InvoiceController::class, 'ledger'])->name('ledger');
        Route::post('/patients/{patient}/top-up', [InvoiceController::class, 'topUp'])->name('topup');
        Route::get('/patients/{patient}/statement', [InvoiceController::class, 'statement'])->name('statements.show');
        Route::get('/patients/{patient}/statement/pdf', [InvoiceController::class, 'statementPdf'])->name('statements.pdf');

        Route::post('/invoices/preview', [InvoiceController::class, 'preview'])->name('invoices.preview');
        Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::get('/invoices/{invoice}/json', [InvoiceController::class, 'data'])->name('invoices.data');
        Route::post('/invoices/{invoice}/payments', [InvoiceController::class, 'storePayment'])->name('invoices.payments');
        Route::post('/invoices/{invoice}/credit', [InvoiceController::class, 'storeCredit'])->name('invoices.credit');
        Route::post('/invoices/{invoice}/void', [InvoiceController::class, 'void'])->name('invoices.void');
        Route::post('/invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
        Route::post('/invoices/{invoice}/submit', [InvoiceController::class, 'submit'])->name('invoices.submit');
        Route::post('/invoices/{invoice}/approve', [InvoiceController::class, 'approve'])->name('invoices.approve');
        Route::post('/invoices/{invoice}/return', [InvoiceController::class, 'returnForCorrection'])->name('invoices.return');
        Route::post('/invoices/{invoice}/finalize', [InvoiceController::class, 'finalize'])->name('invoices.finalize');
        Route::post('/invoices/{invoice}/dispatches', [InvoiceController::class, 'storeDispatch'])->name('invoices.dispatches');
        Route::post('/invoices/{invoice}/dispatches/{dispatch}/confirm', [InvoiceController::class, 'confirmReceipt'])->name('invoices.dispatches.confirm');
        Route::get('/invoices/{invoice}/history', [InvoiceController::class, 'history'])->name('invoices.history');

        Route::get('/claims/{claim}', [ClaimController::class, 'show'])->name('claims.show');

        Route::patch('/claims/{claim}', [ClaimController::class, 'update'])->name('claims.update');

        Route::post('/pre-authorizations', [PreAuthController::class, 'store'])->middleware('throttle:10,1')->name('preauths.store');
        Route::patch('/pre-authorizations/{preAuthorization}', [PreAuthController::class, 'update'])->name('preauths.update');

        Route::post('/quotations', [QuotationController::class, 'store'])->middleware('throttle:10,1')->name('quotations.store');
        Route::get('/quotations/{quotation}', [QuotationController::class, 'show'])->name('quotations.show');
        Route::get('/quotations/{quotation}/history', [QuotationController::class, 'history'])->name('quotations.history');
        Route::post('/quotations/{quotation}/send', [QuotationController::class, 'send'])->name('quotations.send');
        Route::post('/quotations/{quotation}/confirm', [QuotationController::class, 'confirm'])->name('quotations.confirm');
        Route::post('/quotations/{quotation}/clear', [QuotationController::class, 'clear'])->name('quotations.clear');
        Route::post('/quotations/{quotation}/cancel', [QuotationController::class, 'cancel'])->name('quotations.cancel');

        Route::get('/bulk-run', [BulkRunController::class, 'preview'])->name('bulk.preview');
        Route::post('/bulk-run', [BulkRunController::class, 'issue'])->name('bulk.issue');

    });
