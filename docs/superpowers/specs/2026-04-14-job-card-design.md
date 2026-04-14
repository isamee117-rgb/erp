# Job Card Feature — Design Spec
**Date:** 2026-04-14
**Branch:** feature/job-card
**Status:** Approved — ready for implementation

---

## 1. Overview

Introduce a **Job Card** module for the Automobile industry vertical.
Job Cards replace the POS Terminal for workshops — they allow a mechanic or service advisor to open multiple running bills simultaneously, one per vehicle, and finalize each when work is complete.

### Core Principles
- Job Cards are stored **separately** from Sale Orders (`job_cards` table, not `sale_orders`)
- Multiple job cards can be open **at the same time** (tab-based UI)
- POS and Job Card mode are mutually exclusive, controlled by a company-level setting
- **No inline CSS or JS** anywhere — all styles in `public/css/app.css`, all logic in `public/js/pages/job-card.js`

---

## 2. Settings Toggle

A single key-value setting controls which module is active:

| Setting Key | Value | Behavior |
|-------------|-------|----------|
| `job_card_mode` | `0` | POS Terminal shown in sidebar, Job Card hidden |
| `job_card_mode` | `1` | Job Card shown in sidebar, POS Terminal hidden |

- Stored in the existing `settings` table
- Exposed via `/api/sync/core` (already handled by `SyncService`)
- Toggle UI added to the **Settings page** under a new "Module Settings" section
- API: `PUT /api/settings/job-card-mode`

---

## 3. Database Schema

### 3.1 `job_cards` table

| Column | Type | Notes |
|--------|------|-------|
| `id` | varchar PK | `'JC-' . Str::random(9)` |
| `company_id` | varchar FK | multi-tenant scope |
| `job_card_no` | varchar | auto-numbered via `DocumentSequence` (key: `job_card`) |
| `status` | enum `open\|closed` | `open` = draft, `closed` = finalized |
| `customer_id` | varchar FK nullable | links to `parties` |
| `customer_name` | varchar nullable | snapshot at creation |
| `phone` | varchar nullable | snapshot at creation |
| `vehicle_reg_number` | varchar nullable | per-job-card (one customer, many vehicles) |
| `vin_chassis_number` | varchar nullable | |
| `engine_number` | varchar nullable | |
| `make_model_year` | varchar nullable | |
| `lift_number` | varchar nullable | workshop bay/hoist |
| `current_odometer` | decimal(10,2) nullable | on finalize → updates `parties.last_odometer_reading` |
| `payment_method` | enum `Cash\|Credit` | default `Cash` |
| `parts_subtotal` | decimal(12,2) | sum of part line items |
| `services_subtotal` | decimal(12,2) | sum of service line items |
| `subtotal` | decimal(12,2) | parts + services |
| `discount` | decimal(12,2) | absolute amount after conversion |
| `discount_type` | enum `fixed\|percent` | default `fixed` |
| `discount_value` | decimal(10,2) | raw input (% or fixed) |
| `grand_total` | decimal(12,2) | subtotal − discount |
| `created_by` | varchar FK | user id |
| `closed_at` | timestamp nullable | set on finalize |
| `created_at` / `updated_at` | timestamps | |

### 3.2 `job_card_items` table

| Column | Type | Notes |
|--------|------|-------|
| `id` | varchar PK | `'JCI-' . Str::random(9)` |
| `job_card_id` | varchar FK | |
| `item_type` | enum `part\|service` | `part` = Product type, `service` = Service type |
| `product_id` | varchar FK | |
| `product_name` | varchar | snapshot |
| `quantity` | decimal(10,3) | |
| `unit_price` | decimal(12,2) | |
| `discount` | decimal(12,2) | line-level discount (fixed) |
| `total_line_price` | decimal(12,2) | (unit_price × qty) − discount |
| `created_at` / `updated_at` | timestamps | |

---

## 4. Backend Architecture

### 4.1 New Files

| File | Purpose |
|------|---------|
| `database/migrations/2026_04_14_000001_create_job_cards_table.php` | Schema migration |
| `app/Models/JobCard.php` | Eloquent model |
| `app/Models/JobCardItem.php` | Eloquent model |
| `app/Services/JobCardService.php` | All business logic |
| `app/Http/Controllers/Api/JobCardController.php` | Thin controller |
| `app/Http/Resources/JobCardResource.php` | camelCase JSON |
| `app/Http/Resources/JobCardItemResource.php` | camelCase JSON |
| `app/Http/Requests/StoreJobCardRequest.php` | Create validation |
| `app/Http/Requests/UpdateJobCardRequest.php` | Header update validation |

### 4.2 API Endpoints

All routes under `ApiTokenAuth` middleware.

| Method | Route | Controller Method | Notes |
|--------|-------|-------------------|-------|
| `GET` | `/api/job-cards` | `index` | Open cards for company |
| `POST` | `/api/job-cards` | `store` | Create new open card |
| `PUT` | `/api/job-cards/{id}` | `update` | Auto-save header fields |
| `POST` | `/api/job-cards/{id}/items` | `addItem` | Add part or service line |
| `PUT` | `/api/job-cards/{id}/items/{itemId}` | `updateItem` | Update qty/price/discount |
| `DELETE` | `/api/job-cards/{id}/items/{itemId}` | `removeItem` | Remove line item |
| `POST` | `/api/job-cards/{id}/finalize` | `finalize` | Close + deduct stock + update odometer |
| `DELETE` | `/api/job-cards/{id}` | `destroy` | Discard open card |
| `GET` | `/api/job-cards/history` | `history` | Paginated closed cards |
| `PUT` | `/api/settings/job-card-mode` | `SettingsController@updateJobCardMode` | Toggle setting |

### 4.3 JobCardService Methods

```
create(User $user, array $data): JobCard
  - generates job_card_no via DocumentSequenceService (key: 'job_card')
  - sets status = open, company_id, created_by

updateHeader(JobCard $card, array $data): JobCard
  - updates vehicle/customer fields
  - calls recalculateTotals()

addItem(JobCard $card, array $data): JobCardItem
  - validates product exists and belongs to company
  - validates product->type matches item_type ('Product'→part, 'Service'→service)
  - creates JobCardItem
  - calls recalculateTotals()

updateItem(JobCard $card, string $itemId, array $data): JobCardItem
  - updates qty/price/discount
  - calls recalculateTotals()

removeItem(JobCard $card, string $itemId): void
  - deletes JobCardItem
  - calls recalculateTotals()

finalize(JobCard $card, User $user): JobCard  [DB transaction]
  - deducts stock for each part item via InventoryCostingService
  - updates Party.last_odometer_reading if customer_id and current_odometer set
  - sets status = closed, closed_at = now()
  - returns finalized card

recalculateTotals(JobCard $card): void  [private]
  - parts_subtotal = sum of part items total_line_price
  - services_subtotal = sum of service items total_line_price
  - subtotal = parts_subtotal + services_subtotal
  - discount = discount_type=percent ? subtotal*(discount_value/100) : discount_value
  - grand_total = subtotal - discount
  - saves card
```

### 4.4 SyncService Addition

`getTransactionData()` includes two job card datasets:
```php
// Open cards — fully loaded with items (drives the tab UI)
'jobCards' => JobCardResource::collection(
    JobCard::with('items')
           ->where('company_id', $companyId)
           ->where('status', 'open')
           ->get()
),
// Recent closed cards — header only, no items (drives vehicle prefill lookup)
'jobCardHistory' => JobCardResource::collection(
    JobCard::where('company_id', $companyId)
           ->where('status', 'closed')
           ->orderByDesc('closed_at')
           ->limit(100)
           ->get()
),
```

The history **list page** (`GET /api/job-cards/history`) is called directly from the frontend with pagination — it does not go through sync.

### 4.5 DocumentSequence

Add `job_card` as a new sequence key in `DocumentSequenceService`. Default format: `JC-0001`.

---

## 5. Frontend Architecture

### 5.1 New Files

| File | Purpose |
|------|---------|
| `resources/views/pages/job-card.blade.php` | Page Blade template (no inline CSS/JS) |
| `public/js/pages/job-card.js` | All page logic |

### 5.2 Web Route

```php
Route::get('/job-card', function () {
    return view('pages.job-card');
});
```

### 5.3 Sidebar Toggle

In `app.blade.php`, both nav items present in HTML:
```html
<!-- POS nav item: data-nav-mode="pos" -->
<!-- Job Card nav item: data-nav-mode="job-card" -->
```
On `ERP.onReady`, JS reads `ERP.state.settings.jobCardMode`:
- `1` → show job-card nav, hide pos nav
- `0` → show pos nav, hide job-card nav (default)

### 5.4 Page Layout

```
┌─────────────────────────────────────────────────┐
│ [JC-001 · ABC-123] [JC-002 · XYZ-999]  [+ New] │  ← Tab Bar
├──────────────────────────────┬──────────────────┤
│ HEADER FIELDS                │ Parts Subtotal   │
│  Customer search             │ Services Total   │
│  Vehicle Reg / VIN /         │ Subtotal         │
│  Engine / Make-Model-Year /  │ Discount         │
│  Lift No / Odometer          │ Grand Total      │
│                              │                  │
│ PARTS  ──────────────────    │ Payment Method   │
│  [product search + add]      │                  │
│  table of parts              │ [Finalize]       │
│                              │ [Print]          │
│ SERVICES ────────────────    │ [Discard]        │
│  [service search + add]      │                  │
│  table of services           │                  │
└──────────────────────────────┴──────────────────┘
│ HISTORY TAB (closed cards, paginated table)      │
└──────────────────────────────────────────────────┘
```

### 5.5 State Management

```js
// In job-card.js
var jcTabs = [];          // array of open job card objects (from ERP.state.jobCards)
var jcActiveId = null;    // id of currently visible tab

// Tab operations
function jcNewTab()          // POST /api/job-cards → push to jcTabs, set active
function jcSwitchTab(id)     // set jcActiveId, re-render panel
function jcCloseTab(id)      // remove from jcTabs (card stays in backend)

// Auto-save (debounced, 500ms)
function jcSaveHeader()      // PUT /api/job-cards/{id}
function jcAddItem(type)     // POST /api/job-cards/{id}/items
function jcUpdateItem(itemId)// PUT /api/job-cards/{id}/items/{itemId}
function jcRemoveItem(itemId)// DELETE /api/job-cards/{id}/items/{itemId}

// Actions
function jcFinalize()        // POST /api/job-cards/{id}/finalize → remove tab, show success
function jcDiscard()         // DELETE /api/job-cards/{id} → remove tab
function jcPrint()           // window.print() — CSS handles layout
```

### 5.6 Customer Lookup Flow

1. User types in customer search field (vehicle reg / phone / name)
2. JS filters `ERP.state.parties` client-side — no extra API call
3. **Match found:** auto-fills customer fields. Checks `jcTabs` history (closed cards from `ERP.state.jobCardHistory`) to pre-fill last known vehicle fields for that customer
4. **No match:** "Create New Customer" button appears → inline mini-form (name + phone) → `POST /api/parties` → party added to state → set on job card

### 5.7 Product Search (Parts / Services)

- Parts search: filters `ERP.state.products` where `type === 'Product'`
- Services search: filters `ERP.state.products` where `type === 'Service'`
- Both use the same searchable dropdown pattern already in `pos.js`

### 5.8 Print Layout

`window.print()` triggered by the Print button.
CSS in `app.css` under `@media print`:
- Hide: sidebar, tab bar, action buttons, header nav
- Show: clean company header, customer block, vehicle block, parts table, services table, totals block

### 5.9 Strict Rules Enforced
- Zero `style=""` attributes in `job-card.blade.php`
- Zero `onclick=""` / `onchange=""` / `onsubmit=""` in `job-card.blade.php`
- No `<style>` or `<script>` blocks in `job-card.blade.php`
- All event listeners attached via `addEventListener` in `job-card.js`
- All custom styles added to `public/css/app.css` under `.job-card-*` namespace

---

## 6. Settings Page Changes

Add a **"Module Settings"** section to `settings.js` and `settings.blade.php`:
- Toggle switch: "Job Card Mode" (replaces POS Terminal with Job Card in sidebar)
- On toggle → `PUT /api/settings/job-card-mode` → update `ERP.state.settings.jobCardMode` → re-evaluate sidebar visibility

---

## 7. Impacted Existing Files

| File | Change |
|------|--------|
| `routes/api.php` | Add job card routes + settings/job-card-mode route |
| `routes/web.php` | Add `/job-card` web route |
| `resources/views/layouts/app.blade.php` | Add Job Card nav item with mode toggle logic hook |
| `app/Services/SyncService.php` | Add open job cards to `getTransactionData()` |
| `app/Http/Controllers/Api/SettingsController.php` | Add `updateJobCardMode()` |
| `public/css/app.css` | Add `.job-card-*` styles + `@media print` rules |
| `public/js/app.js` | Sidebar show/hide logic for `jobCardMode` |
| `public/js/pages/settings.js` | Add Module Settings toggle UI |

---

## 8. Out of Scope (Not in this feature)

- Job Card → Sale Order conversion (stored separately by design)
- Parts warranty tracking
- Technician assignment per job
- Job Card editing after finalization
- Mobile/PWA offline support
