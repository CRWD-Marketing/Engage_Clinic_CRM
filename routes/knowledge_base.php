<?php

use App\Http\Controllers\KnowledgeBase\KnowledgeBaseController;
use App\Http\Controllers\KnowledgeBase\ProcessQueueController;
use Illuminate\Support\Facades\Route;

// Hit by an external scheduler (e.g. a free cron-ping service), not a
// logged-in user - sits outside the auth group like the Meta webhook routes,
// and is protected by its own secret-token check instead of a session.
Route::get('/ai-employee/cron/process-queue', ProcessQueueController::class)->name('ai_employee.processQueue');

Route::middleware(['auth', 'feature:knowledge_base'])
    ->group(function () {

        Route::get('/knowledge-base', [KnowledgeBaseController::class, 'index'])->name('knowledge_base.index');
        Route::post('/knowledge-base', [KnowledgeBaseController::class, 'store'])->name('knowledge_base.store');

        // Global AI Employee settings (kill switch, model, history/KB limits, prompt
        // override) - small enough to live alongside knowledge base management rather
        // than a separate module. Must be registered before the PUT /{entry} route
        // below - otherwise "ai-settings" matches {entry} first (Laravel matches
        // routes in registration order) and fails as an unknown entry id/model.
        Route::put('/knowledge-base/ai-settings', [KnowledgeBaseController::class, 'updateSettings'])->name('knowledge_base.updateSettings');

        Route::put('/knowledge-base/{entry}', [KnowledgeBaseController::class, 'update'])->name('knowledge_base.update');
        Route::delete('/knowledge-base/{entry}', [KnowledgeBaseController::class, 'destroy'])->name('knowledge_base.destroy');

    });
