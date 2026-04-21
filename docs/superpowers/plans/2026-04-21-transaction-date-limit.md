# Transaction Date Limiting — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Limit `/api/sync/transactions` to last 6 months by default; pages re-fetch from server when user's date filter goes beyond the loaded window.

**Architecture:** Backend `SyncService::getTransactionData` gains `$from`/`$to` Carbon params (default 6 months). Controller reads query params and passes them. Frontend stores the loaded window boundary in `window.ERP.state.transactionLoadedFrom`; date filter changes in sales/purchases/inventory-ledger pages trigger a server re-fetch when needed.

**Tech Stack:** Laravel 12 / PHP 8.2, Vanilla JS, PHPUnit 11

---

## File Map

| File | What changes |
|------|-------------|
| `app/Services/SyncService.php` | `getTransactionData` gains `$from`/`$to`, returns `loadedFrom` |
| `app/Http/Controllers/Api/AuthController.php` | `syncTransactions` parses `?from`/`?to` query params |
| `public/js/api.js` | `syncTransactions(params)` builds query string |
| `public/js/app.js` | Expose `mergeState`; set `transactionLoadedFrom` after sync |
| `public/js/pages/sales.js` | Date filter triggers server re-fetch when out of range |
| `public/js/pages/purchases.js` | Same |
| `public/js/pages/inventory-ledger.js` | Same |
| `tests/Feature/SyncDateLimitTest.php` | New — verifies date filtering behaviour |

---

## Task 1: Backend — `SyncService::getTransactionData` with date params

**Files:**
- Modify: `app/Services/SyncService.php:158-193`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/SyncDateLimitTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\Feature\ApiTestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Carbon;

class SyncDateLimitTest extends ApiTestCase
{
    #[Test]
    public function transactions_default_to_last_six_months(): void
    {
        $company = $this->createCompany();
        $user    = $this->createAdminUser($company);
        $token   = $this->loginAndGetToken($user);

        // Old sale — 8 months ago (should be excluded by default)
        \App\Models\SaleOrder::create([
            'id'             => 'SO-OLD000001',
            'invoice_no'     => 'INV-OLD-001',
            'company_id'     => $company->id,
            'customer_id'    => null,
            'payment_method' => 'cash',
            'total_amount'   => 100,
            'is_returned'    => false,
            'return_status'  => 'none',
            'created_at'     => Carbon::now()->subMonths(8),
            'updated_at'     => Carbon::now()->subMonths(8),
        ]);

        // Recent sale — 1 month ago (should be included)
        \App\Models\SaleOrder::create([
            'id'             => 'SO-NEW000001',
            'invoice_no'     => 'INV-NEW-001',
            'company_id'     => $company->id,
            'customer_id'    => null,
            'payment_method' => 'cash',
            'total_amount'   => 200,
            'is_returned'    => false,
            'return_status'  => 'none',
            'created_at'     => Carbon::now()->subMonth(),
            'updated_at'     => Carbon::now()->subMonth(),
        ]);

        $response = $this->auth($token)->getJson('/api/sync/transactions');

        $response->assertOk();
        $ids = collect($response->json('sales'))->pluck('id')->all();
        $this->assertContains('INV-NEW-001', $ids);
        $this->assertNotContains('INV-OLD-001', $ids);
        $this->assertArrayHasKey('loadedFrom', $response->json());
    }

    #[Test]
    public function transactions_respect_explicit_from_param(): void
    {
        $company = $this->createCompany();
        $user    = $this->createAdminUser($company);
        $token   = $this->loginAndGetToken($user);

        \App\Models\SaleOrder::create([
            'id'             => 'SO-OLD000002',
            'invoice_no'     => 'INV-OLD-002',
            'company_id'     => $company->id,
            'customer_id'    => null,
            'payment_method' => 'cash',
            'total_amount'   => 100,
            'is_returned'    => false,
            'return_status'  => 'none',
            'created_at'     => Carbon::now()->subMonths(8),
            'updated_at'     => Carbon::now()->subMonths(8),
        ]);

        $from = Carbon::now()->subMonths(9)->toDateString();
        $response = $this->auth($token)->getJson('/api/sync/transactions?from=' . $from);

        $response->assertOk();
        $ids = collect($response->json('sales'))->pluck('id')->all();
        $this->assertContains('INV-OLD-002', $ids);
    }
}
```

- [ ] **Step 2: Run the test to confirm it fails**

```bash
/c/xampp/php/php artisan test tests/Feature/SyncDateLimitTest.php
```

Expected: FAIL — `loadedFrom` key missing, old sale appears in default response.

- [ ] **Step 3: Update `SyncService::getTransactionData`**

Replace the entire `getTransactionData` method in `app/Services/SyncService.php`:

```php
// ── Transactions: sales, purchases, payments, ledger ─────────────────────
// Default: last 6 months. Pass $from/$to to override.
public function getTransactionData(User $user, ?\Carbon\Carbon $from = null, ?\Carbon\Carbon $to = null): array
{
    $from ??= now()->subMonths(6)->startOfDay();

    $isSuper = $user->system_role === 'Super Admin';
    $coId    = $user->company_id;

    $sales           = $this->scopedQueryWithDates(SaleOrder::with('items'),         $isSuper, $coId, $from, $to);
    $purchaseOrders  = $this->scopedQueryWithDates(PurchaseOrder::with(['items', 'receives.items']), $isSuper, $coId, $from, $to);
    $payments        = $this->scopedQueryWithDates(Payment::query(),                  $isSuper, $coId, $from, $to);
    $ledger          = $this->scopedQueryWithDates(InventoryLedger::query(),           $isSuper, $coId, $from, $to);
    $salesReturns    = $this->scopedQueryWithDates(SaleReturn::with('items'),          $isSuper, $coId, $from, $to);
    $purchaseReturns = $this->scopedQueryWithDates(PurchaseReturn::with('items'),      $isSuper, $coId, $from, $to);

    // costLayers exempt — full history needed for FIFO costing accuracy
    $costLayers = $this->scopedQuery(InventoryCostLayer::query(), $isSuper, $coId);

    $openJobCards = $isSuper ? collect() : JobCard::with('items')
        ->where('company_id', $coId)
        ->where('status', 'open')
        ->get();

    $recentJobCards = $isSuper ? collect() : JobCard::where('company_id', $coId)
        ->where('status', 'closed')
        ->where('created_at', '>=', $from)
        ->orderByDesc('closed_at')
        ->limit(100)
        ->get();

    return [
        'loadedFrom'      => $from->toDateString(),
        'sales'           => SaleOrderResource::collection($sales),
        'purchaseOrders'  => PurchaseOrderResource::collection($purchaseOrders),
        'payments'        => PaymentResource::collection($payments),
        'ledger'          => InventoryLedgerResource::collection($ledger),
        'salesReturns'    => SaleReturnResource::collection($salesReturns),
        'purchaseReturns' => PurchaseReturnResource::collection($purchaseReturns),
        'costLayers'      => InventoryCostLayerResource::collection($costLayers),
        'jobCards'        => JobCardResource::collection($openJobCards),
        'jobCardHistory'  => JobCardResource::collection($recentJobCards),
    ];
}
```

Also add this **new private helper** at the bottom of the class, just above the closing `}`:

```php
private function scopedQueryWithDates(
    \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation $query,
    bool $isSuper,
    ?string $coId,
    \Carbon\Carbon $from,
    ?\Carbon\Carbon $to
) {
    $query->where('created_at', '>=', $from);
    if ($to) {
        $query->where('created_at', '<=', $to->endOfDay());
    }
    return $isSuper ? $query->get() : $query->where('company_id', $coId)->get();
}
```

- [ ] **Step 4: Run the test to confirm it passes**

```bash
/c/xampp/php/php artisan test tests/Feature/SyncDateLimitTest.php
```

Expected: PASS — both tests green.

- [ ] **Step 5: Run full test suite to check for regressions**

```bash
/c/xampp/php/php artisan test
```

Expected: All previously passing tests still pass.

- [ ] **Step 6: Commit**

```bash
git add app/Services/SyncService.php tests/Feature/SyncDateLimitTest.php
git commit -m "feat: limit getTransactionData to last 6 months by default"
```

---

## Task 2: Backend — `AuthController::syncTransactions` reads date params

**Files:**
- Modify: `app/Http/Controllers/Api/AuthController.php:69-74`

- [ ] **Step 1: Update `syncTransactions` method**

Replace lines 69–74 in `app/Http/Controllers/Api/AuthController.php`:

```php
// Transactions: sales, purchases, payments, ledger — heavy, background mein
public function syncTransactions(Request $request)
{
    $from = null;
    $to   = null;

    if ($request->filled('from')) {
        try { $from = \Carbon\Carbon::parse($request->input('from'))->startOfDay(); } catch (\Exception) {}
    }
    if ($request->filled('to')) {
        try { $to = \Carbon\Carbon::parse($request->input('to'))->endOfDay(); } catch (\Exception) {}
    }

    return response()->json(
        $this->syncService->getTransactionData($request->get('auth_user'), $from, $to)
    );
}
```

- [ ] **Step 2: Add controller-level test to `SyncDateLimitTest.php`**

Append this test to `tests/Feature/SyncDateLimitTest.php` inside the class:

```php
#[Test]
public function invalid_date_param_is_silently_ignored(): void
{
    $company = $this->createCompany();
    $user    = $this->createAdminUser($company);
    $token   = $this->loginAndGetToken($user);

    $response = $this->auth($token)->getJson('/api/sync/transactions?from=not-a-date');

    // Should still return 200 with default 6-month window
    $response->assertOk();
    $this->assertArrayHasKey('loadedFrom', $response->json());
}
```

- [ ] **Step 3: Run the tests**

```bash
/c/xampp/php/php artisan test tests/Feature/SyncDateLimitTest.php
```

Expected: All 3 tests pass.

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/Api/AuthController.php tests/Feature/SyncDateLimitTest.php
git commit -m "feat: syncTransactions controller reads ?from=/?to= query params"
```

---

## Task 3: Frontend — `api.js` and `app.js`

**Files:**
- Modify: `public/js/api.js:57-66`
- Modify: `public/js/app.js:50-57` (sync function), `app.js:73-88` (syncProgressive), `app.js:39-47` (mergeState)

- [ ] **Step 1: Update `syncTransactions` in `api.js`**

In `public/js/api.js`, replace lines 57–66:

```js
syncTransactions: function(params) {
    var token = getToken();
    if (!token) return Promise.resolve({});
    var qs = '';
    if (params && (params.from || params.to)) {
        var parts = [];
        if (params.from) parts.push('from=' + encodeURIComponent(params.from));
        if (params.to)   parts.push('to='   + encodeURIComponent(params.to));
        qs = '?' + parts.join('&');
    }
    return request('GET', '/sync/transactions' + qs).catch(function() { return {}; });
},
```

- [ ] **Step 2: Expose `mergeState` in `app.js` and set `transactionLoadedFrom`**

In `public/js/app.js`, replace the `mergeState` function (lines 39–47) to also expose it on `window.ERP`:

```js
function mergeState(data) {
    if (data && typeof data === 'object') {
        for (var key in data) {
            if (data.hasOwnProperty(key)) {
                window.ERP.state[key] = data[key];
            }
        }
    }
}
window.ERP.mergeState = mergeState;
```

Then in `syncProgressive` (lines 73–88), update the `syncTransactions` `.then` callback to also store `loadedFrom`:

```js
window.ERP.api.syncTransactions().then(function(txData) {
    mergeState(txData);
    if (txData.loadedFrom) {
        window.ERP.state.transactionLoadedFrom = txData.loadedFrom;
    }
    if (typeof window.ERP.onReady === 'function') {
        window.ERP.onReady();
    }
})
```

- [ ] **Step 3: Manual smoke test**

Open browser at `http://localhost/erppos`, open DevTools → Network tab, reload any page. Confirm:
- `GET /api/sync/transactions` response contains `loadedFrom` key (check Response tab)
- No JS errors in Console

- [ ] **Step 4: Commit**

```bash
git add public/js/api.js public/js/app.js
git commit -m "feat: api.js syncTransactions accepts params; app.js exposes mergeState and stores transactionLoadedFrom"
```

---

## Task 4: Frontend — `sales.js` server re-fetch on out-of-range date

**Files:**
- Modify: `public/js/pages/sales.js`

- [ ] **Step 1: Add `sRefetchIfNeeded` helper and wire to date filter**

In `public/js/pages/sales.js`, replace the date filter `onChange` listeners inside `window.ERP.onReady` (currently lines 12–26):

```js
['sale-date-from', 'sale-date-to'].forEach(function(id) {
    var el = document.getElementById(id);
    if (!el) return;
    el.addEventListener('change', function() {
        sCurrentPage = 1;
        sRefetchIfNeeded(renderPage);
    });
    el.addEventListener('input', function() {
        if (!this.value) return;
        var parts = this.value.split('-');
        if (parts[0] && parts[0].length > 4) {
            parts[0] = parts[0].slice(-4);
            this.value = parts.join('-');
            sCurrentPage = 1;
            sRefetchIfNeeded(renderPage);
        }
    });
});
```

Then add this new function anywhere before `window.ERP.onReady` in `sales.js`:

```js
function sRefetchIfNeeded(callback) {
    var loadedFrom = window.ERP.state.transactionLoadedFrom;
    var requestedFrom = (document.getElementById('sale-date-from').value || '');
    var requestedTo   = (document.getElementById('sale-date-to').value   || '');

    if (loadedFrom && requestedFrom && requestedFrom < loadedFrom) {
        var tbody = document.getElementById('salesTableBody');
        if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-2"></span>Loading...</td></tr>';

        ERP.api.syncTransactions({ from: requestedFrom, to: requestedTo || undefined })
            .then(function(txData) {
                ERP.mergeState(txData);
                if (txData.loadedFrom) {
                    window.ERP.state.transactionLoadedFrom = txData.loadedFrom;
                }
                if (typeof callback === 'function') callback();
            })
            .catch(function(e) {
                alert('Error loading data: ' + e.message);
            });
    } else {
        if (typeof callback === 'function') callback();
    }
}
```

- [ ] **Step 2: Manual smoke test**

1. Open `http://localhost/erppos/sales`
2. Set "Date From" to a date more than 6 months ago
3. Confirm loading spinner appears briefly, then older sales appear
4. Set "Date From" to last month — confirm instant client-side filter (no network request in DevTools)

- [ ] **Step 3: Commit**

```bash
git add public/js/pages/sales.js
git commit -m "feat: sales page re-fetches from server when date filter exceeds loaded window"
```

---

## Task 5: Frontend — `purchases.js` server re-fetch on out-of-range date

**Files:**
- Modify: `public/js/pages/purchases.js`

- [ ] **Step 1: Add `poRefetchIfNeeded` helper and wire to date filter**

Add this function before `window.ERP.onReady` in `public/js/pages/purchases.js`:

```js
function poRefetchIfNeeded(callback) {
    var loadedFrom = window.ERP.state.transactionLoadedFrom;
    var requestedFrom = (document.getElementById('po-date-from').value || '');
    var requestedTo   = (document.getElementById('po-date-to').value   || '');

    if (loadedFrom && requestedFrom && requestedFrom < loadedFrom) {
        var tbody = document.getElementById('poTableBody');
        if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-2"></span>Loading...</td></tr>';

        ERP.api.syncTransactions({ from: requestedFrom, to: requestedTo || undefined })
            .then(function(txData) {
                ERP.mergeState(txData);
                if (txData.loadedFrom) {
                    window.ERP.state.transactionLoadedFrom = txData.loadedFrom;
                }
                if (typeof callback === 'function') callback();
            })
            .catch(function(e) {
                alert('Error loading data: ' + e.message);
            });
    } else {
        if (typeof callback === 'function') callback();
    }
}
```

In `window.ERP.onReady`, find where date filter events are registered (the `po-date-from` / `po-date-to` change listeners) and update them to call `poRefetchIfNeeded(renderPage)` instead of just `renderPage()`. If they don't exist yet, add inside `window.ERP.onReady`:

```js
['po-date-from', 'po-date-to'].forEach(function(id) {
    var el = document.getElementById(id);
    if (!el) return;
    el.addEventListener('change', function() { poCurrentPage = 1; poRefetchIfNeeded(renderPage); });
});
```

- [ ] **Step 2: Manual smoke test**

1. Open `http://localhost/erppos/purchases`
2. Set "Date From" to a date more than 6 months ago
3. Confirm loading spinner → older POs appear
4. Set "Date From" to recent date — instant re-render, no network request

- [ ] **Step 3: Commit**

```bash
git add public/js/pages/purchases.js
git commit -m "feat: purchases page re-fetches from server when date filter exceeds loaded window"
```

---

## Task 6: Frontend — `inventory-ledger.js` server re-fetch on out-of-range date

**Files:**
- Modify: `public/js/pages/inventory-ledger.js`

- [ ] **Step 1: Add `ilRefetchIfNeeded` helper and wire to date filter**

Add this function before the `DOMContentLoaded` listener in `public/js/pages/inventory-ledger.js`:

```js
function ilRefetchIfNeeded(callback) {
    var loadedFrom = window.ERP.state.transactionLoadedFrom;
    var requestedFrom = (document.getElementById('dateFrom').value || '');
    var requestedTo   = (document.getElementById('dateTo').value   || '');

    if (loadedFrom && requestedFrom && requestedFrom < loadedFrom) {
        var tbody = document.getElementById('ilTableBody');
        if (tbody) tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-2"></span>Loading...</td></tr>';

        ERP.api.syncTransactions({ from: requestedFrom, to: requestedTo || undefined })
            .then(function(txData) {
                ERP.mergeState(txData);
                if (txData.loadedFrom) {
                    window.ERP.state.transactionLoadedFrom = txData.loadedFrom;
                }
                if (typeof callback === 'function') callback();
            })
            .catch(function(e) {
                alert('Error loading data: ' + e.message);
            });
    } else {
        if (typeof callback === 'function') callback();
    }
}
```

Then update the existing `dateFrom`/`dateTo` change listeners (lines 15–17) to call `ilRefetchIfNeeded`:

```js
['dateFrom','dateTo'].forEach(function(id){
    document.getElementById(id).addEventListener('change', function(){ ilPage=1; ilRefetchIfNeeded(renderPage); });
});
```

- [ ] **Step 2: Manual smoke test**

1. Open `http://localhost/erppos/inventory-ledger`
2. Set "Date From" beyond 6 months ago
3. Confirm spinner → older ledger entries appear
4. Clear date → instant client-side render

- [ ] **Step 3: Final full test suite run**

```bash
/c/xampp/php/php artisan test
```

Expected: All tests pass.

- [ ] **Step 4: Commit**

```bash
git add public/js/pages/inventory-ledger.js
git commit -m "feat: inventory ledger re-fetches from server when date filter exceeds loaded window"
```

---

## Self-Review Checklist

- [x] `loadedFrom` returned by service ✅ Task 1 Step 3
- [x] `loadedFrom` stored in `window.ERP.state` ✅ Task 3 Step 2
- [x] `mergeState` exposed as `window.ERP.mergeState` ✅ Task 3 Step 2
- [x] `costLayers` NOT date-filtered ✅ Task 1 Step 3
- [x] Invalid `?from=` param silently ignored ✅ Task 2 Step 2
- [x] All three pages wired ✅ Tasks 4–6
- [x] No placeholder steps — every step has actual code ✅
