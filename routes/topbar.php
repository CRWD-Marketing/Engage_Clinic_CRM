<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Search\SearchController;
use App\Http\Controllers\Notification\NotificationController;
use App\Http\Controllers\Activity\ActivityController;

/*
|--------------------------------------------------------------------------
| Admin Topbar Routes (search, notifications, activity feed)
|--------------------------------------------------------------------------
|
| URL:
| /admin/search
| /admin/notifications
| /admin/activity-feed
|
*/

Route::middleware(['auth'])
    ->prefix('admin')
    ->group(function () {

        Route::get('search', [SearchController::class, 'search'])->name('search');

        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');

        Route::get('activity-feed', [ActivityController::class, 'index'])->name('activity.index');

    });
