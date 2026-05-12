# Rate Limiting Design — LeanERP API

**Date:** 2026-05-12
**Goal:** Protect all API endpoints from abuse and overload, ensuring stable performance for 350 concurrent users.

---

## Problem

Only `POST /api/login` has rate limiting (`throttle:5,1`). Every other endpoint — including the heaviest one (`/api/sync/transactions` which pulls 6 months of data) — is unlimited. A single user repeatedly refreshing or a bug causing a request loop can bring the server down for all 350 users.

---

## Solution

Use **Laravel Named Rate Limiters** defined in `AppServiceProvider::boot()`, applied via `throttle:name` middleware in `routes/api.php`.

- **Storage:** Laravel file cache (`CACHE_STORE=file`) — no Redis required
- **Scope:** Per authenticated user (by user ID from `auth_user` on the request)
- **Response on limit exceeded:** `429 Too Many Requests` with JSON body

---

## Rate Limit Groups

| Group Name | Limit | Applied To |
|------------|-------|-----------|
| `sync-heavy` | 10 requests / minute | `GET /api/sync`, `GET /api/sync/transactions` |
| `sync-light` | 30 requests / minute | `GET /api/sync/core`, `GET /api/sync/master` |
| `api-mutations` | 60 requests / minute | All POST / PUT / DELETE endpoints (except login) |
| `api-reads` | 120 requests / minute | All remaining GET endpoints |

### Reasoning

- **sync-heavy (10/min):** `syncTransactions` loads 6 months of sales, purchases, payments, ledger, cost layers into memory. One user can hit it at most once every 6 seconds — enough for normal use, blocks spam.
- **sync-light (30/min):** core + master data are lighter but still hit multiple tables.
- **api-mutations (60/min):** A busy POS cashier doing 1 sale/second is 60/min — this is the realistic ceiling.
- **api-reads (120/min):** Read-only lookups (barcode scan, party search) can be faster; 2/second per user is generous.

---

## Error Response Format

```json
HTTP 429 Too Many Requests
Retry-After: 23

{
    "error": "Too many requests. Please slow down."
}
```

Laravel automatically adds the `Retry-After` and `X-RateLimit-*` headers.

---

## Files Changed

| File | Change |
|------|--------|
| `app/Providers/AppServiceProvider.php` | Define 4 named rate limiters in `boot()` |
| `routes/api.php` | Add `throttle:group-name` to each route group |

---

## What Does NOT Change

- `POST /api/login` already has `throttle:5,1` (per IP) — leave as-is
- No new middleware classes created — uses Laravel built-in `throttle`
- No model, service, or controller changes

---

## Testing

- `php artisan test` — existing suite must still pass
- Manual: hit `GET /api/sync/transactions` 11 times rapidly → 11th returns 429
