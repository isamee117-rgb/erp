# Detailed Transaction Reports — Server-Side Pagination + Mandatory Date

**Date:** 2026-06-29
**Status:** Approved (design) — pending implementation plan

## Problem

The six "detailed" transaction reports render client-side from the global
`window.ERP.state` (`state.sales`, `state.purchaseOrders`, `state.salesReturns`,
`state.purchaseReturns`). That state is populated by `GET /api/sync/transactions`,
which serializes the full transaction payload (sales, purchases, payments, ledger,
returns, job cards) for the default window into one JSON response.

For the larger tenant this payload is ~13 MB, which exhausted the container's
128 MB PHP memory limit during JSON serialization → `sync/transactions` returned
500 → `state.sales` never loaded → reports showed nothing. Raising the memory
limit is a stop-gap; the payload grows unbounded with data.

This design removes the reports' dependency on the full-payload sync. Each report
fetches only the rows for an explicitly selected date range, one page at a time.

## Goals

1. **Blank until run** — opening a report panel shows a placeholder, not data.
   Data loads only when the user clicks **Run Report**.
2. **Mandatory date range** — `from` and `to` are required (enforced client- and
   server-side). No run without both.
3. **Range-limited fetch** — only the selected date range is fetched, never more.
4. **Pagination** — results are paginated (default 50 rows/page) via the server.

## Scope

In scope — these six reports change:

| Report | Tile / panel | Run fn (current) |
|--------|--------------|------------------|
| Detailed Sales | `rpt-sales-panel` | `runSalesReport` |
| Detailed Purchase | `rpt-purchase-panel` | `runPurchaseReport` |
| Sales Return | `rpt-salesReturn-panel` | `runSalesReturnReport` |
| Purchase Return | `rpt-purchaseReturn-panel` | `runPurchaseReturnReport` |
| Sales by Customer | `rpt-salesByCustomer-panel` | `runSalesByCustomerReport` |
| Purchase by Vendor | `rpt-purchaseByVendor-panel` | `runPurchaseByVendorReport` |

Out of scope — unchanged: Product / Customer / Vendor list reports (master data,
already small), Profit & Loss, Balance Sheet, Journal report, and the
`sync/transactions` endpoint itself (other pages still use it).

## Backend

### Routes (`routes/api.php`)

Add under the existing authenticated `/api` group that already hosts
`/reports/profit-loss` and `/reports/balance-sheet`:

```
GET /api/reports/detailed-sales
GET /api/reports/detailed-purchase
GET /api/reports/sales-returns
GET /api/reports/purchase-returns
GET /api/reports/sales-by-customer
GET /api/reports/purchase-by-vendor
```

All read-only → rate-limited under the existing `api-reads` limiter.

### Controller — `ReportDataController` (new, thin)

Six methods, one per route. Each: validate via `ReportQueryRequest`, reject Super
Admin (company_id null) with 403 (mirrors `ReportBuilderController`), delegate to
`ReportQueryService`, return `response()->json($result)`. No logic in controller.

### Form Request — `ReportQueryRequest` (new)

```php
'from'     => 'required|date',
'to'       => 'required|date|after_or_equal:from',
'page'     => 'integer|min:1',
'perPage'  => 'integer|min:1|max:200',
'export'   => 'boolean',
// optional, ignored by reports that don't use them:
'customerId' => 'string',
'vendorId'   => 'string',
'paymentMethod' => 'string',
'status'   => 'string',
'search'   => 'string',
```

`from`/`to` being `required` is the server-side enforcement of "date mandatory".

### Service — `ReportQueryService` (new)

One public method per report:
`detailedSales`, `detailedPurchase`, `salesReturns`, `purchaseReturns`,
`salesByCustomer`, `purchaseByVendor`.

Each accepts the authenticated company id, parsed `from`/`to` (Carbon,
start/end of day), the optional filters, `page`, `perPage`, and `export` flag.
All queries are scoped by `company_id` and bounded by `created_at BETWEEN from AND to`.

**Export range cap.** When `export` is true, enforce a maximum range
(`config('reports.max_export_days')`, default **366**). If exceeded, throw a
`RuntimeException` the controller turns into a 422 with message:
"Date range too large for export. Please select a range of up to 1 year."
Paginated (display) requests have no cap — pagination keeps memory bounded.

### Response contract

```json
{
  "data": [ /* page rows, or groups for aggregate reports */ ],
  "pagination": { "page": 1, "perPage": 50, "total": 1234, "lastPage": 25 },
  "summary": { /* totals computed over the ENTIRE range, not just the page */ }
}
```

- `summary` is computed server-side across the whole filtered range (separate
  aggregate query) so footer grand-totals stay correct under pagination.
- When `export=1`: `data` contains **all** rows in range (no pagination);
  `pagination` is omitted or `null`; `summary` still present.

**Per-report `data` shape (camelCase):**

- *Detailed Sales:* array of sales; each `{ id, customerId, customerName,
  paymentMethod, returnStatus, createdAt, totalAmount, items:[{productId,
  productName, quantity, unitPrice, totalLinePrice}], returns:[{id, reason,
  createdAt, totalAmount}], netAmount }`.
- *Detailed Purchase:* array of POs; each with `items` (incl. receivedQuantity,
  unitCost, totalLineCost), `status`, `returnStatus`, `vendorName`, `totalAmount`.
- *Sales Return / Purchase Return:* array of returns; each with `items`, party
  name, reason, `createdAt`, `totalAmount`.
- *Sales by Customer:* array of customer **groups**, paginated by group; each
  `{ customerId, customerName, invoiceCount, customerTotal, sales:[...] }`.
- *Purchase by Vendor:* array of vendor **groups**, paginated by group; each
  `{ vendorId, vendorName, orderCount, vendorTotal, orders:[...] }`.

**Output format note (intentional deviation from project rule).** The project rule
is "always use API Resources." These endpoints instead return purpose-built
camelCase arrays straight from the service, because the payload is a computed,
paginated reporting DTO (rows + `pagination` + range-wide `summary`, with nested
grouping) that does not map one-to-one onto a single Eloquent model. CRUD API
Resources wrap a single model/collection and cannot carry the pagination/summary
envelope. Keys remain camelCase to match the rest of the API. Approved 2026-06-29.

### Config — `config/reports.php` (new)

```php
return [
    'default_per_page' => 50,
    'max_export_days'  => 366,
];
```

## Frontend (`public/js/pages/reports.js` + `resources/views/pages/reports.blade.php`)

### Blade changes (per affected panel)

- Remove auto-run handlers: `onchange="runX()"` on date/customer/payment/status
  inputs and `oninput="runX()"` on search inputs. Inputs now only hold values;
  they apply on the next **Run Report** click.
- Keep the **Run Report** button (`onclick="runX()"`) and **Clear** button.
- Add a **pagination footer** container below each report table:
  `« Prev | page numbers | Next »` plus "Showing X–Y of N".
- Add a **blank placeholder** element shown before the first run.

### JS changes

- `rptOpen(type)` for the six reports: pre-fill From/To to the **current month**,
  populate the customer/vendor dropdown as today, show the blank placeholder,
  and **do not fetch**.
- Each `runX()` rewritten to:
  1. Validate From & To are non-empty (else show inline error toast, abort).
  2. Show loading spinner, disable Run.
  3. Fetch page 1 from the report's endpoint with current filters.
  4. Render rows (existing row markup reused) + render pagination footer.
  5. Render `summary` into the existing footer/summary bar.
- Page navigation: clicking a page fetches that page and re-renders (filters held
  from the last run, not re-read mid-pagination).
- Reports no longer read from `window.ERP.state.*` transaction arrays.

### New shared JS helpers

- `rptFetchReport(endpointKey, params)` — wraps `ERP.api` call, returns the
  `{data, pagination, summary}` envelope; centralized error handling.
- `rptRenderPagination(containerId, pagination, onPageClick)` — renders footer.
- `rptValidateDateRange(fromId, toId)` — returns bool, shows inline error if invalid.

### New API wrappers (`public/js/api.js`)

Six methods under `window.ERP.api`, e.g.
`getDetailedSalesReport(params)`, `getDetailedPurchaseReport(params)`, etc.,
each issuing the GET with query string and returning parsed JSON.

### Export changes

The six `exportXExcel()` / `exportXPDF()` functions currently read the
client-side filtered dataset. They will instead fetch the endpoint with
`export=1` (full range, subject to the 366-day cap), then build the file from the
returned rows using the existing XLSX/jsPDF code. On a 422 (range too large), show
the server message and abort.

## Files touched

**New:** `app/Http/Controllers/Api/ReportDataController.php`,
`app/Services/ReportQueryService.php`,
`app/Http/Requests/ReportQueryRequest.php`,
`config/reports.php`.

**Modified:** `routes/api.php` (+6 routes),
`public/js/pages/reports.js` (6 run fns + 6 export fns + 3 helpers),
`public/js/api.js` (+6 wrappers),
`resources/views/pages/reports.blade.php` (6 panels: remove auto-run, add
pagination footer + blank placeholder).

## Testing

Feature tests (`tests/Feature/`) for `ReportDataController`:

- Each endpoint requires `from`/`to` → 422 when missing.
- `to before from` → 422.
- Returns only rows within range and within the caller's company (tenant scope).
- Pagination: `total`/`lastPage` correct; page 2 returns the next slice.
- `summary` totals computed over the full range, independent of page.
- `export=1` returns all rows and omits pagination.
- `export=1` with range > `max_export_days` → 422.
- Super Admin (company_id null) → 403.
- Aggregate reports group by customer/vendor and paginate by group.

## Non-goals

- No change to how other pages consume `sync/transactions`.
- No infinite-scroll (explicit pager only).
- No server-side export file generation (file still built in browser).
