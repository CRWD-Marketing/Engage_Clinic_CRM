<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Therapist\TherapistController;


Route::middleware(['auth', 'role:FULL_ADMIN,HR_STAFF,CLINICAL_SUPERVISOR,THERAPIST'])
    ->group(function () {

        Route::resource('therapist', TherapistController::class);

    });