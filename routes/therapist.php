<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Therapist\TherapistController;


Route::middleware(['auth', 'feature:therapists'])
    ->group(function () {

        Route::resource('therapist', TherapistController::class);

    });