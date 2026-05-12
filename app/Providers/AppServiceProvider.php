<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        JsonResource::withoutWrapping();

        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        // ApiTokenAuth sets auth_user via $request->merge() before ThrottleRequests runs.
        // Falls back to IP only as a safety net (unauthenticated edge cases).
        $byUser = fn(Request $request): string =>
            (string) ($request->get('auth_user')?->id ?? $request->ip());

        $tooManyResponse = fn() => response()->json(
            ['error' => 'Too many requests. Please slow down.'], 429
        );

        // Each limiter name is included in the ->by() key so that sync-heavy,
        // sync-light, api-mutations and api-reads maintain INDEPENDENT counters
        // per user. Without the prefix they would share the same cache key.

        // GET /sync and GET /sync/transactions — pulls 6 months of data from 8 tables.
        // 10/min = one full sync every 6 seconds, enough for any real use case.
        RateLimiter::for('sync-heavy', fn(Request $request) =>
            Limit::perMinute(10)->by('sync-heavy:' . $byUser($request))->response($tooManyResponse)
        );

        // GET /sync/core and GET /sync/master — lighter but still multi-table.
        RateLimiter::for('sync-light', fn(Request $request) =>
            Limit::perMinute(30)->by('sync-light:' . $byUser($request))->response($tooManyResponse)
        );

        // All POST / PUT / DELETE endpoints (except login which has its own throttle).
        // 60/min = 1 mutation per second — ceiling for the busiest POS cashier.
        RateLimiter::for('api-mutations', fn(Request $request) =>
            Limit::perMinute(60)->by('api-mutations:' . $byUser($request))->response($tooManyResponse)
        );

        // GET endpoints that are not sync — barcode scan, reports, settings reads.
        // 120/min = 2 per second per user, generous for read-only lookups.
        RateLimiter::for('api-reads', fn(Request $request) =>
            Limit::perMinute(120)->by('api-reads:' . $byUser($request))->response($tooManyResponse)
        );
    }
}
