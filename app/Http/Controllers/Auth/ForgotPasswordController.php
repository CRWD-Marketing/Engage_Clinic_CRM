<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class ForgotPasswordController extends Controller
{
    /**
     * Max reset-link requests allowed for a given email+IP before throttling.
     */
    protected const MAX_ATTEMPTS = 5;

    protected const DECAY_SECONDS = 60;

    /**
     * Display the "enter your email" form.
     */
    public function showLinkRequestForm()
    {
        return view('auth.forgot_password');
    }

    /**
     * Email the user a signed reset link, via Laravel's built-in password broker.
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'email' => "Too many attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

        Password::sendResetLink($request->only('email'));

        // Always show the same message whether or not the email exists, so this
        // form can't be used to enumerate which staff emails have accounts.
        return back()->with('status', 'If an account exists for that email, a password reset link has been sent.');
    }

    /**
     * Display the "choose a new password" form.
     */
    public function showResetForm(Request $request, string $token)
    {
        return view('auth.reset_password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    /**
     * Validate the token and set the new password.
     */
    public function reset(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $validated,
            function ($user, $password) {
                $user->forceFill(['password' => $password])
                    ->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Your password has been reset. You can now sign in.');
        }

        return back()
            ->withErrors(['email' => __($status)])
            ->withInput($request->only('email'));
    }

    /**
     * Scope the limiter to email+IP so an attacker requesting resets for one
     * account can't lock out the real user from a different IP.
     */
    protected function throttleKey(Request $request): string
    {
        return Str::lower($request->input('email')).'|'.$request->ip();
    }
}
