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

        Route::post(
            'leads/{lead}/notes',
            [LeadController::class, 'addNote']
        )->name('leads.notes.store');

        Route::post(
            'leads/{lead}/convert-to-patient',
            [LeadController::class, 'convertToPatient']
        )->name('leads.convert-to-patient');

        // Add route for getting lead count (for real-time badge updates)
        Route::get(
            'leads/count',
            [LeadController::class, 'getLeadCount']
        )->name('leads.count');

        // Add route for kanban data
        Route::get(
            'leads/kanban',
            [LeadController::class, 'getKanbanData']
        )->name('leads.kanban');

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