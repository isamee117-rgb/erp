# Paginated Transaction Reports Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the six detailed transaction reports fetch only an explicitly-selected date range, one page at a time, from new server-side paginated endpoints — eliminating the full-payload `sync/transactions` dependency that caused OOM.

**Architecture:** New `ReportDataController` (thin) delegates to `ReportQueryService`, which runs company-scoped, date-bounded, paginated Eloquent queries and reuses existing API Resources to shape rows. Each endpoint returns a `{data, pagination, summary}` envelope. The frontend renders blank until **Run Report**, validates a mandatory date range, fetches one page at a time, and fetches the full range only for Excel/PDF export (capped at 1 year).

**Tech Stack:** Laravel 12 / PHP 8.2, Eloquent, API Resources, Form Requests, PHPUnit feature tests (MySQL `erppos_test`); vanilla JS (`window.ERP`), Tabler CSS, XLSX + jsPDF.

## Global Constraints

- Controllers thin — all logic in `app/Services/`. Validation only via Form Requests. (backend-rules)
- JSON keys camelCase; error format `response()->json(['error' => '...'], 4xx)`.
- IDs are string-prefixed; never auto-increment. Multi-tenant: every tenant query scoped by `company_id`.
- Super Admin has `company_id = null` — these reports return 403 for Super Admin (mirror `ReportBuilderController`).
- Get authenticated user via `$request->get('auth_user')`.
- Run tests with XAMPP PHP: `/c/xampp/php/php artisan test`. Test DB is `erppos_test` (configured in `phpunit.xml`).
- No inline JS/CSS in Blade (`onclick=` for button triggers is the existing pattern and is retained; no `<script>`/`<style>` blocks, no `style=` attributes added).
- Output rows reuse existing Resources where they fit; the `{data, pagination, summary}` envelope itself is a plain camelCase array (approved deviation — see spec §3).
- Export date-range cap: `config('reports.max_export_days')`, default **366**. Default page size: `config('reports.default_per_page')`, default **50**.

---

## File Structure

**New:**
- `config/reports.php` — `default_per_page`, `max_export_days`.
- `app/Http/Requests/ReportQueryRequest.php` — validates `from`/`to` (required) + optional filters/paging.
- `app/Services/ReportQueryService.php` — 6 public report methods + shared private helpers.
- `app/Http/Controllers/Api/ReportDataController.php` — 6 thin methods.
- `tests/Feature/ReportDataTest.php` — feature tests for all endpoints.

**Modified:**
- `routes/api.php` — +6 GET routes in the `throttle:api-reads` group.
- `public/js/api.js` — +6 `window.ERP.api` wrappers.
- `public/js/pages/reports.js` — 3 shared helpers; rewrite 6 `runX()` + 6 `exportX*()`; blank-on-open in `rptOpen`.
- `resources/views/pages/reports.blade.php` — 6 panels: remove auto-run handlers, add pagination footer + blank placeholder.

---

## Task 1: Backend foundation + Detailed Sales endpoint

**Files:**
- Create: `config/reports.php`
- Create: `app/Http/Requests/ReportQueryRequest.php`
- Create: `app/Services/ReportQueryService.php`
- Create: `app/Http/Controllers/Api/ReportDataController.php`
- Modify: `routes/api.php` (after line 152, inside `throttle:api-reads` group)
- Test: `tests/Feature/ReportDataTest.php`

**Interfaces:**
- Produces:
  - `ReportQueryService::detailedSales(string $companyId, \Carbon\Carbon $from, \Carbon\Carbon $to, array $filters, int $page, int $perPage, bool $export): array` returning `['data' => array, 'pagination' => ?array, 'summary' => array]`.
  - Private helpers later tasks reuse:
    - `assertExportRange(\Carbon\Carbon $from, \Carbon\Carbon $to, bool $export): void` — throws `\RuntimeException` if `$export` and range > `max_export_days`.
    - `buildEnvelope(\Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection $rows, callable $shape, array $summary, bool $export): array` — wraps shaped rows + pagination meta + summary.
  - `ReportDataController::detailedSales(ReportQueryRequest $request)`.
  - Route name: `GET /api/reports/detailed-sales`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/ReportDataTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Services\DocumentSequenceService;

class ReportDataTest extends ApiTestCase
{
    private array $product;
    private array $customer;

    protected function setUp(): void
    {
        parent::setUp();
        app(DocumentSequenceService::class)->ensureSequencesExist($this->company->id);
        $this->product  = $this->createProduct([
            'name' => 'Rice 5kg', 'unitPrice' => 500.00, 'unitCost' => 350.00, 'initialStock' => 100,
        ]);
        $this->customer = $this->createParty('Customer', ['name' => 'ACME Traders']);
    }

    private function makeSale(int $qty = 2): array
    {
        return $this->postJson('/api/sales', [
            'customerId'    => $this->customer['id'],
            'paymentMethod' => 'Cash',
            'items'         => [['productId' => $this->product['id'], 'quantity' => $qty, 'discount' => 0]],
        ], $this->auth())->json();
    }

    private function range(): string
    {
        $from = now()->startOfDay()->toDateString();
        $to   = now()->endOfDay()->toDateString();
        return "from={$from}&to={$to}";
    }

    /** @test */
    public function detailed_sales_requires_from_and_to(): void
    {
        $this->getJson('/api/reports/detailed-sales', $this->auth())
             ->assertStatus(422);
    }

    /** @test */
    public function detailed_sales_rejects_to_before_from(): void
    {
        $this->getJson('/api/reports/detailed-sales?from=2026-02-01&to=2026-01-01', $this->auth())
             ->assertStatus(422);
    }

    /** @test */
    public function detailed_sales_returns_scoped_paginated_rows_with_summary(): void
    {
        $this->makeSale();
        $this->makeSale();

        $res = $this->getJson('/api/reports/detailed-sales?' . $this->range() . '&page=1&perPage=1', $this->auth());

        $res->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'customerId', 'paymentMethod', 'totalAmount', 'items', 'returns', 'returnStatus', 'createdAt']],
                'pagination' => ['page', 'perPage', 'total', 'lastPage'],
                'summary' => ['totalInvoices', 'grandTotal', 'totalReturns', 'netTotal'],
            ]);

        $this->assertSame(2, $res->json('pagination.total'));
        $this->assertSame(2, $res->json('pagination.lastPage'));
        $this->assertCount(1, $res->json('data'));            // perPage=1
        $this->assertSame(2, $res->json('summary.totalInvoices'));
    }

    /** @test */
    public function detailed_sales_export_returns_all_rows_without_pagination(): void
    {
        $this->makeSale();
        $this->makeSale();

        $res = $this->getJson('/api/reports/detailed-sales?' . $this->range() . '&export=1', $this->auth());

        $res->assertStatus(200);
        $this->assertNull($res->json('pagination'));
        $this->assertCount(2, $res->json('data'));
    }

    /** @test */
    public function detailed_sales_export_rejects_range_over_one_year(): void
    {
        $this->getJson('/api/reports/detailed-sales?from=2024-01-01&to=2026-01-01&export=1', $this->auth())
             ->assertStatus(422);
    }

    /** @test */
    public function detailed_sales_forbidden_for_super_admin(): void
    {
        $super = $this->createSuperAdmin();
        $token = $this->loginAndGetToken($super);

        $this->getJson('/api/reports/detailed-sales?' . $this->range(), $this->auth($token))
             ->assertStatus(403);
    }

    /** @test */
    public function detailed_sales_excludes_other_company_sales(): void
    {
        $this->makeSale();

        $otherCompany = $this->createCompany(['name' => 'Other Co']);
        $otherAdmin   = $this->createAdminUser($otherCompany, ['username' => 'otheradmin']);
        $otherToken   = $this->loginAndGetToken($otherAdmin);

        $res = $this->getJson('/api/reports/detailed-sales?' . $this->range(), $this->auth($otherToken));
        $res->assertStatus(200);
        $this->assertSame(0, $res->json('pagination.total'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `/c/xampp/php/php artisan test --filter=ReportDataTest`
Expected: FAIL — route `/api/reports/detailed-sales` not defined (404, assertions fail).

- [ ] **Step 3: Create the config file**

Create `config/reports.php`:

```php
<?php

return [
    // Default rows per page for paginated transaction reports.
    'default_per_page' => 50,

    // Maximum date-range span (in days) allowed for a full-range export,
    // to keep memory bounded. ~1 year.
    'max_export_days' => 366,
];
```

- [ ] **Step 4: Create the Form Request**

Create `app/Http/Requests/ReportQueryRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportQueryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ApiTokenAuth middleware already authenticated the request.
    }

    public function rules(): array
    {
        return [
            'from'          => 'required|date',
            'to'            => 'required|date|after_or_equal:from',
            'page'          => 'integer|min:1',
            'perPage'       => 'integer|min:1|max:200',
            'export'        => 'boolean',
            'customerId'    => 'nullable|string',
            'vendorId'      => 'nullable|string',
            'paymentMethod' => 'nullable|string',
            'status'        => 'nullable|string',
            'search'        => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'from.required' => 'A start date is required.',
            'to.required'   => 'An end date is required.',
            'to.after_or_equal' => 'The end date must be on or after the start date.',
        ];
    }
}
```

- [ ] **Step 5: Create the service with shared helpers + detailedSales()**

Create `app/Services/ReportQueryService.php`:

```php
<?php

namespace App\Services;

use App\Http\Resources\SaleOrderResource;
use App\Models\SaleOrder;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReportQueryService
{
    public function detailedSales(
        string $companyId,
        Carbon $from,
        Carbon $to,
        array $filters,
        int $page,
        int $perPage,
        bool $export
    ): array {
        $this->assertExportRange($from, $to, $export);

        $query = SaleOrder::with(['items', 'returns', 'customer'])
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$from, $to]);

        if (!empty($filters['customerId']))    $query->where('customer_id', $filters['customerId']);
        if (!empty($filters['paymentMethod'])) $query->where('payment_method', $filters['paymentMethod']);
        if (!empty($filters['search']))        $query->where('invoice_no', 'like', '%' . $filters['search'] . '%');

        $query->orderByDesc('created_at');

        $summary = $this->salesSummary($companyId, $from, $to, $filters);

        $shape = fn($sale) => (new SaleOrderResource($sale))->resolve();

        return $this->buildEnvelope(
            $export ? $query->get() : $query->paginate($perPage, ['*'], 'page', $page),
            $shape,
            $summary,
            $export
        );
    }

    // ── Shared helpers (reused by every report method) ─────────────────────────

    protected function assertExportRange(Carbon $from, Carbon $to, bool $export): void
    {
        if ($export && $from->diffInDays($to) > config('reports.max_export_days')) {
            throw new \RuntimeException(
                'Date range too large for export. Please select a range of up to 1 year.'
            );
        }
    }

    /**
     * Wraps shaped rows into the {data, pagination, summary} envelope.
     * $rows is a paginator (display) or a plain Collection (export).
     */
    protected function buildEnvelope(
        LengthAwarePaginator|Collection $rows,
        callable $shape,
        array $summary,
        bool $export
    ): array {
        if ($export) {
            return [
                'data'       => $rows->map($shape)->values()->all(),
                'pagination' => null,
                'summary'    => $summary,
            ];
        }

        return [
            'data'       => collect($rows->items())->map($shape)->values()->all(),
            'pagination' => [
                'page'     => $rows->currentPage(),
                'perPage'  => $rows->perPage(),
                'total'    => $rows->total(),
                'lastPage' => $rows->lastPage(),
            ],
            'summary'    => $summary,
        ];
    }

    protected function salesSummary(string $companyId, Carbon $from, Carbon $to, array $filters): array
    {
        $base = SaleOrder::where('company_id', $companyId)
            ->whereBetween('created_at', [$from, $to]);
        if (!empty($filters['customerId']))    $base->where('customer_id', $filters['customerId']);
        if (!empty($filters['paymentMethod'])) $base->where('payment_method', $filters['paymentMethod']);
        if (!empty($filters['search']))        $base->where('invoice_no', 'like', '%' . $filters['search'] . '%');

        $totalInvoices = (clone $base)->count();
        $grandTotal    = (float) (clone $base)->sum('total_amount');

        // Returns linked to the sales in range
        $totalReturns = (float) \App\Models\SaleReturn::where('company_id', $companyId)
            ->whereBetween('created_at', [$from, $to])
            ->sum('total_amount');

        return [
            'totalInvoices' => $totalInvoices,
            'grandTotal'    => $grandTotal,
            'totalReturns'  => $totalReturns,
            'netTotal'      => $grandTotal - $totalReturns,
        ];
    }
}
```

- [ ] **Step 6: Create the controller**

Create `app/Http/Controllers/Api/ReportDataController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportQueryRequest;
use App\Services\ReportQueryService;
use Carbon\Carbon;

class ReportDataController extends Controller
{
    public function __construct(private ReportQueryService $service) {}

    public function detailedSales(ReportQueryRequest $request)
    {
        return $this->run($request, fn($coId, $from, $to, $filters, $page, $perPage, $export) =>
            $this->service->detailedSales($coId, $from, $to, $filters, $page, $perPage, $export)
        );
    }

    /**
     * Shared request handling: Super-Admin guard, date parsing, paging defaults,
     * filter extraction, and RuntimeException -> 422 mapping.
     */
    private function run(ReportQueryRequest $request, callable $call)
    {
        $user = $request->get('auth_user');
        if (!$user->company_id) {
            return response()->json(['error' => 'Not available for Super Admin.'], 403);
        }

        $from    = Carbon::parse($request->input('from'))->startOfDay();
        $to      = Carbon::parse($request->input('to'))->endOfDay();
        $page    = (int) $request->input('page', 1);
        $perPage = (int) $request->input('perPage', config('reports.default_per_page'));
        $export  = $request->boolean('export');

        $filters = $request->only(['customerId', 'vendorId', 'paymentMethod', 'status', 'search']);

        try {
            return response()->json(
                $call($user->company_id, $from, $to, $filters, $page, $perPage, $export)
            );
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
```

- [ ] **Step 7: Register the route**

In `routes/api.php`, inside the `throttle:api-reads` group, immediately after the `report-builder` routes (after line 155), add the import at top and the route:

Add to imports (top of file, near line 19):
```php
use App\Http\Controllers\Api\ReportDataController;
```

Add inside the `throttle:api-reads` group:
```php
        Route::get('/reports/detailed-sales', [ReportDataController::class, 'detailedSales']);
```

- [ ] **Step 8: Run tests to verify they pass**

Run: `/c/xampp/php/php artisan test --filter=ReportDataTest`
Expected: PASS — all 7 tests green.

- [ ] **Step 9: Commit**

```bash
git add config/reports.php app/Http/Requests/ReportQueryRequest.php app/Services/ReportQueryService.php app/Http/Controllers/Api/ReportDataController.php routes/api.php tests/Feature/ReportDataTest.php
git commit -m "feat: paginated detailed-sales report endpoint with mandatory date range"
```

---

## Task 2: Detailed Purchase endpoint

**Files:**
- Modify: `app/Services/ReportQueryService.php` (add `detailedPurchase()` + `purchaseSummary()`)
- Modify: `app/Http/Controllers/Api/ReportDataController.php` (add `detailedPurchase()`)
- Modify: `routes/api.php`
- Test: `tests/Feature/ReportDataTest.php` (add purchase tests)

**Interfaces:**
- Consumes: `assertExportRange`, `buildEnvelope` from Task 1.
- Produces: `ReportQueryService::detailedPurchase(string $companyId, Carbon $from, Carbon $to, array $filters, int $page, int $perPage, bool $export): array`; `ReportDataController::detailedPurchase`; `GET /api/reports/detailed-purchase`.

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/ReportDataTest.php`. First add a purchase helper and test (uses the existing PO create + receive API; confirm payload against `app/Http/Controllers/Api/PurchaseController.php` and `StorePurchaseOrderRequest` before running):

```php
    private function makePurchaseOrder(): array
    {
        $vendor = $this->createParty('Vendor', ['name' => 'Supplier Co']);
        return $this->postJson('/api/purchases', [
            'vendorId' => $vendor['id'],
            'items'    => [['productId' => $this->product['id'], 'quantity' => 10, 'unitCost' => 300]],
        ], $this->auth())->json();
    }

    /** @test */
    public function detailed_purchase_requires_dates_and_returns_rows(): void
    {
        $this->getJson('/api/reports/detailed-purchase', $this->auth())->assertStatus(422);

        $this->makePurchaseOrder();
        $res = $this->getJson('/api/reports/detailed-purchase?' . $this->range(), $this->auth());

        $res->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'vendorId', 'status', 'totalAmount', 'items']],
                'pagination' => ['page', 'perPage', 'total', 'lastPage'],
                'summary' => ['totalOrders', 'grandTotal'],
            ]);
        $this->assertSame(1, $res->json('pagination.total'));
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `/c/xampp/php/php artisan test --filter=detailed_purchase_requires_dates_and_returns_rows`
Expected: FAIL — route not defined.

- [ ] **Step 3: Add the service method**

Add to `app/Services/ReportQueryService.php` (and import `use App\Http\Resources\PurchaseOrderResource; use App\Models\PurchaseOrder;`):

```php
    public function detailedPurchase(
        string $companyId,
        Carbon $from,
        Carbon $to,
        array $filters,
        int $page,
        int $perPage,
        bool $export
    ): array {
        $this->assertExportRange($from, $to, $export);

        $query = PurchaseOrder::with(['items', 'receives.items', 'vendor'])
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$from, $to]);

        if (!empty($filters['vendorId'])) $query->where('vendor_id', $filters['vendorId']);
        if (!empty($filters['status']))   $query->where('status', $filters['status']);
        if (!empty($filters['search']))   $query->where('po_number', 'like', '%' . $filters['search'] . '%');

        $query->orderByDesc('created_at');

        $base = PurchaseOrder::where('company_id', $companyId)->whereBetween('created_at', [$from, $to]);
        if (!empty($filters['vendorId'])) $base->where('vendor_id', $filters['vendorId']);
        if (!empty($filters['status']))   $base->where('status', $filters['status']);
        if (!empty($filters['search']))   $base->where('po_number', 'like', '%' . $filters['search'] . '%');

        $summary = [
            'totalOrders' => (clone $base)->count(),
            'grandTotal'  => (float) (clone $base)->sum('total_amount'),
        ];

        $shape = fn($po) => (new PurchaseOrderResource($po))->resolve();

        return $this->buildEnvelope(
            $export ? $query->get() : $query->paginate($perPage, ['*'], 'page', $page),
            $shape,
            $summary,
            $export
        );
    }
```

> Before writing, confirm `PurchaseOrder` uses `po_number` and `total_amount` columns and the `items`, `receives`, `vendor` relations exist (read `app/Models/PurchaseOrder.php` and `app/Http/Resources/PurchaseOrderResource.php`). Adjust column/relation names to match.

- [ ] **Step 4: Add the controller method**

Add to `app/Http/Controllers/Api/ReportDataController.php`:

```php
    public function detailedPurchase(ReportQueryRequest $request)
    {
        return $this->run($request, fn($coId, $from, $to, $filters, $page, $perPage, $export) =>
            $this->service->detailedPurchase($coId, $from, $to, $filters, $page, $perPage, $export)
        );
    }
```

- [ ] **Step 5: Add the route**

In `routes/api.php`, after the `detailed-sales` route:
```php
        Route::get('/reports/detailed-purchase', [ReportDataController::class, 'detailedPurchase']);
```

- [ ] **Step 6: Run tests to verify they pass**

Run: `/c/xampp/php/php artisan test --filter=ReportDataTest`
Expected: PASS — all tests including new purchase test.

- [ ] **Step 7: Commit**

```bash
git add app/Services/ReportQueryService.php app/Http/Controllers/Api/ReportDataController.php routes/api.php tests/Feature/ReportDataTest.php
git commit -m "feat: paginated detailed-purchase report endpoint"
```

---

## Task 3: Sales Return + Purchase Return endpoints

**Files:**
- Modify: `app/Services/ReportQueryService.php` (add `salesReturns()`, `purchaseReturns()`)
- Modify: `app/Http/Controllers/Api/ReportDataController.php` (add 2 methods)
- Modify: `routes/api.php`
- Test: `tests/Feature/ReportDataTest.php`

**Interfaces:**
- Consumes: `assertExportRange`, `buildEnvelope`.
- Produces: `ReportQueryService::salesReturns(...)` and `purchaseReturns(...)` with the same 7-arg signature as `detailedSales`; controller methods `salesReturns`, `purchaseReturns`; routes `GET /api/reports/sales-returns`, `GET /api/reports/purchase-returns`.

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/ReportDataTest.php` (validation-level tests that don't require seeding returns; structure asserted on empty set):

```php
    /** @test */
    public function sales_returns_requires_dates(): void
    {
        $this->getJson('/api/reports/sales-returns', $this->auth())->assertStatus(422);
    }

    /** @test */
    public function sales_returns_returns_envelope(): void
    {
        $res = $this->getJson('/api/reports/sales-returns?' . $this->range(), $this->auth());
        $res->assertStatus(200)
            ->assertJsonStructure(['data', 'pagination' => ['page', 'perPage', 'total', 'lastPage'], 'summary' => ['totalReturns', 'grandTotal']]);
    }

    /** @test */
    public function purchase_returns_requires_dates(): void
    {
        $this->getJson('/api/reports/purchase-returns', $this->auth())->assertStatus(422);
    }

    /** @test */
    public function purchase_returns_returns_envelope(): void
    {
        $res = $this->getJson('/api/reports/purchase-returns?' . $this->range(), $this->auth());
        $res->assertStatus(200)
            ->assertJsonStructure(['data', 'pagination' => ['page', 'perPage', 'total', 'lastPage'], 'summary' => ['totalReturns', 'grandTotal']]);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `/c/xampp/php/php artisan test --filter="sales_returns_returns_envelope|purchase_returns_returns_envelope"`
Expected: FAIL — routes not defined.

- [ ] **Step 3: Add the service methods**

Add to `app/Services/ReportQueryService.php` (import `use App\Http\Resources\SaleReturnResource; use App\Http\Resources\PurchaseReturnResource; use App\Models\SaleReturn; use App\Models\PurchaseReturn;`):

```php
    public function salesReturns(
        string $companyId, Carbon $from, Carbon $to, array $filters, int $page, int $perPage, bool $export
    ): array {
        $this->assertExportRange($from, $to, $export);

        $query = SaleReturn::with(['items', 'customer'])
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$from, $to]);
        if (!empty($filters['customerId'])) $query->where('customer_id', $filters['customerId']);
        $query->orderByDesc('created_at');

        $base = SaleReturn::where('company_id', $companyId)->whereBetween('created_at', [$from, $to]);
        if (!empty($filters['customerId'])) $base->where('customer_id', $filters['customerId']);

        $summary = [
            'totalReturns' => (clone $base)->count(),
            'grandTotal'   => (float) (clone $base)->sum('total_amount'),
        ];

        $shape = fn($r) => (new SaleReturnResource($r))->resolve();

        return $this->buildEnvelope(
            $export ? $query->get() : $query->paginate($perPage, ['*'], 'page', $page),
            $shape, $summary, $export
        );
    }

    public function purchaseReturns(
        string $companyId, Carbon $from, Carbon $to, array $filters, int $page, int $perPage, bool $export
    ): array {
        $this->assertExportRange($from, $to, $export);

        $query = PurchaseReturn::with(['items', 'vendor'])
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$from, $to]);
        if (!empty($filters['vendorId'])) $query->where('vendor_id', $filters['vendorId']);
        $query->orderByDesc('created_at');

        $base = PurchaseReturn::where('company_id', $companyId)->whereBetween('created_at', [$from, $to]);
        if (!empty($filters['vendorId'])) $base->where('vendor_id', $filters['vendorId']);

        $summary = [
            'totalReturns' => (clone $base)->count(),
            'grandTotal'   => (float) (clone $base)->sum('total_amount'),
        ];

        $shape = fn($r) => (new PurchaseReturnResource($r))->resolve();

        return $this->buildEnvelope(
            $export ? $query->get() : $query->paginate($perPage, ['*'], 'page', $page),
            $shape, $summary, $export
        );
    }
```

> Confirm relation names (`customer`/`vendor`/`items`) and the `total_amount` column on `SaleReturn`/`PurchaseReturn` before running (read the models). Adjust to match.

- [ ] **Step 4: Add the controller methods**

```php
    public function salesReturns(ReportQueryRequest $request)
    {
        return $this->run($request, fn($coId, $from, $to, $filters, $page, $perPage, $export) =>
            $this->service->salesReturns($coId, $from, $to, $filters, $page, $perPage, $export)
        );
    }

    public function purchaseReturns(ReportQueryRequest $request)
    {
        return $this->run($request, fn($coId, $from, $to, $filters, $page, $perPage, $export) =>
            $this->service->purchaseReturns($coId, $from, $to, $filters, $page, $perPage, $export)
        );
    }
```

- [ ] **Step 5: Add the routes**

```php
        Route::get('/reports/sales-returns',    [ReportDataController::class, 'salesReturns']);
        Route::get('/reports/purchase-returns', [ReportDataController::class, 'purchaseReturns']);
```

- [ ] **Step 6: Run tests to verify they pass**

Run: `/c/xampp/php/php artisan test --filter=ReportDataTest`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Services/ReportQueryService.php app/Http/Controllers/Api/ReportDataController.php routes/api.php tests/Feature/ReportDataTest.php
git commit -m "feat: paginated sales-returns and purchase-returns report endpoints"
```

---

## Task 4: Sales by Customer + Purchase by Vendor endpoints (grouped)

**Files:**
- Modify: `app/Services/ReportQueryService.php` (add `salesByCustomer()`, `purchaseByVendor()`)
- Modify: `app/Http/Controllers/Api/ReportDataController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/ReportDataTest.php`

**Interfaces:**
- Consumes: `assertExportRange`, `buildEnvelope`.
- Produces: `salesByCustomer(...)`, `purchaseByVendor(...)` (same 7-arg signature). `data` is an array of **groups**; pagination is over groups. Controller methods `salesByCustomer`, `purchaseByVendor`; routes `GET /api/reports/sales-by-customer`, `GET /api/reports/purchase-by-vendor`.

- [ ] **Step 1: Write the failing test**

```php
    /** @test */
    public function sales_by_customer_groups_and_paginates_by_customer(): void
    {
        $this->makeSale();
        $this->makeSale();

        $res = $this->getJson('/api/reports/sales-by-customer?' . $this->range(), $this->auth());
        $res->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['customerId', 'customerName', 'invoiceCount', 'customerTotal', 'sales']],
                'pagination' => ['page', 'perPage', 'total', 'lastPage'],
                'summary' => ['totalCustomers', 'totalInvoices', 'grandTotal'],
            ]);
        // Both sales belong to one customer -> one group
        $this->assertSame(1, $res->json('pagination.total'));
        $this->assertSame(2, $res->json('data.0.invoiceCount'));
    }

    /** @test */
    public function purchase_by_vendor_requires_dates(): void
    {
        $this->getJson('/api/reports/purchase-by-vendor', $this->auth())->assertStatus(422);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `/c/xampp/php/php artisan test --filter="sales_by_customer_groups_and_paginates_by_customer"`
Expected: FAIL — route not defined.

- [ ] **Step 3: Add the service methods**

Group in PHP after loading the range, then paginate the groups manually with `LengthAwarePaginator`. Add `use Illuminate\Pagination\LengthAwarePaginator;` import.

```php
    public function salesByCustomer(
        string $companyId, Carbon $from, Carbon $to, array $filters, int $page, int $perPage, bool $export
    ): array {
        $this->assertExportRange($from, $to, $export);

        $q = SaleOrder::with(['items', 'customer'])
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$from, $to]);
        if (!empty($filters['customerId']))    $q->where('customer_id', $filters['customerId']);
        if (!empty($filters['paymentMethod'])) $q->where('payment_method', $filters['paymentMethod']);
        $sales = $q->orderByDesc('created_at')->get();

        // Group by customer (walk-in = null id)
        $groups = $sales->groupBy(fn($s) => $s->customer_id ?? '__walkin__')
            ->map(function ($rows, $key) {
                $first = $rows->first();
                return [
                    'customerId'   => $key === '__walkin__' ? null : $key,
                    'customerName' => $key === '__walkin__' ? 'Walk-in / Cash' : ($first->customer?->name ?? $key),
                    'invoiceCount' => $rows->count(),
                    'customerTotal'=> (float) $rows->sum('total_amount'),
                    'sales'        => $rows->map(fn($s) => (new SaleOrderResource($s))->resolve())->values()->all(),
                ];
            })
            ->sortBy('customerName', SORT_FLAG_CASE | SORT_STRING)
            ->values();

        $summary = [
            'totalCustomers' => $groups->count(),
            'totalInvoices'  => $sales->count(),
            'grandTotal'     => (float) $sales->sum('total_amount'),
        ];

        $pageGroups = $export
            ? $groups
            : new LengthAwarePaginator(
                $groups->forPage($page, $perPage)->values(),
                $groups->count(), $perPage, $page
            );

        // Groups are already shaped; identity shaper.
        return $this->buildEnvelope($pageGroups, fn($g) => $g, $summary, $export);
    }

    public function purchaseByVendor(
        string $companyId, Carbon $from, Carbon $to, array $filters, int $page, int $perPage, bool $export
    ): array {
        $this->assertExportRange($from, $to, $export);

        $q = PurchaseOrder::with(['items', 'vendor'])
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$from, $to]);
        if (!empty($filters['vendorId'])) $q->where('vendor_id', $filters['vendorId']);
        if (!empty($filters['status']))   $q->where('status', $filters['status']);
        $orders = $q->orderByDesc('created_at')->get();

        $groups = $orders->groupBy(fn($o) => $o->vendor_id ?? '__none__')
            ->map(function ($rows, $key) {
                $first = $rows->first();
                return [
                    'vendorId'   => $key === '__none__' ? null : $key,
                    'vendorName' => $key === '__none__' ? '—' : ($first->vendor?->name ?? $key),
                    'orderCount' => $rows->count(),
                    'vendorTotal'=> (float) $rows->sum('total_amount'),
                    'orders'     => $rows->map(fn($o) => (new PurchaseOrderResource($o))->resolve())->values()->all(),
                ];
            })
            ->sortBy('vendorName', SORT_FLAG_CASE | SORT_STRING)
            ->values();

        $summary = [
            'totalVendors' => $groups->count(),
            'totalOrders'  => $orders->count(),
            'grandTotal'   => (float) $orders->sum('total_amount'),
        ];

        $pageGroups = $export
            ? $groups
            : new LengthAwarePaginator($groups->forPage($page, $perPage)->values(), $groups->count(), $perPage, $page);

        return $this->buildEnvelope($pageGroups, fn($g) => $g, $summary, $export);
    }
```

> Note: `buildEnvelope` already handles both `LengthAwarePaginator` and `Collection`. The identity shaper `fn($g) => $g` is used because groups are pre-shaped arrays.

- [ ] **Step 4: Add the controller methods**

```php
    public function salesByCustomer(ReportQueryRequest $request)
    {
        return $this->run($request, fn($coId, $from, $to, $filters, $page, $perPage, $export) =>
            $this->service->salesByCustomer($coId, $from, $to, $filters, $page, $perPage, $export)
        );
    }

    public function purchaseByVendor(ReportQueryRequest $request)
    {
        return $this->run($request, fn($coId, $from, $to, $filters, $page, $perPage, $export) =>
            $this->service->purchaseByVendor($coId, $from, $to, $filters, $page, $perPage, $export)
        );
    }
```

- [ ] **Step 5: Add the routes**

```php
        Route::get('/reports/sales-by-customer', [ReportDataController::class, 'salesByCustomer']);
        Route::get('/reports/purchase-by-vendor', [ReportDataController::class, 'purchaseByVendor']);
```

- [ ] **Step 6: Run tests to verify they pass**

Run: `/c/xampp/php/php artisan test --filter=ReportDataTest`
Expected: PASS — all backend tests green.

- [ ] **Step 7: Commit**

```bash
git add app/Services/ReportQueryService.php app/Http/Controllers/Api/ReportDataController.php routes/api.php tests/Feature/ReportDataTest.php
git commit -m "feat: paginated sales-by-customer and purchase-by-vendor report endpoints"
```

---

## Task 5: Frontend foundation + Detailed Sales wired end-to-end

No JS test framework exists in this project; verification is manual against a running app (`/c/xampp/php/php artisan serve`, log in as a company admin with sales data).

**Files:**
- Modify: `public/js/api.js` (add `getDetailedSalesReport`)
- Modify: `public/js/pages/reports.js` (3 shared helpers + rewrite `runSalesReport`, `rptOpen('sales')` blank, `exportSalesExcel`/`exportSalesPDF`)
- Modify: `resources/views/pages/reports.blade.php` (sales panel: remove auto-run, add placeholder + pagination footer)

**Interfaces:**
- Consumes: `GET /api/reports/detailed-sales` (Task 1).
- Produces JS globals reused by Task 6:
  - `ERP.api.getDetailedSalesReport(params)` → resolves to `{data, pagination, summary}`.
  - `rptFetchReport(apiFn, params)` → Promise of the envelope; centralises error alert.
  - `rptRenderPagination(containerId, pagination, onPageClick)`.
  - `rptValidateDateRange(fromId, toId)` → boolean.
  - `rptCurrentMonthRange()` → `{from, to}` ISO date strings.

- [ ] **Step 1: Add the API wrapper**

In `public/js/api.js`, add to `window.ERP.api` (match the existing wrapper style — find how other GETs build query strings; here is the explicit form):

```js
        getDetailedSalesReport: function(params) {
            var qs = new URLSearchParams(params).toString();
            return ERP.api._get('/reports/detailed-sales?' + qs);
        },
```

> Confirm the internal GET helper name (`_get`/`get`/`request`) by reading `public/js/api.js`; use whatever the existing read wrappers use. All six report wrappers follow this identical shape.

- [ ] **Step 2: Add shared helpers to reports.js**

At the top of `public/js/pages/reports.js` (after the existing top-level vars), add:

```js
function rptCurrentMonthRange() {
    var now = new Date();
    var first = new Date(now.getFullYear(), now.getMonth(), 1);
    var pad = function(n){ return (n < 10 ? '0' : '') + n; };
    var iso = function(d){ return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()); };
    return { from: iso(first), to: iso(now) };
}

function rptValidateDateRange(fromId, toId) {
    var from = document.getElementById(fromId).value;
    var to   = document.getElementById(toId).value;
    if (!from || !to) { alert('Please select both From and To dates before running the report.'); return false; }
    if (from > to)    { alert('From date must be on or before To date.'); return false; }
    return true;
}

function rptFetchReport(apiFn, params) {
    return apiFn.call(ERP.api, params);
}

function rptRenderPagination(containerId, pagination, onPageClick) {
    var el = document.getElementById(containerId);
    if (!pagination || pagination.lastPage <= 1) { el.innerHTML = ''; return; }
    var p = pagination.page, last = pagination.lastPage;
    var start = (p - 1) * pagination.perPage + 1;
    var end = Math.min(p * pagination.perPage, pagination.total);
    var html = '<div class="d-flex align-items-center justify-content-between py-2">';
    html += '<span class="text-muted erp-text-sm">Showing ' + start + '–' + end + ' of ' + pagination.total + '</span>';
    html += '<div class="btn-group">';
    html += '<button class="btn btn-sm btn-light" ' + (p<=1?'disabled':'') + ' data-rpt-page="' + (p-1) + '">Prev</button>';
    html += '<span class="btn btn-sm btn-light disabled">Page ' + p + ' of ' + last + '</span>';
    html += '<button class="btn btn-sm btn-light" ' + (p>=last?'disabled':'') + ' data-rpt-page="' + (p+1) + '">Next</button>';
    html += '</div></div>';
    el.innerHTML = html;
    el.querySelectorAll('[data-rpt-page]').forEach(function(btn){
        btn.addEventListener('click', function(){ onPageClick(parseInt(btn.getAttribute('data-rpt-page'), 10)); });
    });
}
```

> Note: no inline `onclick` — page buttons use `addEventListener` per frontend-rules.

- [ ] **Step 3: Rewrite runSalesReport + blank-on-open**

Replace the body of `runSalesReport` (currently lines ~979–1145) so it fetches a page from the endpoint instead of reading `state.sales`. Keep the existing row-building markup for an invoice + items + returns + net (reuse the current HTML-building loop, but iterate over `resp.data` and each sale's `.items` / `.returns`). Add a module var `var _rptSalesPage = 1;`.

```js
function runSalesReport(page) {
    if (!rptValidateDateRange('rptSalesFrom', 'rptSalesTo')) return;
    _rptSalesPage = page || 1;

    var params = {
        from: document.getElementById('rptSalesFrom').value,
        to:   document.getElementById('rptSalesTo').value,
        page: _rptSalesPage,
        perPage: 50,
    };
    var custId = document.getElementById('rptSalesCustomer').value;
    var payM   = document.getElementById('rptSalesPayment').value;
    var search = document.getElementById('rptSalesSearch').value;
    if (custId) params.customerId = custId;
    if (payM)   params.paymentMethod = payM;
    if (search) params.search = search;

    var btn = document.getElementById('rptSalesRunBtn');
    if (btn) btn.disabled = true;
    document.getElementById('rptSalesBody').innerHTML = '<tr><td colspan="7" class="text-center py-4"><span class="spinner-border spinner-border-sm"></span> Loading…</td></tr>';

    rptFetchReport(ERP.api.getDetailedSalesReport, params)
        .then(function(resp) {
            rptRenderSalesRows(resp.data);           // builds invoice/item/return rows from resp.data
            rptRenderSalesSummary(resp.summary);     // fills footer/summary bar from resp.summary
            rptRenderPagination('rptSalesPagination', resp.pagination, function(p){ runSalesReport(p); });
        })
        .catch(function(e){ alert('Error loading report: ' + e.message); })
        .finally(function(){ if (btn) btn.disabled = false; });
}
```

Extract the existing row-building loop into `rptRenderSalesRows(sales)` and the footer/summary code into `rptRenderSalesSummary(summary)` (move, don't rewrite, the current DOM-building logic; it already expects each sale to have `.items` and `.returns`, which the endpoint provides). For net amount per invoice, compute `totalAmount - sum(returns[].totalAmount)` as the current code already does.

Then change `rptOpen('sales')` (lines ~129–139): keep the customer-dropdown population, set default dates and show a blank placeholder, and **do not** call `runSalesReport()`:

```js
  } else if (type === 'sales') {
    document.getElementById('rpt-sales-panel').classList.remove('d-none');
    var state = window.ERP.state;
    var coId = (state.currentUser || {}).companyId;
    var custs = (state.parties || []).filter(function(p){ return (!coId || p.companyId === coId) && p.type === 'Customer'; });
    custs.sort(function(a,b){ return (a.name||'').localeCompare(b.name||''); });
    var sel = document.getElementById('rptSalesCustomer');
    sel.innerHTML = '<option value="">All Customers</option>';
    custs.forEach(function(c){ sel.innerHTML += '<option value="'+c.id+'">'+c.name+'</option>'; });
    var r = rptCurrentMonthRange();
    document.getElementById('rptSalesFrom').value = r.from;
    document.getElementById('rptSalesTo').value = r.to;
    document.getElementById('rptSalesBody').innerHTML = '<tr><td colspan="7" class="text-center text-muted py-5">Select a date range and click Run Report.</td></tr>';
    document.getElementById('rptSalesPagination').innerHTML = '';
  }
```

- [ ] **Step 4: Update the sales export functions**

Change `exportSalesExcel` / `exportSalesPDF` to fetch the full range with `export:1` then build the file from `resp.data` (reuse the existing XLSX/jsPDF building code, iterating `resp.data`):

```js
function exportSalesExcel() {
    if (!rptValidateDateRange('rptSalesFrom', 'rptSalesTo')) return;
    var params = { from: document.getElementById('rptSalesFrom').value, to: document.getElementById('rptSalesTo').value, export: 1 };
    rptFetchReport(ERP.api.getDetailedSalesReport, params)
        .then(function(resp){ rptBuildSalesExcel(resp.data, resp.summary); })
        .catch(function(e){ alert('Error: ' + e.message); });
}
```

`rptBuildSalesExcel` / `rptBuildSalesPDF` hold the existing export-building code, taking `data` rows instead of reading client state. Same pattern for PDF.

- [ ] **Step 5: Update the Blade sales panel**

In `resources/views/pages/reports.blade.php`, sales panel (lines ~344–390):
- Remove `onchange="runSalesReport()"` from `#rptSalesFrom`, `#rptSalesTo`, `#rptSalesCustomer`, `#rptSalesPayment` and `oninput="runSalesReport()"` from `#rptSalesSearch`.
- Give the Run button an id: `<button id="rptSalesRunBtn" class="btn btn-primary rpt-btn" onclick="runSalesReport()">…</button>`.
- After the sales report table (after `</table>` / summary), add the pagination container:
```html
<div id="rptSalesPagination" class="d-print-none"></div>
```
Confirm the body `<tbody id="rptSalesBody">` and footer ids match what the JS targets.

- [ ] **Step 6: Manual verification**

Run: `/c/xampp/php/php artisan serve` and open `/reports` as a company admin.
Verify:
1. Opening **Detailed Sales** shows "Select a date range…" (blank), dates pre-filled to current month, no network call to the report endpoint yet.
2. Clearing a date and clicking **Run Report** shows the validation alert; no fetch.
3. With valid dates, **Run Report** loads page 1; if >50 invoices, Prev/Next work and "Showing X–Y of N" is correct.
4. Footer grand totals reflect the whole range (not just the page).
5. Excel and PDF export produce files covering the whole range; a >1-year range shows the server's "too large" message.
6. DevTools Network: `detailed-sales` returns 200.

- [ ] **Step 7: Commit**

```bash
git add public/js/api.js public/js/pages/reports.js resources/views/pages/reports.blade.php
git commit -m "feat: wire detailed sales report to paginated endpoint with mandatory date + blank-on-open"
```

---

## Task 6: Wire remaining five reports to their endpoints

Repeat the Task 5 frontend pattern for the other five reports. Each is independently verifiable; commit per report or in one commit after all verify.

**Files:**
- Modify: `public/js/api.js` (5 wrappers: `getDetailedPurchaseReport`, `getSalesReturnReport`, `getPurchaseReturnReport`, `getSalesByCustomerReport`, `getPurchaseByVendorReport`)
- Modify: `public/js/pages/reports.js` (rewrite `runPurchaseReport`, `runSalesReturnReport`, `runPurchaseReturnReport`, `runSalesByCustomerReport`, `runPurchaseByVendorReport`; their `rptOpen` branches; their export fns)
- Modify: `resources/views/pages/reports.blade.php` (5 panels: remove auto-run handlers, add Run-button ids + pagination containers + blank placeholders)

**Interfaces:**
- Consumes: endpoints from Tasks 2–4; helpers from Task 5 (`rptFetchReport`, `rptRenderPagination`, `rptValidateDateRange`, `rptCurrentMonthRange`).

- [ ] **Step 1: Add the 5 API wrappers**

In `public/js/api.js`, mirroring `getDetailedSalesReport`:

```js
        getDetailedPurchaseReport: function(params){ return ERP.api._get('/reports/detailed-purchase?' + new URLSearchParams(params).toString()); },
        getSalesReturnReport:      function(params){ return ERP.api._get('/reports/sales-returns?' + new URLSearchParams(params).toString()); },
        getPurchaseReturnReport:   function(params){ return ERP.api._get('/reports/purchase-returns?' + new URLSearchParams(params).toString()); },
        getSalesByCustomerReport:  function(params){ return ERP.api._get('/reports/sales-by-customer?' + new URLSearchParams(params).toString()); },
        getPurchaseByVendorReport: function(params){ return ERP.api._get('/reports/purchase-by-vendor?' + new URLSearchParams(params).toString()); },
```

- [ ] **Step 2: Rewrite the 5 run functions**

For each report, apply the same transformation shown in Task 5 Step 3:
1. Add a `_rpt<Name>Page` module var.
2. `runX(page)`: validate dates (`rptValidateDateRange` with that panel's from/to ids), build `params` (from/to/page/perPage + that report's filters), disable its Run button, show a loading row, call its API wrapper via `rptFetchReport`, then render rows + summary + `rptRenderPagination(<panelPaginationId>, resp.pagination, function(p){ runX(p); })`.
3. Move the existing DOM row/summary-building loops into `rptRender<Name>Rows(data)` / `rptRender<Name>Summary(summary)` — reuse current markup; iterate `resp.data`.
4. For the two grouped reports, iterate groups in `resp.data` (`sales`/`orders` nested arrays) — the existing grouped markup already expects this shape.

Field-name mapping per report (use the endpoint's `summary` keys):
- Detailed Purchase: `summary.totalOrders`, `summary.grandTotal`.
- Sales/Purchase Returns: `summary.totalReturns`, `summary.grandTotal`.
- Sales by Customer: `summary.totalCustomers`, `summary.totalInvoices`, `summary.grandTotal`.
- Purchase by Vendor: `summary.totalVendors`, `summary.totalOrders`, `summary.grandTotal`.

- [ ] **Step 3: Update the 5 rptOpen branches**

For `purchase`, `salesReturn`, `purchaseReturn`, `salesByCustomer`, `purchaseByVendor` branches in `rptOpen`: keep dropdown population, set current-month dates (`rptCurrentMonthRange`), show the blank placeholder in that panel's body, clear its pagination container, and **remove** the trailing `runX()` call.

- [ ] **Step 4: Update the 10 export functions**

For each report's `exportXExcel`/`exportXPDF`: validate dates, fetch with `export:1` via the report's wrapper, build the file from `resp.data` (move existing build code into `rptBuild…` helpers). Handle the 422 range-cap error by alerting `e.message`.

- [ ] **Step 5: Update the 5 Blade panels**

For panels `rpt-purchase-panel`, `rpt-salesReturn-panel`, `rpt-purchaseReturn-panel`, `rpt-salesByCustomer-panel`, `rpt-purchaseByVendor-panel`:
- Remove `onchange="runX()"` / `oninput="runX()"` from their date/filter/search inputs.
- Add an id to each Run button (`rptPurchRunBtn`, `rptSReturnRunBtn`, `rptPReturnRunBtn`, `rptSBCRunBtn`, `rptPBVRunBtn`).
- Add a pagination container after each report table: `<div id="rptPurchPagination" class="d-print-none"></div>` (and `rptSReturnPagination`, `rptPReturnPagination`, `rptSBCPagination`, `rptPBVPagination`).

- [ ] **Step 6: Manual verification**

For each of the five reports, repeat the Task 5 Step 6 checks: blank on open, mandatory-date alert, paginated load, range-wide totals, export covers full range, 422 on >1-year export. Confirm Network shows 200 for each `detailed-purchase`, `sales-returns`, `purchase-returns`, `sales-by-customer`, `purchase-by-vendor`.

- [ ] **Step 7: Commit**

```bash
git add public/js/api.js public/js/pages/reports.js resources/views/pages/reports.blade.php
git commit -m "feat: wire remaining five transaction reports to paginated endpoints"
```

---

## Self-Review Notes (resolved)

- **Spec coverage:** blank-on-open (Tasks 5/6 Step 3), mandatory date (Task 1 Form Request + `rptValidateDateRange`), range-limited fetch (`whereBetween` + no full sync), pagination (`buildEnvelope` + `rptRenderPagination`), range-wide summary (`*Summary` queries), export full-range + cap (`assertExportRange` + `export:1` fetch), tenant scope + Super-Admin 403 (Task 1) — all mapped.
- **Type consistency:** all six service methods share the 7-arg signature `(companyId, from, to, filters, page, perPage, export)` and return `{data, pagination, summary}`; `buildEnvelope` accepts both `LengthAwarePaginator` and `Collection`; helper names (`rptFetchReport`, `rptRenderPagination`, `rptValidateDateRange`, `rptCurrentMonthRange`) are used identically in Tasks 5 and 6.
- **Verification caveats:** column/relation names for Purchase/Return/aggregate models are flagged for confirmation in Tasks 2–4 before running (read the model + resource first); the api.js GET-helper name is flagged in Task 5 Step 1.
