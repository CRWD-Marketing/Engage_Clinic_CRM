<?php

use Illuminate\Support\Facades\Route;


Route::middleware(['auth'])
    ->group(function () {

        Route::get('/calendar', function () {
            return view('calendar.index');
        })->name('calendar.index');

    });