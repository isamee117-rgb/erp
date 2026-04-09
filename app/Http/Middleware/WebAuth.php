<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class WebAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Read raw cookie directly — bypasses Laravel's cookie encryption
        // since this token is set by JavaScript (plain text)
        $token = $_COOKIE['leanerp_token'] ?? null;

        if (!$token) {
            return redirect(url('/login'));
        }

        return $next($request);
    }
}
