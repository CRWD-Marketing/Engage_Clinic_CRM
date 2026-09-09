<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Patient\PatientController;
use App\Http\Controllers\Patient\PatientDocumentController;


Route::middleware(['auth', 'feature:patients'])
    ->group(function () {

        Route::get('patient', [PatientController::class, 'index'])->name('patient.index');
        Route::get('patient/{patient}', [PatientController::class, 'show'])->name('patient.show');
        Route::post('patient', [PatientController::class, 'store'])->name('patient.store');
        Route::put('patient/{patient}', [PatientController::class, 'update'])->name('patient.update');
        Route::patch('patient/{patient}', [PatientController::class, 'update']);

        Route::post('patient/{patient}/notes', [PatientController::class, 'addNote'])
            ->name('patient.notes.store');

        Route::post('patient/{patient}/goals/today', [PatientController::class, 'updateGoalsForToday'])
            ->name('patient.goals.today');

        Route::post('patient/{patient}/authorizations', [PatientController::class, 'storeAuthorization'])
            ->name('patient.authorizations.store');
        Route::put('patient/{patient}/authorizations/{authorization}', [PatientController::class, 'updateAuthorization'])
            ->name('patient.authorizations.update');
        Route::delete('patient/{patient}/authorizations/{authorization}', [PatientController::class, 'destroyAuthorization'])
            ->name('patient.authorizations.destroy');

        Route::post('patient/{patient}/documents', [PatientDocumentController::class, 'store'])
            ->name('patient.documents.store');
        Route::get('patient/{patient}/documents/{document}', [PatientDocumentController::class, 'download'])
            ->name('patient.documents.download');
        Route::delete('patient/{patient}/documents/{document}', [PatientDocumentController::class, 'destroy'])
            ->name('patient.documents.destroy');

    });
