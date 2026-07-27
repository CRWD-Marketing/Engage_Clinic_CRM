<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Lead\LeadController;


/*
|--------------------------------------------------------------------------
| Admin Lead Routes
|--------------------------------------------------------------------------
|
| URL:
| /admin/leads
|
| Route names:
| leads.index
| leads.create
| leads.store
| etc.
|
*/

Route::middleware(['auth'])
    ->prefix('admin')
    ->group(function () {

        Route::resource('leads', LeadController::class);


        Route::patch(
            'leads/{lead}/status',
            [LeadController::class, 'updateStatus']
        )->name('leads.update-status');

    });



/*
|--------------------------------------------------------------------------
| Public Lead API
|--------------------------------------------------------------------------
*/

Route::prefix('api')
    ->name('api.')
    ->group(function () {

        Route::post(
            'leads',
            [LeadController::class, 'apiStore']
        )->name('leads.store');

    });