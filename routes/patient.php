<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Patient\PatientController;


Route::middleware(['auth', 'feature:patients'])
    ->group(function () {

        Route::get('patient', [PatientController::class, 'index'])->name('patient.index');
        Route::post('patient', [PatientController::class, 'store'])->name('patient.store');
        Route::put('patient/{patient}', [PatientController::class, 'update'])->name('patient.update');
        Route::patch('patient/{patient}', [PatientController::class, 'update']);

        Route::post('patient/{patient}/notes', [PatientController::class, 'addNote'])
            ->name('patient.notes.store');

    });
