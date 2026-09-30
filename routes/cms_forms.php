<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CmsForm\FormController;
use App\Http\Controllers\CmsForm\FormSubmissionController;
use App\Http\Controllers\CmsForm\PublicFormController;

/*
|--------------------------------------------------------------------------
| Admin CMS Forms Routes
|--------------------------------------------------------------------------
|
| URL:
| /admin/cms/forms
| /admin/cms/forms/{form}/responses
| /admin/cms/responses/{submission}
|
| Route names:
| cms.forms.index / .store / .edit / .update / .destroy / ...
| cms.forms.responses.index / .export / .show / .status / .convert / .file
|
*/

Route::middleware(['auth', 'feature:cms_forms'])
    ->prefix('admin/cms')
    ->name('cms.forms.')
    ->group(function () {

        Route::get('forms', [FormController::class, 'index'])->name('index');
        Route::post('forms', [FormController::class, 'store'])->name('store');
        Route::get('forms/{form}/edit', [FormController::class, 'edit'])->name('edit');
        Route::put('forms/{form}', [FormController::class, 'update'])->name('update');
        // POST (not PUT) so the page can still flush it with fetch keepalive as the tab closes.
        Route::post('forms/{form}/autosave', [FormController::class, 'autosave'])->name('autosave');
        Route::delete('forms/{form}/autosave', [FormController::class, 'discardAutosave'])->name('autosave.discard');
        Route::delete('forms/{form}', [FormController::class, 'destroy'])->name('destroy');
        Route::get('forms/{form}/preview', [FormController::class, 'preview'])->name('preview');
        Route::post('forms/{form}/publish', [FormController::class, 'publish'])->name('publish');
        Route::post('forms/{form}/unpublish', [FormController::class, 'unpublish'])->name('unpublish');
        Route::post('forms/{form}/duplicate', [FormController::class, 'duplicate'])->name('duplicate');

        Route::get('forms/{form}/responses', [FormSubmissionController::class, 'index'])->name('responses.index');
        Route::get('forms/{form}/responses/export', [FormSubmissionController::class, 'export'])->name('responses.export');
        Route::get('responses/{submission}', [FormSubmissionController::class, 'show'])->name('responses.show');
        Route::patch('responses/{submission}/status', [FormSubmissionController::class, 'updateStatus'])->name('responses.status');
        Route::post('responses/{submission}/convert-to-lead', [FormSubmissionController::class, 'convertToLead'])->name('responses.convert');
        Route::get('responses/{submission}/files/{value}', [FormSubmissionController::class, 'downloadFile'])->name('responses.file');

    });

/*
|--------------------------------------------------------------------------
| Public Forms
|--------------------------------------------------------------------------
| No CRM account needed. The POST is exempt from CSRF (bootstrap/app.php)
| so the form also works inside an <iframe> on another site, where the
| session cookie isn't sent - it carries a honeypot and is rate limited
| instead.
*/

Route::get('forms/{slug}', [PublicFormController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('forms.public.show');

Route::post('forms/{slug}', [PublicFormController::class, 'submit'])
    ->where('slug', '[a-z0-9-]+')
    ->middleware('throttle:10,1')
    ->name('forms.public.submit');
