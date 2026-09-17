<?php

use App\Http\Controllers\Calendar\CalendarController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'feature:calendar'])
    ->prefix('calendar')
    ->name('calendar.')
    ->group(function () {

        Route::get('/', [CalendarController::class, 'index'])->name('index');
        Route::get('/feed', [CalendarController::class, 'feed'])->name('feed');
        Route::get('/utilisation', [CalendarController::class, 'utilisation'])->name('utilisation');

        Route::get('/leave/impact', [CalendarController::class, 'leaveImpact'])->name('leave.impact');
        Route::post('/leave', [CalendarController::class, 'markLeave'])->name('leave.store');
        Route::delete('/leave/{leave}', [CalendarController::class, 'removeLeave'])->name('leave.destroy');

        Route::post('/', [CalendarController::class, 'store'])->name('store');
        Route::get('/{calendarSession}', [CalendarController::class, 'show'])->name('show');
        Route::put('/{calendarSession}', [CalendarController::class, 'update'])->name('update');
        Route::delete('/{calendarSession}', [CalendarController::class, 'destroy'])->name('destroy');

        Route::post('/{calendarSession}/supervision', [CalendarController::class, 'supervise'])->name('supervision.store');
        Route::delete('/{calendarSession}/supervision', [CalendarController::class, 'unsupervise'])->name('supervision.destroy');

        Route::patch('/{calendarSession}/therapist-note', [CalendarController::class, 'therapistNote'])->name('therapist-note.store');

    });
