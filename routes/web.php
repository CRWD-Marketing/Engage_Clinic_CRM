<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');

/*
|--------------------------------------------------------------------------
| Feature Routes
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';
require __DIR__.'/dashboard.php';
require __DIR__.'/lead.php';
require __DIR__.'/patient.php';
require __DIR__.'/therapist.php';
require __DIR__.'/billing.php';
require __DIR__.'/user.php';
require __DIR__.'/report.php';
require __DIR__.'/settings.php';