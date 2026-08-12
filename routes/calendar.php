<?php

use App\Http\Controllers\Calendar\CalendarController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('calendar')
    ->name('calendar.')
    ->group(function () {

        Route::get('/', [CalendarController::class, 'index'])->name('index');
        Route::get('/feed', [CalendarController::class, 'feed'])->name('feed');
        Route::post('/', [CalendarController::class, 'store'])->name('store');
        Route::get('/{calendarSession}', [CalendarController::class, 'show'])->name('show');
        Route::get('/{calendarSession}/edit', [CalendarController::class, 'edit'])->name('edit');
        Route::put('/{calendarSession}', [CalendarController::class, 'update'])->name('update');
        Route::delete('/{calendarSession}', [CalendarController::class, 'destroy'])->name('destroy');

    });