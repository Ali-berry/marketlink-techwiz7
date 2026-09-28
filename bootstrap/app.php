<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Short names so routes can say ->middleware('role:farmer')
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
            'active' => \App\Http\Middleware\EnsureAccountIsActive::class,
            // Spatie's permission check, for splitting admin into super-admin / support-admin
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            // Catches up on urgent orders MarketLink AI should have marked ready by now
            'urgent-auto-ready' => \App\Http\Middleware\MarkDueUrgentOrdersReady::class,
        ]);

        // Guests get bounced to login with a friendly note instead of a blank
        // redirect - happens when someone browsing without an account tries to
        // add to basket, favourite something, checkout, or leave a review.
        $middleware->redirectGuestsTo(function () {
            session()->flash('status', "Please log in to do that - you'll land right back here after.");

            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();