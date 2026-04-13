# Dynamic Fields — Design Spec
**Date:** 2026-04-13
**Branch:** feature/customer-category-pricing (to be continued on new branch)
**Status:** Approved

---

## 1. Overview

A feature that allows company admins to enable/disable a predefined list of industry-specific fields on Product and Customer (Party) master records. Enabled fields appear inline in the existing Product and Party modals alongside standard fields. Field data is queryable for filtering, reporting, and search.

### Key Constraints
- Field list is **fixed and predefined** — admins cannot create custom fields. New fields are added by developers only.
- Industry labels on fields are **hints only** — not enforced. Any company can enable any field.
- A field **cannot be disabled** if any record in the company has a non-null value for it.
- No transactions are deleted in this system, so "data exists = cannot disable" is a safe and permanent rule.

---

## 2. Predefined Field Registry

Stored as a hardcoded PHP array in `app/Config/DynamicFields.php`. No DB table for field definitions.

### Product Fields (16 total)

| key | label | type | industry hint | options |
|-----|-------|------|---------------|---------|
| `brand_name` | Brand Name | text | retail | — |
| `size_color_style` | Size/Color/Style (Variants) | text | retail | — |
| `bin_shelf_location` | Bin/Shelf Location | text | retail, pharmacy, automobile | — |
| `expiry_date` | Expiry Date | date | grocery/pharmacy | — |
| `batch_lot_number` | Batch/Lot Number | text | grocery/pharmacy | — |
| `storage_condition` | Storage Condition | dropdown | grocery/pharmacy | Ambient, Chilled, Frozen |
| `drug_composition` | Drug Composition / Generic Name | text | pharmacy | — |
| `schedule_category` | Schedule Category | dropdown | pharmacy | H, H1, X, OTC |
| `manufacturer_name` | Manufacturer Name | text | grocery/pharmacy | — |
| `dosage_form` | Dosage Form | dropdown | pharmacy | Tablet, Syrup, Injection, Capsule |
| `storage_temp_req` | Storage Temperature Requirements | text | grocery/pharmacy | — |
| `part_number` | Part Number (OEM/Aftermarket) | text | automobile | — |
| `vehicle_compatibility` | Vehicle Compatibility (Make/Model/Year) | text | automobile | — |
| `core_charge_flag` | Core Charge/Exchange Item Flag | boolean | automobile | — |
| `warranty_period` | Warranty Period | text | automobile | — |
| `technical_specs` | Technical Specifications | textarea | automobile | — |

### Customer Fields (4 total)

| key | label | type | industry hint | options |
|-----|-------|------|---------------|---------|
| `vehicle_reg_number` | Vehicle Registration Number (Plate) | text | automobile | — |
| `vin_chassis_number` | VIN/Chassis Number | text | automobile | — |
| `engine_number` | Engine Number | text | automobile | — |
| `last_odometer_reading` | Last Odometer Reading | number | automobile | — |

---

## 3. Database Schema

### Migration 1 — Add columns to `products` table

```sql
brand_name            VARCHAR(255) NULL
size_color_style      VARCHAR(255) NULL
bin_shelf_location    VARCHAR(255) NULL
batch_lot_number      VARCHAR(255) NULL
drug_composition      VARCHAR(255) NULL
manufacturer_name     VARCHAR(255) NULL
storage_temp_req      VARCHAR(255) NULL
part_number           VARCHAR(255) NULL
vehicle_compatibility VARCHAR(255) NULL
warranty_period       VARCHAR(255) NULL
technical_specs       TEXT NULL
expiry_date           DATE NULL
storage_condition     VARCHAR(50) NULL    -- Ambient / Chilled / Frozen
schedule_category     VARCHAR(20) NULL    -- H / H1 / X / OTC
dosage_form           VARCHAR(50) NULL    -- Tablet / Syrup / Injection / Capsule
core_charge_flag      TINYINT(1) NULL
```

### Migration 2 — Add columns to `parties` table

```sql
vehicle_reg_number    VARCHAR(100) NULL
vin_chassis_number    VARCHAR(100) NULL
engine_number         VARCHAR(100) NULL
last_odometer_reading DECIMAL(10,2) NULL
```

### Migration 3 — New `company_field_settings` table

```sql
id            VARCHAR(20)  PRIMARY KEY
company_id    VARCHAR(20)  NOT NULL  FK → companies.id
entity_type   VARCHAR(20)  NOT NULL  -- 'product' | 'customer'
field_key     VARCHAR(50)  NOT NULL
is_enabled    TINYINT(1)   NOT NULL  DEFAULT 0
created_at    TIMESTAMP
updated_at    TIMESTAMP

UNIQUE KEY (company_id, entity_type, field_key)
```

---

## 4. Backend

### 4.1 Field Registry File
**`app/Config/DynamicFields.php`**
- Returns a static array of all 20 field definitions
- Each entry: `key`, `label`, `entity` (`product`|`customer`), `type` (`text`|`date`|`number`|`dropdown`|`boolean`|`textarea`), `options` (array, for dropdowns), `industry_hint` (string, display only)
- Used by backend (validation, response building) and exposed to frontend via sync

### 4.2 Model — `CompanyFieldSetting`
- `app/Models/CompanyFieldSetting.php`
- `$fillable`: `id`, `company_id`, `entity_type`, `field_key`, `is_enabled`
- Scope: `scopeForCompany($query, $companyId)`
- Scope: `scopeEnabled($query)`

### 4.3 Service — `FieldSettingService`
**`app/Services/FieldSettingService.php`**

```
getSettings(company_id)
  → Returns all 20 fields with is_enabled state for this company
  → For fields with no DB row yet, default is_enabled = false

updateSetting(company_id, entity_type, field_key, is_enabled)
  → If disabling: call canDisable() first
  → If canDisable() returns false: throw RuntimeException with record count
  → Upsert row in company_field_settings

canDisable(company_id, entity_type, field_key) → [bool, int $count]
  → entity_type = 'product': query products WHERE company_id = ? AND {field_key} IS NOT NULL AND {field_key} != ''
  → entity_type = 'customer': query parties WHERE company_id = ? AND {field_key} IS NOT NULL AND {field_key} != ''
  → Returns [false, $count] if count > 0
  → Returns [true, 0] if safe to disable
```

### 4.4 Controller — `FieldSettingController`
**`app/Http/Controllers/Api/FieldSettingController.php`**

```
GET  /api/field-settings
  → Returns all fields with enabled state for auth user's company
  → Super Admin guard: if auth_user->company_id is null, return 403

PUT  /api/field-settings/{field_key}
  → Body: { entity_type, is_enabled }
  → Super Admin guard: if auth_user->company_id is null, return 403
  → Validates field_key exists in registry
  → Delegates to FieldSettingService::updateSetting()
  → On RuntimeException: return 422 with error message
```

### 4.5 API Resource — `FieldSettingResource`
Returns: `fieldKey`, `entityType`, `isEnabled`, `label`, `type`, `options`, `industryHint`

### 4.6 Updated Resources
- **`ProductResource`** — add all 16 product field keys to toArray()
- **`PartyResource`** — add all 4 customer field keys to toArray()

### 4.7 Updated Form Requests
- **`StoreProductRequest`** (new — does not exist yet) — create for product store validation, include all 16 dynamic fields as `nullable` rules with type-appropriate validation (`date`, `numeric`, `in:Ambient,Chilled,Frozen`, etc.)
- **`UpdateProductRequest`** (new — does not exist yet) — same rules as StoreProductRequest for update
- **`StorePartyRequest`** (existing) — add 4 customer fields as nullable rules
- **`UpdatePartyRequest`** (existing) — add 4 customer fields as nullable rules
- All dynamic field rules are `nullable` — no field is ever required by the system

### 4.8 Updated `SyncService::getMasterData()`
- Add `fieldSettings` key to sync payload
- Structure includes two parts:
  1. `enabledKeys`: `{ product: ['brand_name', 'expiry_date', ...], customer: ['vehicle_reg_number', ...] }` — only enabled field keys per entity
  2. `definitions`: full array of all 20 field definitions (key, label, type, options, industryHint) — always sent, enables frontend to render correct input types without a separate API call

---

## 5. Frontend

### 5.1 State
`window.ERP.state.fieldSettings` after sync:
```js
{
  enabledKeys: {
    product: ['brand_name', 'expiry_date'],   // enabled field keys
    customer: ['vehicle_reg_number'],          // enabled field keys
  },
  definitions: [                              // full registry — all 20 fields always present
    { key: 'brand_name', label: 'Brand Name', entity: 'product', type: 'text', options: [], industryHint: 'retail' },
    { key: 'expiry_date', label: 'Expiry Date', entity: 'product', type: 'date', options: [], industryHint: 'grocery/pharmacy' },
    // ...
  ]
}
```

### 5.2 Settings Page — Dynamic Fields Tab
- New tab "Dynamic Fields" added to settings page tabs
- Two sections: **Product Fields** and **Customer Fields**
- Each section groups fields by `industry_hint` with a section header
- Each field row:
  - Toggle switch (Bootstrap form-switch)
  - Field label
  - Type badge (small muted text)
  - Industry hint badge
- On toggle OFF attempt:
  - API call → if 422 returned → show error overlay: *"Cannot disable — [N] records have data in this field"*
- On toggle ON: immediate enable, no confirmation needed
- No save button — each toggle is an immediate API call

### 5.3 Product Modal (Inventory Page)
- After standard fields, enabled product fields render inline (same grid/form layout)
- `renderDynamicFields('product', productData)` function builds inputs from `ERP.state.fieldSettings`
- Field type rendering:
  - `text` / `textarea` → `<input type="text">` / `<textarea>`
  - `date` → `<input type="date">`
  - `number` → `<input type="number" step="0.01">`
  - `dropdown` → `<select>` with options from field definition
  - `boolean` → Bootstrap form-check checkbox
- Values pre-filled when editing existing product
- Submitted as part of existing save product API call

### 5.4 Party Modal (Parties Page)
- Same pattern as Product modal
- `renderDynamicFields('customer', partyData)` renders enabled customer fields inline
- Submitted as part of existing save party API call

### 5.5 List Pages — Filtering & Columns
- **Inventory list**: enabled product fields available as optional table columns (toggle via column visibility control)
- **Parties list**: enabled customer fields available as optional columns
- **Filter panels**: enabled fields added as filter inputs (text search, date range, dropdown select)
- Column visibility state stored in `localStorage` per company

---

## 6. Validation Rules Summary

### Disable Field Validation
- Check: `SELECT COUNT(*) FROM products/parties WHERE company_id = ? AND {field_key} IS NOT NULL AND {field_key} != ''`
- If count > 0: return 422 `"Cannot disable — {count} records have data in this field"`
- This check is in `FieldSettingService::canDisable()`

### Input Validation (Form Requests)
| field_key | rule |
|-----------|------|
| `expiry_date` | `nullable\|date` |
| `last_odometer_reading` | `nullable\|numeric\|min:0` |
| `core_charge_flag` | `nullable\|boolean` |
| `storage_condition` | `nullable\|in:Ambient,Chilled,Frozen` |
| `schedule_category` | `nullable\|in:H,H1,X,OTC` |
| `dosage_form` | `nullable\|in:Tablet,Syrup,Injection,Capsule` |
| all others | `nullable\|string\|max:255` (except `technical_specs`: `max:2000`) |

---

## 7. API Routes

```php
Route::get('/field-settings', [FieldSettingController::class, 'index']);
Route::put('/field-settings/{fieldKey}', [FieldSettingController::class, 'update']);
```

Both routes under existing `auth:api` middleware group.

---

## 8. Files to Create

| File | Purpose |
|------|---------|
| `app/Config/DynamicFields.php` | Field registry — all 20 predefined fields |
| `app/Models/CompanyFieldSetting.php` | Eloquent model |
| `app/Services/FieldSettingService.php` | Business logic |
| `app/Http/Controllers/Api/FieldSettingController.php` | API controller |
| `app/Http/Resources/FieldSettingResource.php` | API resource |
| `database/migrations/..._add_dynamic_fields_to_products.php` | Products columns |
| `database/migrations/..._add_dynamic_fields_to_parties.php` | Parties columns |
| `database/migrations/..._create_company_field_settings_table.php` | Settings table |

## 9. Files to Modify

| File | Change |
|------|--------|
| `app/Http/Resources/ProductResource.php` | Add 16 dynamic field keys |
| `app/Http/Resources/PartyResource.php` | Add 4 dynamic field keys |
| `app/Http/Requests/StorePartyRequest.php` | Add customer field validation rules |
| `app/Models/Product.php` | Add dynamic fields to `$fillable` |
| `app/Models/Party.php` | Add dynamic fields to `$fillable` |
| `app/Services/SyncService.php` | Add `fieldSettings` to `getMasterData()` |
| `routes/api.php` | Register new field-settings routes |
| `resources/views/pages/settings.blade.php` | Add Dynamic Fields tab |
| `public/js/pages/settings.js` | Dynamic Fields tab logic |
| `public/js/pages/inventory.js` | Render dynamic fields in product modal, filter/columns |
| `public/js/pages/parties.js` | Render dynamic fields in party modal, filter/columns |
| `public/js/api.js` | Add `getFieldSettings()`, `updateFieldSetting()` API wrappers |
