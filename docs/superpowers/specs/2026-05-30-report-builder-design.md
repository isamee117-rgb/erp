# Report Builder — Design Spec
**Date:** 2026-05-30  
**Status:** Approved  

---

## 1. Problem

The existing P&L and Balance Sheet reports auto-group accounts by `chart_of_accounts.type` and `sub_type`. This means the report structure is driven by how accounts were labelled in the COA — there is no way for a user to explicitly say "these specific accounts make up my Sales Revenue line" or "these accounts belong in Current Assets". The reports work but lack user-controlled structure and often group accounts in unexpected ways.

---

## 2. Goal

Build a **Report Builder** — a dedicated Settings page where the user maps their Chart of Accounts to fixed, predefined lines in the P&L and Balance Sheet. Once configured, running either report uses those mappings to produce a clean, structured output.

---

## 3. Decisions Made

| Question | Decision |
|---|---|
| Where does the builder UI live? | Dedicated Settings page ("Report Builder"), accessible from sidebar |
| Are report lines fixed or user-definable? | Fixed / predefined — no adding or renaming sections |
| Storage approach | New table `report_line_mappings` (Approach A) |
| Fallback when no mappings saved? | Fall back to current sub_type-based logic |
| Unmapped accounts | Shown as a warning in both the builder and at the bottom of the report |
| Multi-account per line? | Yes — one line can map to many accounts |
| One account in two lines? | No — unique constraint `(company_id, report_type, account_id)` |

---

## 4. Predefined Report Lines

### Profit & Loss (`report_type = 'profit_loss'`)

| `line_key` | Display Label | Behaviour |
|---|---|---|
| `sales_revenue` | Sales Revenue | Sum of mapped accounts (credit normal) |
| `sales_returns` | Less: Sales Returns | **Auto** — pulled from `sale_returns` table (no mapping needed) |
| _(calculated)_ | = Net Revenue | sales_revenue − sales_returns |
| `cogs` | Cost of Goods Sold | Sum of mapped accounts (debit normal) |
| _(calculated)_ | = Gross Profit | Net Revenue − COGS |
| `operating_expenses` | Operating Expenses | Sum of mapped accounts (debit normal) |
| _(calculated)_ | = Net Profit / Loss | Gross Profit − Operating Expenses |

### Balance Sheet (`report_type = 'balance_sheet'`)

| `line_key` | Display Label | Behaviour |
|---|---|---|
| `current_assets` | Current Assets | Sum of mapped accounts (debit normal + opening balance) |
| `fixed_assets` | Fixed Assets | Sum of mapped accounts (debit normal + opening balance) |
| `other_assets` | Other Assets | Sum of mapped accounts (debit normal + opening balance) |
| _(calculated)_ | = Total Assets | Sum of all asset lines |
| `current_liabilities` | Current Liabilities | Sum of mapped accounts (credit normal + opening balance) |
| `long_term_liabilities` | Long-term Liabilities | Sum of mapped accounts (credit normal + opening balance) |
| _(calculated)_ | = Total Liabilities | Sum of all liability lines |
| `owners_equity` | Owner's Equity | Sum of mapped accounts (credit normal + opening balance) |
| `retained_earnings` | Retained Earnings | **Auto** — net P&L from inception to as_of date |
| `opening_balance_equity` | Opening Balance Equity | **Auto** — net of all opening balances |
| _(calculated)_ | = Total Equity | owners_equity + retained_earnings + opening_balance_equity |
| _(calculated)_ | = Total Liab + Equity | Total Liabilities + Total Equity |

---

## 5. Database

### New table: `report_line_mappings`

```
id              string  PK  ('RLM-' + Str::random(9))
company_id      string  FK → companies.id
report_type     enum    'profit_loss' | 'balance_sheet'
line_key        string  max:50 (see predefined keys above)
account_id      string  FK → chart_of_accounts.id
created_at      timestamp
updated_at      timestamp

UNIQUE (company_id, report_type, account_id)
```

Migration file: `database/migrations/2026_05_30_000001_create_report_line_mappings_table.php`

---

## 6. Backend

### New: `app/Models/ReportLineMapping.php`

- `$keyType = 'string'`, `$incrementing = false`
- `$fillable`: `[id, company_id, report_type, line_key, account_id]`
- Relationship: `account()` → `belongsTo(ChartOfAccount::class)`

### New: `app/Http/Controllers/Api/ReportBuilderController.php`

**`GET /api/report-builder/{type}`** (type = `profit_loss` or `balance_sheet`)
- Auth: `auth_user` from middleware; requires `company_id` (not Super Admin)
- Returns: array of all predefined lines for the report type, each with `line_key`, `label`, `accounts` (array of mapped COA account objects — `id`, `code`, `name`)
- Lines with no mappings return `accounts: []`

**`PUT /api/report-builder/{type}`**
- Auth: same
- Request body: `{ "mappings": { "sales_revenue": ["COA-xxx", "COA-yyy"], "cogs": ["COA-zzz"] } }`
- Validation: each account_id must exist in `chart_of_accounts` for this company; an account cannot appear in two lines
- Logic: DB transaction — delete all existing mappings for `(company_id, report_type)`, insert new rows
- Returns: same shape as GET

### Modified: `app/Http/Controllers/Api/ReportController.php`

`profitLoss()` changes:
1. Load `ReportLineMapping` for `(company_id, 'profit_loss')`, group by `line_key`
2. If mappings exist → query `journal_entry_lines` for only the mapped account IDs per line key
3. If no mappings → fall back to current sub_type logic (unchanged)
4. Identify Revenue/Expense accounts in COA not in any mapping → return as `unmappedAccounts` array

`balanceSheet()` changes — same pattern for `(company_id, 'balance_sheet')`.

### New Routes

```php
// routes/api.php (authenticated group)
Route::get('/report-builder/{type}',  [ReportBuilderController::class, 'index']);
Route::put('/report-builder/{type}',  [ReportBuilderController::class, 'update']);
```

### New Web Route

```php
// routes/web.php
Route::get('/report-builder', fn() => view('pages.report-builder'));
```

---

## 7. Frontend

### New: `resources/views/pages/report-builder.blade.php`

- Extends `layouts.app`
- Contains the page shell: tab bar (P&L / Balance Sheet), info banner, mapping table, unmapped warning, Save button
- No inline JS — all logic in `public/js/pages/report-builder.js`
- Loads Tom Select from `/dist/libs/tom-select/`

### New: `public/js/pages/report-builder.js`

Responsibilities:
- `window.ERP.onReady` → calls `loadReportBuilder('profit_loss')`
- `loadReportBuilder(type)` → calls `ERP.api.getReportBuilder(type)`, renders the mapping table
- Each row renders a Tom Select multi-select dropdown populated with all COA accounts for the company (filtered to relevant type: Revenue/Expense for P&L, Asset/Liability/Equity for BS)
- Already-mapped accounts are pre-selected in the dropdowns
- Tom Select `onChange` → marks form as dirty (enables Save button)
- `saveMapping(type)` → collects selections per line_key, calls `ERP.api.updateReportBuilder(type, mappings)`, shows success toast
- Tab switch (P&L ↔ Balance Sheet) → saves current tab if dirty, then loads the other

### New API methods in `public/js/api.js`

```js
ERP.api.getReportBuilder = function(type) { /* GET /api/report-builder/{type} */ }
ERP.api.updateReportBuilder = function(type, mappings) { /* PUT /api/report-builder/{type} */ }
```

### Modified: `resources/views/layouts/app.blade.php`

Add "Report Builder" link in the Settings section of the sidebar (near Chart of Accounts).

### Modified: Report Output (P&L and Balance Sheet panels in `reports.blade.php`)

When the report API returns `unmappedAccounts` (non-empty array), render a warning row at the bottom of the report table listing the account codes and names.

---

## 8. UX Details

- **Tom Select config:** `maxItems: null` (unlimited), `placeholder: 'Select accounts…'`, search by code or name
- **Account display in dropdown:** `{code} - {name}` (e.g. `4100 - Sales Revenue`)
- **Dirty state:** Save button is disabled until a mapping changes; re-disables after a successful save
- **Reset button:** Clears all mappings for the current tab (with a confirmation prompt), re-enables Save
- **Unmapped warning:** Orange banner listing account codes — disappears once all relevant accounts are mapped

---

## 9. Files Changed

| File | Change |
|---|---|
| `database/migrations/2026_05_30_000001_create_report_line_mappings_table.php` | New |
| `app/Models/ReportLineMapping.php` | New |
| `app/Http/Controllers/Api/ReportBuilderController.php` | New |
| `resources/views/pages/report-builder.blade.php` | New |
| `public/js/pages/report-builder.js` | New |
| `app/Http/Controllers/Api/ReportController.php` | Modified |
| `public/js/api.js` | Modified — 2 new API methods |
| `routes/api.php` | Modified — 2 new routes |
| `routes/web.php` | Modified — 1 new route |
| `resources/views/layouts/app.blade.php` | Modified — sidebar link |

---

## 10. Out of Scope

- Adding or renaming report lines (fixed predefined lines only)
- Report Builder for other report types (Sales, Purchase, etc.)
- Exporting the mapping configuration
- Per-user permission gating on the Report Builder page (Company Admin access assumed)
