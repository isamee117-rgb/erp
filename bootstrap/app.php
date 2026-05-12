<?php

use App\Http\Middleware\ApiTokenAuth;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\ThrottleRequests;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Exclude JS-set cookies from Laravel's automatic encryption
        $middleware->encryptCookies(except: ['leanerp_token']);

        // Ensure ApiTokenAuth sets auth_user before any ThrottleRequests limiter reads it.
        // Without this, Laravel's priority map moves ThrottleRequests ahead of ApiTokenAuth,
        // causing $request->get('auth_user') to be null inside rate limiter closures.
        $middleware->prependToPriorityList(ThrottleRequests::class, ApiTokenAuth::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
