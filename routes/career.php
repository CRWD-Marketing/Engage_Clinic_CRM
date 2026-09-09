<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Career\JobPostingController;
use App\Http\Controllers\Career\JobApplicationController;

/*
|--------------------------------------------------------------------------
| Admin Careers Routes
|--------------------------------------------------------------------------
|
| URL:
| /admin/job-postings
| /admin/job-applications
|
| Route names:
| job-postings.index / .create / .store / .edit / .update / .destroy
| job-applications.index / .destroy
|
*/

Route::middleware(['auth', 'feature:careers'])
    ->prefix('admin')
    ->group(function () {

        Route::resource('job-postings', JobPostingController::class)->except(['show']);

        Route::get('job-applications', [JobApplicationController::class, 'index'])->name('job-applications.index');
        Route::get('job-applications/count', [JobApplicationController::class, 'getApplicationCount'])->name('job-applications.count');
        Route::get('job-applications/{jobApplication}/resume/view', [JobApplicationController::class, 'viewResume'])->name('job-applications.resume.view');
        Route::get('job-applications/{jobApplication}/resume', [JobApplicationController::class, 'downloadResume'])->name('job-applications.resume');
        Route::patch('job-applications/{jobApplication}/status', [JobApplicationController::class, 'updateStatus'])->name('job-applications.update-status');
        Route::post('job-applications/{jobApplication}/notes', [JobApplicationController::class, 'addNote'])->name('job-applications.notes.store');
        Route::delete('job-applications/{jobApplication}', [JobApplicationController::class, 'destroy'])->name('job-applications.destroy');

    });

/*
|--------------------------------------------------------------------------
| Public Careers API
|--------------------------------------------------------------------------
*/

Route::prefix('api')
    ->name('api.')
    ->group(function () {

        Route::post('job-applications', [JobApplicationController::class, 'apiStore'])->name('job-applications.store');

    });
