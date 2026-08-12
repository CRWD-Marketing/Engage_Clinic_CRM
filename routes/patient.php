<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Patient\PatientController;


Route::middleware(['auth'])
    ->group(function () {

        Route::resource('patient', PatientController::class);

    });