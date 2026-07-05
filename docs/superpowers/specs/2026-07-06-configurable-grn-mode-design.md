# Configurable GRN (Goods Receipt) Mode — Design

**Date:** 2026-07-06
**Status:** Approved
**Scope:** Make the GRN/receive step configurable per company. When enabled, the
current PO → Receive flow is used. When disabled, creating a PO immediately
receives the full ordered quantity and adds it to inventory (no GRN step).

## Problem

Some companies want a two-step purchase flow (create PO, then receive goods via
a GRN). Others want a single step — creating the PO should directly add stock,
no separate receipt. This must be a per-company setting in system Setup,
following the existing toggle pattern (`job_card_mode`, `expiry_date_enabled`).

## Decisions Made

| Question | Decision |
|----------|----------|
| Setting scope | Per-company `settings` key `grn_enabled` (`'1'`/`'0'`) |
| Default | `'1'` (GRN ON) — existing companies unchanged |
| GRN OFF implementation | Auto-receive full quantity via existing `receiveOrder()` logic (no duplication) |
| PO status when GRN OFF | `Received` (no new status label) |
| Batch/Mfg/Expiry capture when GRN OFF | Captured on the PO create form (when expiry/mfg tracking is ON) |
| Journal posting when GRN OFF | Posted at PO create (mirror of the `receive()` path) |
| Existing draft POs | Unaffected — setting applies to new POs only |
| Returns | Unaffected (read `received_quantity`, which auto-receive fills) |

## 1. Setup Toggle (Settings)

New **"Goods Receipt (GRN)"** card on the Settings page, following the existing
`job_card_mode` toggle pattern:

- One switch → `settings` key `grn_enabled` (`'1'`/`'0'`), default `'1'`.

**Backend:** `PUT /api/settings/grn-mode` handled by
`SettingsController::updateGrnMode()` using `Setting::updateOrCreate` scoped to
`auth_user->company_id` (same shape as `updateJobCardMode`). Accepts
`grnEnabled` (bool). Returns `{ success: true, grnEnabled: bool }`.

**Sync:** add `grn_enabled` to the `whereIn` keys in `SyncService::getCoreData()`
and expose `grnEnabled` (bool, default `true`) in the core payload.

**Frontend:** `ERP.state.grnEnabled` (default `true` in `app.js`); new
`ERP.api.updateGrnMode()` wrapper in `public/js/api.js`; toggle wiring in
`public/js/pages/settings.js`.

**Meaning:**
- **ON** → current flow: PO created as `Draft`; GRN/Receive step adds inventory.
- **OFF** → PO creation immediately receives full quantity; inventory added;
  status `Received`; no receive step shown.

## 2. Backend — Auto-receive on PO create

In `PurchaseService::createOrder()`, after the PO and its items are created,
read the company's `grn_enabled` setting. If OFF:

- Build receive items from all PO lines at full ordered quantity (same shape as
  `buildDefaultReceiveItems`), carrying `batchNo`/`mfgDate`/`expiryDate` from the
  create payload per line.
- Call the existing `receiveOrder()` path so stock, moving-average/FIFO cost
  layers, inventory ledger, and vendor balance all update through the one
  tested code path (no logic duplication). PO ends at status `Received`.

If ON, `createOrder()` behaves exactly as today (status `Draft`, no stock
change).

**Journal posting:** `PurchaseController::store()` currently posts no journal
(only `receive()` does). When the PO was auto-received (GRN OFF), `store()`
posts the purchase-receive journal for the created receive, mirroring the
try/catch in `receive()` (log + non-fatal `warning`). When GRN is ON, `store()`
stays exactly as today.

## 3. Frontend — PO create form (`purchases.js`)

- When `grnEnabled === false` **and** expiry/mfg tracking is ON, PO item rows
  show Batch No / Mfg Date / Expiry Date inputs (same fields the receive modal
  uses today). Values are sent per item in the `createPurchaseOrder` payload.
- When `grnEnabled === false`, the PO list hides the "Receive Goods" /
  "Receive More" button (PO arrives as `Received`).
- When `grnEnabled === true`, the page renders exactly as today.

## 4. Request Validation

`StorePurchaseOrderRequest`: add optional per-item fields (always accepted; the
toggle only controls UI visibility, not API acceptance):

- `items.*.batchNo` → `nullable|string|max:255`
- `items.*.mfgDate` → `nullable|date`
- `items.*.expiryDate` → `nullable|date` (after `mfgDate` when both present)

Same rules already present in `ReceivePurchaseOrderRequest`.

## 5. Error Handling

- Invalid dates or expiry-before-mfg → 422 via Form Request validation.
- Journal posting failure on auto-receive → logged, returned as non-fatal
  `warning` on the response (same as the existing `receive()` behaviour); the
  PO and stock changes still commit.

## 6. Out of Scope

- No changes to POS, sales, or sale returns.
- No changes to the expiry report / dashboard card (they read
  `purchase_receive_items`, which auto-receive still populates).
- No new PO status label; GRN-OFF POs use the existing `Received` status.
- No retroactive change to existing draft POs.

## 7. Testing

Feature tests (PHPUnit, `#[Test]` attributes, on `erppos_test`):

1. Settings: `PUT /api/settings/grn-mode` persists `grn_enabled`; value
   round-trips through the sync/core payload as `grnEnabled`.
2. GRN OFF: creating a PO sets status `Received`, increases product stock,
   writes an inventory ledger row + cost layer, and updates vendor balance.
3. GRN ON (default): creating a PO stays `Draft` with no stock change
   (current behaviour intact).
4. GRN OFF with expiry tracking: `batchNo`/`mfgDate`/`expiryDate` sent on PO
   create are stored on `purchase_receive_items` (`assertDatabaseHas`).
5. Company scoping: one company's `grn_enabled` does not affect another's.
