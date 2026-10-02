<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Token sign-in for the mobile app. Same rules as the web's
 * Auth\LoginController and ForgotPasswordController (validation messages,
 * email+IP lockout), but answering in JSON and issuing a Sanctum token
 * instead of starting a session.
 */
class AuthController extends Controller
{
    /** Same limits as Auth\LoginController. */
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $throttleKey = Str::lower($request->input('email')).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $message = "Too many login attempts. Please try again in {$seconds} seconds.";

            return response()->json(['message' => $message, 'errors' => ['email' => [$message]]], 429);
        }

        $user = User::where('email', $credentials['email'])->first();

        // A suspended account is refused with the same message as a wrong
        // password, so the form can't be used to tell which accounts exist.
        if (! $user || ! Hash::check($credentials['password'], $user->password) || ! $user->is_active) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);
            $message = 'Invalid email or password.';

            return response()->json(['message' => $message, 'errors' => ['email' => [$message]]], 422);
        }

        RateLimiter::clear($throttleKey);

        $token = $user->createToken($request->input('device_name') ?: 'mobile')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $this->userPayload($user)]);
    }

    /** The signed-in user with their role template, so the app can resolve permissions. */
    public function me(Request $request)
    {
        return response()->json($this->userPayload($request->user()));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Signed out.']);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $throttleKey = 'forgot-password|'.Str::lower($request->input('email')).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $message = "Too many attempts. Please try again in {$seconds} seconds.";

            return response()->json(['message' => $message, 'errors' => ['email' => [$message]]], 429);
        }

        RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

        Password::sendResetLink($request->only('email'));

        // Same answer whether or not the email exists, so it can't be used to
        // find out which staff emails have accounts.
        return response()->json(['message' => 'If an account exists for that email, a password reset link has been sent.']);
    }

    public static function userPayload(User $user): User
    {
        return $user->fresh()->load('roleTemplate');
    }
}
