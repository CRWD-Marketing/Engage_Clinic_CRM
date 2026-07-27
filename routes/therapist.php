<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\TherapistController;


Route::middleware(['auth'])
    ->group(function () {

        Route::resource('therapist', TherapistController::class);

    });