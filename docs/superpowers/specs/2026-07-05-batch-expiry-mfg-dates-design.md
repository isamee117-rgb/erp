# Batch-Level Expiry & Manufacturing Date Tracking — Design

**Date:** 2026-07-05
**Status:** Approved
**Scope:** Capture + alerts only (Phase 1). No batch-wise stock deduction, no FEFO, no POS changes.

## Problem

Companies dealing in perishables (grocery, pharmacy) need to record expiry and
manufacturing dates when goods are received, and see which stock is expired or
expiring soon. The feature must be controlled from system Setup: each company
enables Expiry Date tracking and/or Manufacturing Date tracking independently.
When both are off, nothing in the app changes.

The existing product-level `expiry_date` dynamic field (one date per product)
is unrelated to this feature and stays untouched. This feature tracks dates
per **batch** — each purchase receive line.

## Decisions Made

| Question | Decision |
|----------|----------|
| Date level | Batch/purchase level (per GRN line), not product master |
| Sales-side behavior | Capture + alerts only; stock stays aggregate; POS unchanged |
| Batch number | Yes — Batch/Lot No captured alongside the dates |
| Alerts surface | Expiry Report page + dashboard summary card |
| Expiring-soon threshold | Configurable in setup, default 30 days |
| Storage | Nullable columns on `purchase_receive_items` (no new table) |
| Field requiredness | Optional even when toggles are ON (nullable) |

## 1. Setup Toggles (Settings)

New **"Inventory Dates"** card on the Settings page, following the existing
`job_card_mode` toggle pattern:

- **Expiry Date tracking** switch → `settings` key `expiry_date_enabled` (`'1'`/`'0'`)
- **Manufacturing Date tracking** switch → `settings` key `mfg_date_enabled` (`'1'`/`'0'`)
- **Expiry alert days** number input → `settings` key `expiry_alert_days` (default `'30'`); visible only when expiry tracking is ON

Backend: `PUT /api/settings/inventory-dates` handled by
`SettingsController::updateInventoryDates()` using `Setting::updateOrCreate`
scoped to `auth_user->company_id` (same shape as `updateJobCardMode`).
Accepts `expiryDateEnabled` (bool), `mfgDateEnabled` (bool),
`expiryAlertDays` (int, 1–365).

Frontend: `ERP.state.expiryDateEnabled`, `ERP.state.mfgDateEnabled`,
`ERP.state.expiryAlertDays` — loaded through the same sync/core flow that
populates `jobCardMode`. New `ERP.api.updateInventoryDates()` wrapper in
`public/js/api.js`; toggle wiring in `public/js/pages/settings.js`.

## 2. GRN Capture (Purchase Receive)

**Migration:** add three nullable columns to `purchase_receive_items`:

```
batch_no      VARCHAR(255) NULL
mfg_date      DATE NULL
expiry_date   DATE NULL
```

`down()` drops the three columns.

**Receive modal** (`public/js/pages/purchases.js`, `openReceiveModal` /
`submitReceive`): when either toggle is ON, each item row gains a **Batch No**
text input; a **Mfg Date** input appears only when mfg tracking is ON; an
**Expiry Date** input appears only when expiry tracking is ON. With both
toggles OFF the modal renders exactly as today. Values are sent per item as
`batchNo`, `mfgDate`, `expiryDate` in the existing
`partialReceivePurchaseOrder` payload.

**Backend:**
- `ReceivePurchaseOrderRequest`: `items.*.batchNo` → `nullable|string|max:255`;
  `items.*.mfgDate` → `nullable|date`; `items.*.expiryDate` →
  `nullable|date|after:items.*.mfgDate` when both are present (expiry must be
  after mfg). Fields are always accepted regardless of toggle state — the
  toggles control UI visibility, not API validation.
- `PurchaseService::receiveOrder()`: persist the three fields on
  `PurchaseReceiveItem::create`.
- `PurchaseReceiveItem` model: add to `$fillable`, cast dates.
- `PurchaseReceiveItemResource`: output `batchNo`, `mfgDate`, `expiryDate`.

## 3. Expiry Report

New **"Expiry Report"** in the Reports section, visible only when expiry
tracking is ON.

- Endpoint: `GET /api/reports/expiry` — paginated, following the existing
  paginated report endpoint pattern (recent detailed sales/purchase reports).
- Row source: `purchase_receive_items` with non-null `expiry_date`, joined to
  `products` and `purchase_receives`, scoped by `company_id`.
- Columns: Product, Batch No, Receive Date, Mfg Date, Expiry Date,
  Received Qty, Status.
- Status classification (server-side, relative to today):
  - **Expired** — `expiry_date < today`
  - **Expiring Soon** — `today <= expiry_date <= today + expiry_alert_days`
  - **OK** — otherwise
- Filter: by status (all / expired / expiring soon / ok).

**Known limitation (documented in UI copy):** quantities shown are *received*
quantities. Because Phase 1 does not deduct stock batch-wise, a listed batch
may already be partially or fully sold. Batch-wise remaining stock is Phase 2
(FEFO) territory.

## 4. Dashboard Card

When expiry tracking is ON, the dashboard shows a card with **Expired** and
**Expiring Soon** batch counts. Data from `GET /api/reports/expiry-summary`
returning `{ "expired": n, "expiringSoon": n }` using the same classification
and company scoping as the report.

## 5. Out of Scope

- Batch-wise stock deduction, FEFO consumption, blocking expired sales
- Batch selection at POS
- Changes to the product-level `expiry_date` dynamic field
- Changes to purchase returns / sale returns (returns do not reference batches)

## 6. Error Handling

- Invalid dates or expiry-before-mfg → 422 via Form Request validation.
- Missing/invalid `expiryAlertDays` on settings save → 422 (must be int 1–365).
- Report endpoints return empty data (not errors) when no batches have dates.

## 7. Testing

Feature tests (PHPUnit, `#[Test]` attributes, on `erppos_test`):

1. Settings: `PUT /api/settings/inventory-dates` persists all three keys;
   values survive round-trip through the sync/core payload.
2. Receive: receiving a PO with `batchNo`/`mfgDate`/`expiryDate` stores them
   (`assertDatabaseHas` on `purchase_receive_items`); receiving without them
   still works (nullable).
3. Validation: expiry date before mfg date → 422.
4. Report: batches classify correctly as expired / expiring soon / ok given a
   configured threshold; company scoping holds (other company's batches never
   appear).
5. Summary: counts match report classification.
