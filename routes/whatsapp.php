<?php

use App\Http\Controllers\Whatsapp\WhatsappController;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth', 'feature:whatsapp'])
    ->group(function () {

        Route::get('/whatsapp', [WhatsappController::class, 'index'])->name('whatsapp.index');
        Route::get('/whatsapp/poll', [WhatsappController::class, 'poll'])->name('whatsapp.poll');
        Route::post('/whatsapp/send', [WhatsappController::class, 'send'])->middleware('throttle:20,1')->name('whatsapp.send');
        Route::post('/whatsapp/{contact}/convert-to-lead', [WhatsappController::class, 'convertToLead'])->name('whatsapp.convertToLead');
        Route::post('/whatsapp/{contact}/family-details', [WhatsappController::class, 'updateFamilyDetails'])->name('whatsapp.updateFamilyDetails');
        Route::post('/whatsapp/{contact}/ai-state', [WhatsappController::class, 'updateAiState'])->name('whatsapp.updateAiState');
        Route::post('/whatsapp/message/{message}/convert-to-lead', [WhatsappController::class, 'convertMessageToLead'])->name('whatsapp.message.convertToLead');

    });

// Meta calls these directly (no session auth), so they sit outside the auth group.
Route::get('/whatsapp/webhook', [WhatsappController::class, 'verifyWebhook'])->name('whatsapp.webhook.verify');
Route::post('/whatsapp/webhook', [WhatsappController::class, 'handleWebhook'])->name('whatsapp.webhook.handle');

// Instagram redirects the account owner's browser here after they approve business login.
Route::get('/instagram/callback', [WhatsappController::class, 'instagramOAuthCallback'])->name('instagram.callback');
