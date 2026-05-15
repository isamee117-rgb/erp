# Rate Limiting Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add per-user rate limiting to all LeanERP API endpoints so a single user's spam cannot take down the server for 350 concurrent users.

**Architecture:** Define 4 named rate limiters in `AppServiceProvider::boot()` using Laravel's `RateLimiter::for()`. Apply them via `throttle:name` sub-groups inside `routes/api.php`. Key is per authenticated user ID (falls back to IP for safety). File cache driver — no Redis needed.

**Tech Stack:** Laravel 12, PHP 8.2, XAMPP, file cache (`CACHE_STORE=file`), PHPUnit 11

---

## Files

| Action | Path |
|--------|------|
| **Modify** | `app/Providers/AppServiceProvider.php` |
| **Modify** | `routes/api.php` |
| **Create** | `tests/Feature/RateLimitTest.php` |

---

## Rate Limit Groups (reference)

| Name | Limit | Endpoints |
|------|-------|-----------|
| `sync-heavy` | 10 req/min | `GET /sync`, `GET /sync/transactions` |
| `sync-light` | 30 req/min | `GET /sync/core`, `GET /sync/master` |
| `api-mutations` | 60 req/min | All POST / PUT / DELETE (except login) |
| `api-reads` | 120 req/min | All remaining GET endpoints |

---

## Task 1: Write the Failing Tests

**Files:**
- Create: `tests/Feature/RateLimitTest.php`

The test must clear the rate limiter cache before each test — otherwise previous test runs leave counts in the file cache and tests interfere with each other.

- [ ] **Step 1: Create `tests/Feature/RateLimitTest.php`**

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\RateLimiter;
use Tests\Feature\ApiTestCase;

class RateLimitTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('sync-heavy:' . $this->user->id);
        RateLimiter::clear('sync-light:' . $this->user->id);
        RateLimiter::clear('api-mutations:' . $this->user->id);
        RateLimiter::clear('api-reads:' . $this->user->id);
    }

    #[Test]
    public function sync_transactions_is_blocked_after_ten_requests(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->getJson('/api/sync/transactions', $this->auth())
                 ->assertStatus(200);
        }

        $this->getJson('/api/sync/transactions', $this->auth())
             ->assertStatus(429)
             ->assertJson(['error' => 'Too many requests. Please slow down.']);
    }

    #[Test]
    public function sync_core_is_blocked_after_thirty_requests(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->getJson('/api/sync/core', $this->auth())
                 ->assertStatus(200);
        }

        $this->getJson('/api/sync/core', $this->auth())
             ->assertStatus(429)
             ->assertJson(['error' => 'Too many requests. Please slow down.']);
    }

    #[Test]
    public function mutations_are_blocked_after_sixty_requests(): void
    {
        // Use logout as a cheap POST endpoint (it returns 200 regardless of state)
        for ($i = 0; $i < 60; $i++) {
            $this->postJson('/api/logout', [], $this->auth())
                 ->assertStatus(200);
        }

        $this->postJson('/api/logout', [], $this->auth())
             ->assertStatus(429)
             ->assertJson(['error' => 'Too many requests. Please slow down.']);
    }

    #[Test]
    public function reads_are_blocked_after_one_hundred_twenty_requests(): void
    {
        for ($i = 0; $i < 120; $i++) {
            $this->getJson('/api/settings/document-sequences', $this->auth())
                 ->assertStatus(200);
        }

        $this->getJson('/api/settings/document-sequences', $this->auth())
             ->assertStatus(429)
             ->assertJson(['error' => 'Too many requests. Please slow down.']);
    }

    #[Test]
    public function different_users_have_independent_rate_limit_counters(): void
    {
        $company2  = $this->createCompany();
        $user2     = $this->createAdminUser($company2);
        $token2    = $this->loginAndGetToken($company2, $user2->username, 'password');
        $auth2     = ['Authorization' => 'Bearer ' . $token2];

        RateLimiter::clear('sync-heavy:' . $user2->id);

        // Exhaust user1's sync-heavy limit
        for ($i = 0; $i < 10; $i++) {
            $this->getJson('/api/sync/transactions', $this->auth())->assertStatus(200);
        }
        $this->getJson('/api/sync/transactions', $this->auth())->assertStatus(429);

        // user2 is unaffected
        $this->getJson('/api/sync/transactions', $auth2)->assertStatus(200);
    }
}
```

- [ ] **Step 2: Run the tests to confirm they fail (rate limiting not yet implemented)**

```bash
/c/xampp/php/php artisan test --filter=RateLimitTest
```

Expected output — all 5 tests fail with `Expected status code 429 but received 200`:
```
FAIL  Tests\Feature\RateLimitTest
⨯ sync transactions is blocked after ten requests
⨯ sync core is blocked after thirty requests
⨯ mutations are blocked after sixty requests
⨯ reads are blocked after one hundred twenty requests
⨯ different users have independent rate limit counters
```

---

## Task 2: Define Named Rate Limiters in AppServiceProvider

**Files:**
- Modify: `app/Providers/AppServiceProvider.php`

- [ ] **Step 3: Replace `app/Providers/AppServiceProvider.php` with this exact content**

```php
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
        // Key: user ID for authenticated requests (guaranteed by ApiTokenAuth middleware).
        // Falls back to IP only as a safety net — should never happen in practice.
        $byUser = fn(Request $request): string =>
            (string) ($request->get('auth_user')?->id ?? $request->ip());

        // GET /sync and GET /sync/transactions — pulls 6 months of data from 8 tables.
        // 10/min = one full sync every 6 seconds, enough for any real use case.
        RateLimiter::for('sync-heavy', fn(Request $request) =>
            Limit::perMinute(10)->by($byUser($request))
                 ->response(fn() => response()->json(
                     ['error' => 'Too many requests. Please slow down.'], 429
                 ))
        );

        // GET /sync/core and GET /sync/master — lighter but still multi-table.
        RateLimiter::for('sync-light', fn(Request $request) =>
            Limit::perMinute(30)->by($byUser($request))
                 ->response(fn() => response()->json(
                     ['error' => 'Too many requests. Please slow down.'], 429
                 ))
        );

        // All POST / PUT / DELETE endpoints (except login which has its own throttle).
        // 60/min = 1 mutation per second — enough for the busiest POS cashier.
        RateLimiter::for('api-mutations', fn(Request $request) =>
            Limit::perMinute(60)->by($byUser($request))
                 ->response(fn() => response()->json(
                     ['error' => 'Too many requests. Please slow down.'], 429
                 ))
        );

        // GET endpoints that are not sync — barcode scan, reports, settings reads.
        // 120/min = 2 per second per user, generous for read-only lookups.
        RateLimiter::for('api-reads', fn(Request $request) =>
            Limit::perMinute(120)->by($byUser($request))
                 ->response(fn() => response()->json(
                     ['error' => 'Too many requests. Please slow down.'], 429
                 ))
        );
    }
}
```

---

## Task 3: Apply Throttle Middleware in routes/api.php

**Files:**
- Modify: `routes/api.php`

Restructure the single flat group into 4 throttle sub-groups inside the existing `ApiTokenAuth` group. Login stays untouched (`throttle:5,1` per IP already).

- [ ] **Step 4: Replace `routes/api.php` with this exact content**

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PartyController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\FieldSettingController;
use App\Http\Controllers\Api\ChartOfAccountController;
use App\Http\Controllers\Api\AccountMappingController;
use App\Http\Controllers\Api\JournalEntryController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\JobCardController;
use App\Http\Middleware\ApiTokenAuth;

// Login: 5 attempts/minute per IP (unchanged)
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware(ApiTokenAuth::class)->group(function () {

    // ── Heavy sync: 10 req/min per user ──────────────────────────────────────
    Route::middleware('throttle:sync-heavy')->group(function () {
        Route::get('/sync',              [AuthController::class, 'sync']);
        Route::get('/sync/transactions', [AuthController::class, 'syncTransactions']);
    });

    // ── Light sync: 30 req/min per user ──────────────────────────────────────
    Route::middleware('throttle:sync-light')->group(function () {
        Route::get('/sync/core',   [AuthController::class, 'syncCore']);
        Route::get('/sync/master', [AuthController::class, 'syncMaster']);
    });

    // ── Mutations: 60 req/min per user ───────────────────────────────────────
    Route::middleware('throttle:api-mutations')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::post('/companies',                        [CompanyController::class, 'store']);
        Route::put('/companies/{id}/status',             [CompanyController::class, 'updateStatus']);
        Route::put('/companies/{id}/limit',              [CompanyController::class, 'updateLimit']);
        Route::put('/companies/{id}/admin-password',     [CompanyController::class, 'updateAdminPassword']);
        Route::put('/companies/{id}/details',            [CompanyController::class, 'updateDetails']);
        Route::put('/company-info',                      [CompanyController::class, 'updateInfo']);
        Route::post('/company-logo',                     [CompanyController::class, 'uploadLogo']);

        Route::post('/products',                                  [ProductController::class, 'store']);
        Route::put('/products/{id}',                              [ProductController::class, 'update']);
        Route::delete('/products/{id}',                           [ProductController::class, 'destroy']);
        Route::post('/products/adjust-stock',                     [ProductController::class, 'adjustStock']);
        Route::post('/products/{id}/uom-conversions',             [ProductController::class, 'storeUomConversion']);
        Route::put('/products/{id}/uom-conversions/{cid}',        [ProductController::class, 'updateUomConversion']);
        Route::delete('/products/{id}/uom-conversions/{cid}',     [ProductController::class, 'destroyUomConversion']);
        Route::post('/products/{id}/price-tiers',                 [ProductController::class, 'storePriceTier']);
        Route::put('/products/{id}/price-tiers/{tid}',            [ProductController::class, 'updatePriceTier']);
        Route::delete('/products/{id}/price-tiers/{tid}',         [ProductController::class, 'destroyPriceTier']);

        Route::post('/parties',        [PartyController::class, 'store']);
        Route::put('/parties/{id}',    [PartyController::class, 'update']);
        Route::delete('/parties/{id}', [PartyController::class, 'destroy']);

        Route::post('/sales',        [SaleController::class, 'store']);
        Route::post('/sales/return', [SaleController::class, 'createReturn']);

        Route::post('/purchases',                      [PurchaseController::class, 'store']);
        Route::put('/purchases/{id}/receive',          [PurchaseController::class, 'receive']);
        Route::post('/purchases/{id}/partial-receive', [PurchaseController::class, 'receive']);
        Route::post('/purchases/return',               [PurchaseController::class, 'createReturn']);

        Route::post('/payments',        [PaymentController::class, 'store']);
        Route::delete('/payments/{id}', [PaymentController::class, 'destroy']);

        Route::post('/job-cards',                          [JobCardController::class, 'store']);
        Route::put('/job-cards/{id}',                      [JobCardController::class, 'update']);
        Route::post('/job-cards/{id}/items',               [JobCardController::class, 'addItem']);
        Route::put('/job-cards/{id}/items/{itemId}',       [JobCardController::class, 'updateItem']);
        Route::delete('/job-cards/{id}/items/{itemId}',    [JobCardController::class, 'removeItem']);
        Route::post('/job-cards/{id}/finalize',            [JobCardController::class, 'finalize']);
        Route::delete('/job-cards/{id}',                   [JobCardController::class, 'destroy']);

        Route::put('/settings/job-card-mode',       [SettingsController::class, 'updateJobCardMode']);
        Route::put('/settings/currency',            [SettingsController::class, 'updateCurrency']);
        Route::put('/settings/invoice-format',      [SettingsController::class, 'updateInvoiceFormat']);
        Route::put('/settings/costing-method',      [SettingsController::class, 'updateCostingMethod']);
        Route::put('/settings/document-sequences',  [SettingsController::class, 'updateDocumentSequence']);
        Route::post('/categories',                  [SettingsController::class, 'createCategory']);
        Route::delete('/categories/{id}',           [SettingsController::class, 'deleteCategory']);
        Route::post('/uoms',                        [SettingsController::class, 'createUOM']);
        Route::delete('/uoms/{id}',                 [SettingsController::class, 'deleteUOM']);
        Route::post('/entity-types',                [SettingsController::class, 'createEntityType']);
        Route::delete('/entity-types/{id}',         [SettingsController::class, 'deleteEntityType']);
        Route::post('/business-categories',         [SettingsController::class, 'createBusinessCategory']);
        Route::delete('/business-categories/{id}',  [SettingsController::class, 'deleteBusinessCategory']);

        Route::post('/users',                [UserController::class, 'store']);
        Route::put('/users/{id}',            [UserController::class, 'update']);
        Route::put('/users/{id}/status',     [UserController::class, 'setStatus']);
        Route::put('/users/{id}/password',   [UserController::class, 'updatePassword']);

        Route::post('/roles',        [RoleController::class, 'store']);
        Route::put('/roles/{id}',    [RoleController::class, 'update']);
        Route::delete('/roles/{id}', [RoleController::class, 'destroy']);

        Route::put('/field-settings/{fieldKey}', [FieldSettingController::class, 'update']);

        Route::post('/accounting/coa',              [ChartOfAccountController::class, 'store']);
        Route::put('/accounting/coa/{id}',          [ChartOfAccountController::class, 'update']);
        Route::delete('/accounting/coa/{id}',       [ChartOfAccountController::class, 'destroy']);
        Route::put('/accounting/mappings',          [AccountMappingController::class, 'update']);
        Route::post('/accounting/journals',         [JournalEntryController::class, 'store']);
        Route::post('/accounting/journals/{id}/post', [JournalEntryController::class, 'post']);
        Route::delete('/accounting/journals/{id}',  [JournalEntryController::class, 'destroy']);
    });

    // ── Reads: 120 req/min per user ───────────────────────────────────────────
    Route::middleware('throttle:api-reads')->group(function () {
        Route::get('/products/barcode',                    [ProductController::class, 'findByBarcode']);
        Route::get('/products/{id}/uom-conversions',       [ProductController::class, 'listUomConversions']);

        Route::get('/job-cards',         [JobCardController::class, 'index']);
        Route::get('/job-cards/history', [JobCardController::class, 'history']);
        Route::get('/job-cards/{id}',    [JobCardController::class, 'show']);

        Route::get('/settings/document-sequences', [SettingsController::class, 'getDocumentSequences']);
        Route::get('/field-settings',              [FieldSettingController::class, 'index']);

        Route::get('/accounting/coa',            [ChartOfAccountController::class, 'index']);
        Route::get('/accounting/mappings',       [AccountMappingController::class, 'index']);
        Route::get('/accounting/journals',       [JournalEntryController::class, 'index']);
        Route::get('/accounting/journals/{id}',  [JournalEntryController::class, 'show']);

        Route::get('/reports/profit-loss',   [ReportController::class, 'profitLoss']);
        Route::get('/reports/balance-sheet', [ReportController::class, 'balanceSheet']);
    });
});
```

---

## Task 4: Run All Tests

- [ ] **Step 5: Run the full RateLimitTest suite**

```bash
/c/xampp/php/php artisan test --filter=RateLimitTest
```

Expected — all 5 pass:
```
PASS  Tests\Feature\RateLimitTest
✓ sync transactions is blocked after ten requests
✓ sync core is blocked after thirty requests
✓ mutations are blocked after sixty requests
✓ reads are blocked after one hundred twenty requests
✓ different users have independent rate limit counters
```

- [ ] **Step 6: Run the full test suite to confirm no regressions**

```bash
/c/xampp/php/php artisan test
```

Expected: same pass/fail counts as before this change. The pre-existing failures in `PurchaseTest` and `SaleTest` (about JSON structure) will still fail — that is expected and unrelated to rate limiting.

---

## Task 5: Commit

- [ ] **Step 7: Commit**

```bash
git add app/Providers/AppServiceProvider.php routes/api.php tests/Feature/RateLimitTest.php
git commit -m "feat: add per-user rate limiting to all API endpoints

sync-heavy(10/min), sync-light(30/min), api-mutations(60/min),
api-reads(120/min) — all keyed by authenticated user ID.
File cache driver, no Redis required."
```

---

## Self-Review

- [x] **Spec coverage:** All 4 groups defined + applied. Login (`throttle:5,1`) untouched. Error response `{"error": "Too many requests. Please slow down."}` with 429. ✅
- [x] **No placeholders:** Every step has exact code. ✅
- [x] **Type consistency:** `RateLimiter::for('sync-heavy', ...)` in Task 2 matches `throttle:sync-heavy` in Task 3 and `RateLimiter::clear('sync-heavy:' . $this->user->id)` in Task 1. ✅
- [x] **Route coverage check:** Every route from original `routes/api.php` appears in exactly one throttle group in the new file. `GET /sync` and `GET /sync/transactions` → sync-heavy. `GET /sync/core` and `GET /sync/master` → sync-light. All POST/PUT/DELETE → api-mutations. All remaining GETs → api-reads. ✅
