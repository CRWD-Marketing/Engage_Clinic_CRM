<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Report\ReportController;

Route::middleware(['auth', 'feature:reports'])->group(function () {

    Route::get('/reports', [ReportController::class, 'index'])
        ->name('reports.index');

});