<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Profile\ProfileController as WebProfileController;
use Illuminate\Http\Request;

/**
 * "My profile" for the mobile app: the web's ProfileController::update, with
 * the role template added to the returned user.
 */
class ProfileController extends WebProfileController
{
    public function update(Request $request)
    {
        $response = parent::update($request);

        if ($response->getStatusCode() !== 200) {
            // Validation failure: reshape `{success, errors}` into Laravel's usual `{message, errors}`.
            $errors = $response->getData(true)['errors'] ?? [];

            return response()->json([
                'message' => collect($errors)->flatten()->first() ?? 'The given data was invalid.',
                'errors' => $errors,
            ], $response->getStatusCode());
        }

        return response()->json(['success' => true, 'user' => AuthController::userPayload($request->user())]);
    }
}
