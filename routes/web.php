<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Career\JobPostingController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/services', function () {
    return view('landing_page.services');
})->name('services');

Route::get('/about-us', function () {
    return view('landing_page.about_us');
})->name('about-us');

Route::get('/careers', [JobPostingController::class, 'publicIndex'])->name('careers');

Route::get('/blog', function () {
    return view('landing_page.blog');
})->name('blog');

Route::get('/contact', function () {
    return view('landing_page.contact');
})->name('contact');

Route::get('/privacy-policy', function () {
    return view('landing_page.privacy_policy');
})->name('privacy-policy');

/*
|--------------------------------------------------------------------------
| CSRF token refresh
|--------------------------------------------------------------------------
| Returns the current session's CSRF token so a page left open past
| SESSION_LIFETIME can recover instead of failing the booking submit with a
| 419. Must stay in web.php: it needs the session middleware to read the
| session's own token.
*/
Route::get('/csrf-token', function () {
    return response()->json(['token' => csrf_token()]);
})->name('csrf.token');

/*
|--------------------------------------------------------------------------
| Feature Routes
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';
require __DIR__.'/dashboard.php';
require __DIR__.'/lead.php';
require __DIR__.'/contact.php';
require __DIR__.'/topbar.php';
require __DIR__.'/career.php';
require __DIR__.'/patient.php';
require __DIR__.'/therapist.php';
require __DIR__.'/billing.php';
require __DIR__.'/report.php';
require __DIR__ . '/profile.php';