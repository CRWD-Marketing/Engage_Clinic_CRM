<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Vendor\VendorController;
use App\Http\Controllers\Vendor\VendorRegistrationController;

/*
|--------------------------------------------------------------------------
| Admin Vendors Routes
|--------------------------------------------------------------------------
|
| URL:
| /admin/vendors
| /admin/vendors/{vendor}
|
| Route names:
| vendors.index / .count / .show / .update / .destroy / .document
|
*/

Route::middleware(['auth', 'feature:vendors'])
    ->prefix('admin')
    ->group(function () {

        Route::get('vendors', [VendorController::class, 'index'])->name('vendors.index');
        Route::get('vendors/count', [VendorController::class, 'count'])->name('vendors.count');
        Route::get('vendors/{vendor}', [VendorController::class, 'show'])->name('vendors.show');
        Route::patch('vendors/{vendor}', [VendorController::class, 'update'])->name('vendors.update');
        Route::delete('vendors/{vendor}', [VendorController::class, 'destroy'])->name('vendors.destroy');
        Route::get('vendors/{vendor}/documents/{document}', [VendorController::class, 'document'])->name('vendors.document');

    });

/*
|--------------------------------------------------------------------------
| Public Vendor Registration
|--------------------------------------------------------------------------
| Linked from the website footer. No CRM account needed - the POST carries
| a honeypot and is rate limited.
*/

Route::get('vendor-registration', [VendorRegistrationController::class, 'show'])->name('vendor-registration');

Route::post('vendor-registration', [VendorRegistrationController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('vendor-registration.store');
