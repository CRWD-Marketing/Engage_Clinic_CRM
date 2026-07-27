<?php

use Illuminate\Support\Facades\Route;


Route::middleware(['auth'])
    ->group(function () {

        Route::get('/whatsapp', function () {
            return view('whatsapp.index');
        })->name('whatsapp.index');

    });