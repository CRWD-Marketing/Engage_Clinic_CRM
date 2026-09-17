<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Contact\ContactController;

/*
|--------------------------------------------------------------------------
| Admin Contact Us Routes
|--------------------------------------------------------------------------
|
| URL:
| /admin/contacts
|
| Route names:
| contacts.index
| contacts.show
| etc.
|
*/

Route::middleware(['auth', 'feature:contacts'])
    ->prefix('admin')
    ->group(function () {

        Route::get('contacts', [ContactController::class, 'index'])->name('contacts.index');
        Route::get('contacts/count', [ContactController::class, 'getContactCount'])->name('contacts.count');
        Route::patch('contacts/{contact}', [ContactController::class, 'update'])->name('contacts.update');
        Route::patch('contacts/{contact}/status', [ContactController::class, 'updateStatus'])->name('contacts.update-status');
        Route::post('contacts/{contact}/send-email', [ContactController::class, 'sendStatusEmail'])->name('contacts.send-email');
        Route::post('contacts/{contact}/convert-to-lead', [ContactController::class, 'convertToLead'])->name('contacts.convert-to-lead');
        Route::delete('contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');

    });

/*
|--------------------------------------------------------------------------
| Public Contact Us API
|--------------------------------------------------------------------------
*/

Route::prefix('api')
    ->name('api.')
    ->group(function () {

        Route::post('contacts', [ContactController::class, 'apiStore'])->name('contacts.store');

    });
