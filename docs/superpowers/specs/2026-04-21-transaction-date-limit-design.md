# Transaction Date Limiting — Design Spec
**Date:** 2026-04-21

## Problem
`SyncService::getTransactionData` fetches ALL records with no limit — sales, purchases, payments, ledger, returns, cost layers. On weak internet or large datasets this causes slow page loads and timeouts.

## Solution
Add `from`/`to` date params to `/api/sync/transactions`. Default cutoff: last 6 months. Pages with date filters trigger a server re-fetch when the user requests data outside the loaded window.

## Architecture

### Backend

**`SyncService::getTransactionData(User $user, ?Carbon $from = null, ?Carbon $to = null)`**
- Return array includes `loadedFrom` key (ISO date string of the actual `$from` used)
- Default: `$from = now()->subMonths(6)`, `$to = null`
- Apply `->where('created_at', '>=', $from)` to: sales, purchaseOrders, payments, ledger, salesReturns, purchaseReturns
- Apply `->where('created_at', '<=', $to)` when `$to` is set
- `costLayers` — exempt (full history needed for FIFO costing accuracy)
- JobCards — apply same cutoff

**`AuthController::syncTransactions(Request $request)`**
- Read `?from=` and `?to=` query params
- Parse to Carbon (validate as valid dates, silently ignore invalid)
- Pass to `SyncService::getTransactionData`

### Frontend

**`api.js` — `syncTransactions(params)`**
- Accept optional `{from, to}` object
- Build query string: `/api/sync/transactions?from=YYYY-MM-DD&to=YYYY-MM-DD`
- No params = default 6-month window (server handles default)

**`app.js` — `syncProgressive`**
- No change to call signature — still calls `syncTransactions()` with no params
- After merge, set `window.ERP.state.transactionLoadedFrom` from the `loadedFrom` field returned by the server (exact timestamp, not client-side approximation)

**Pages — sales.js, purchases.js, inventory-ledger.js**
- Date filter `onChange`: if requested `from` date < `window.ERP.state.transactionLoadedFrom`
  - Show loading indicator on table
  - Call `ERP.api.syncTransactions({ from, to })` 
  - Merge result into `window.ERP.state` via existing `mergeState` (expose it or inline)
  - Update `transactionLoadedFrom` to new from-date
  - Re-render page
- Otherwise: existing client-side filter (no change)

## Affected Files

| File | Change |
|------|--------|
| `app/Services/SyncService.php` | Add `$from`/`$to` params, apply to all transaction queries |
| `app/Http/Controllers/Api/AuthController.php` | Parse and pass date params |
| `public/js/api.js` | `syncTransactions(params)` accepts params object |
| `public/js/app.js` | Set `transactionLoadedFrom` after sync; expose `mergeState` |
| `public/js/pages/sales.js` | Date filter triggers server fetch when out of range |
| `public/js/pages/purchases.js` | Same |
| `public/js/pages/inventory-ledger.js` | Same |

## Out of Scope
- Journal entries (already server-side paginated — no change needed)
- `costLayers` limiting (breaks costing)
- Payments page (no date filter UI currently)
