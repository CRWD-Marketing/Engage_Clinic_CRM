<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\FeatureMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__.'/../routes/web.php',
            __DIR__.'/../routes/auth.php',
            __DIR__.'/../routes/whatsapp.php',
            __DIR__.'/../routes/patient.php',
            __DIR__.'/../routes/calendar.php',
            __DIR__.'/../routes/knowledge_base.php',
        ],
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'feature' => FeatureMiddleware::class,
        ]);

        // Meta calls this directly with no Laravel session/CSRF token.
        $middleware->validateCsrfTokens(except: [
            'whatsapp/webhook',
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, \Illuminate\Http\Request $request) {
            if (! $request->expectsJson()) {
                return back()->withErrors(['message' => 'You\'re sending too fast. Please wait a moment and try again.']);
            }
        });

        // A wrong-method hit (e.g. GET on the POST-only /logout route, from a stale
        // bookmark or typed URL) shouldn't show a raw stack trace - just send the
        // visitor somewhere sensible instead.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e, \Illuminate\Http\Request $request) {
            if (! $request->expectsJson()) {
                return redirect()->to(auth()->check() ? route('dashboard') : route('login'));
            }
        });
    })
    ->create();