# Race Condition Fixes — LeanERP

**Date:** 2026-05-12
**Goal:** Eliminate stock and balance corruption under concurrent POS load (350 users).

---

## Problem

Three read-modify-write sequences run without DB-level locks:

1. **`current_stock`** — two cashiers selling the same item simultaneously both read stock = 10, both subtract 5, both write 5. Result: stock should be 0 but is 5.
2. **`current_balance`** — two concurrent payments to the same party both read balance = 100, both add 50, both write 150. Result: balance should be 200 but is 150.
3. **`document_sequences`** — already fixed (`lockForUpdate()` + `DB::transaction()` in `DocumentSequenceService::getNextNumber()`). No changes needed.

---

## Solution

**Pessimistic locking** (`SELECT ... FOR UPDATE`) inside `DB::transaction()` on every read-modify-write of `current_stock` and `current_balance`.

Each service method that modifies stock or balance becomes one atomic transaction. The `getNextNumber()` call moves inside this outer transaction — it already uses `DB::transaction()` internally, so it becomes a nested transaction (MySQL savepoint). If the outer transaction rolls back (e.g. product not found mid-sale), the sequence number rolls back too — no gaps in invoice numbering.

---

## Files Changed

| File | Methods | Change |
|------|---------|--------|
| `app/Services/SaleService.php` | `createSale()`, `createReturn()` | Wrap entire method body in `DB::transaction()`. Change `Product::find()` and `Party::find()` (for balance) to `->lockForUpdate()->first()`. |
| `app/Services/PurchaseService.php` | `receiveOrder()`, `createReturn()` | Same pattern. |
| `app/Http/Controllers/Api/PaymentController.php` | `store()`, `destroy()` | Wrap Payment creation + Party balance update in `DB::transaction()`. Change `Party::find()` to `->lockForUpdate()->first()`. |

---

## Locking Pattern

```php
// Read-modify-write — safe version
DB::transaction(function () use (...) {
    $product = Product::where('id', $productId)->lockForUpdate()->first();
    $product->current_stock -= $baseQty;
    $product->save();
});
```

`lockForUpdate()` acquires an exclusive row lock. Concurrent transactions that try to lock the same row will wait until the first transaction commits or rolls back. MySQL InnoDB handles this natively.

---

## Deadlock Handling

If two concurrent sales lock products in different orders, MySQL InnoDB detects the deadlock and rolls back one transaction with error 1213. The rolled-back transaction throws `\Illuminate\Database\QueryException` which propagates as a 500 to the client. The client (POS app) retries the sale. No retry logic is added to the services — the DB handles detection and the client handles retry.

---

## What Does NOT Change

- `DocumentSequenceService` — already correct, no changes
- No schema migrations needed
- No new service classes
- No model changes
