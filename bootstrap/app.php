<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/');
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 419 "Page expired" (stale CSRF token / session timed out): send people somewhere useful
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() !== 419 || $request->expectsJson()) {
                return null;
            }

            if (! $request->user() || $request->routeIs('logout')) {
                return redirect()->route('login')
                    ->withErrors(['email' => 'Your session expired. Please log in again.']);
            }

            return back()
                ->withInput($request->except(['password', 'password_confirmation', 'current_password', '_token']))
                ->with('err', 'The page had expired, please try again.');
        });
    })->create();
