<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile API (v1)
|--------------------------------------------------------------------------
| JSON endpoints for the Engage Clinic mobile app, served under /api/v1.
| Sign-in returns a Sanctum token that the app sends as
| `Authorization: Bearer <token>`. Module routes carry the same `feature:`
| middleware as the web routes, so access rules are identical. Every
| request under this prefix is answered in JSON (ForceJsonResponse).
*/

Route::prefix('v1')->group(function () {

    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::put('profile', [ProfileController::class, 'update']);

        require __DIR__.'/api/modules.php';
    });
});
