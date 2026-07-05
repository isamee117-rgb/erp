# Batch-Level Expiry & Manufacturing Date Tracking — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Company-level setup toggles enable capturing Batch No + Expiry/Mfg dates per purchase-receive line, with an Expiry Report and dashboard alert card.

**Architecture:** Three nullable columns on `purchase_receive_items` store batch data captured in the GRN (receive) modal. Two settings-table toggles (`expiry_date_enabled`, `mfg_date_enabled`) plus a threshold (`expiry_alert_days`) control UI visibility, delivered to the frontend via the existing `/api/sync/core` payload (same flow as `jobCardMode`). Report endpoints live in `ReportDataController` → `ReportQueryService` following the existing paginated-report envelope pattern.

**Tech Stack:** Laravel 12 (PHP 8.2), MySQL, PHPUnit 11, Vanilla JS + Tabler/Bootstrap 5 Blade pages.

**Spec:** `docs/superpowers/specs/2026-07-05-batch-expiry-mfg-dates-design.md`

## Global Constraints

- Multi-tenant: every query on tenant tables scoped by `company_id` (purchase_receive_items has no company_id — scope via `whereHas('purchaseReceive', ...)`).
- Controllers thin; business logic in `app/Services/`. Form Requests for all validation. API Resources / service-shaped arrays for JSON (camelCase keys).
- Auth user via `$request->get('auth_user')`.
- PHP run via `/c/xampp/php/php` (Git Bash). Tests: `/c/xampp/php/php artisan test` (uses `erppos_test` DB).
- Frontend: no ES6 modules, no arrow functions in page JS, no inline `style="..."` in NEW markup (new CSS classes go in `public/css/app.css`), API calls only via `window.ERP.api`.
- Settings API fields are always accepted regardless of toggle state — toggles control UI visibility only.
- Both toggles OFF ⇒ receive modal, reports tiles, and dashboard render exactly as today.
- Commit after every task: `feat: <description>`.

---

### Task 1: Settings backend — inventory-dates endpoint + sync payload

**Files:**
- Create: `app/Http/Requests/UpdateInventoryDatesRequest.php`
- Modify: `app/Http/Controllers/Api/SettingsController.php` (add method after `updateJobCardMode`, ~line 55)
- Modify: `routes/api.php` (after line 88, the `/settings/job-card-mode` route)
- Modify: `app/Services/SyncService.php` (lines 64–65 whereIn list; lines 111–114 payload)
- Test: `tests/Feature/InventoryDatesTest.php` (new file)

**Interfaces:**
- Consumes: `Setting` model (`updateOrCreate` on `company_id`+`key`), existing `/api/sync/core` route.
- Produces: `PUT /api/settings/inventory-dates` accepting `{expiryDateEnabled: bool, mfgDateEnabled: bool, expiryAlertDays: int}` returning `{success, expiryDateEnabled, mfgDateEnabled, expiryAlertDays}`. Sync/core payload gains keys `expiryDateEnabled` (bool), `mfgDateEnabled` (bool), `expiryAlertDays` (int, default 30). Settings keys: `expiry_date_enabled`, `mfg_date_enabled`, `expiry_alert_days`. Tasks 3–7 rely on these exact key names.

- [ ] **Step 1: Write failing tests**

Create `tests/Feature/InventoryDatesTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Services\DocumentSequenceService;
use PHPUnit\Framework\Attributes\Test;

class InventoryDatesTest extends ApiTestCase
{
    private array $vendor;
    private array $product;

    protected function setUp(): void
    {
        parent::setUp();
        app(DocumentSequenceService::class)->ensureSequencesExist($this->company->id);
        $this->vendor  = $this->createParty('Vendor');
        $this->product = $this->createProduct();
    }

    private function enableInventoryDates(int $alertDays = 30): void
    {
        $this->putJson('/api/settings/inventory-dates', [
            'expiryDateEnabled' => true,
            'mfgDateEnabled'    => true,
            'expiryAlertDays'   => $alertDays,
        ], $this->auth())->assertOk();
    }

    #[Test]
    public function inventory_dates_settings_are_saved(): void
    {
        $this->enableInventoryDates(45);
        $this->assertDatabaseHas('settings', ['company_id' => $this->company->id, 'key' => 'expiry_date_enabled', 'value' => '1']);
        $this->assertDatabaseHas('settings', ['company_id' => $this->company->id, 'key' => 'mfg_date_enabled', 'value' => '1']);
        $this->assertDatabaseHas('settings', ['company_id' => $this->company->id, 'key' => 'expiry_alert_days', 'value' => '45']);
    }

    #[Test]
    public function sync_core_returns_inventory_date_settings(): void
    {
        $this->enableInventoryDates(45);
        $this->getJson('/api/sync/core', $this->auth())
            ->assertOk()
            ->assertJson([
                'expiryDateEnabled' => true,
                'mfgDateEnabled'    => true,
                'expiryAlertDays'   => 45,
            ]);
    }

    #[Test]
    public function sync_core_defaults_when_settings_missing(): void
    {
        $this->getJson('/api/sync/core', $this->auth())
            ->assertOk()
            ->assertJson([
                'expiryDateEnabled' => false,
                'mfgDateEnabled'    => false,
                'expiryAlertDays'   => 30,
            ]);
    }

    #[Test]
    public function invalid_alert_days_is_rejected(): void
    {
        $this->putJson('/api/settings/inventory-dates', [
            'expiryDateEnabled' => true,
            'mfgDateEnabled'    => false,
            'expiryAlertDays'   => 0,
        ], $this->auth())->assertStatus(422);
    }
}
```

Note: `$vendor`/`$product` are unused until Task 2 — that's intentional; Task 2 adds tests to this same file.

- [ ] **Step 2: Run tests to verify they fail**

Run: `/c/xampp/php/php artisan test --filter=InventoryDatesTest`
Expected: FAIL — `inventory_dates_settings_are_saved` and `invalid_alert_days_is_rejected` get 404 (route missing); the two sync tests fail on missing JSON keys.

- [ ] **Step 3: Create the Form Request**

Create `app/Http/Requests/UpdateInventoryDatesRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryDatesRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'expiryDateEnabled' => 'required|boolean',
            'mfgDateEnabled'    => 'required|boolean',
            'expiryAlertDays'   => 'required|integer|min:1|max:365',
        ];
    }

    public function messages(): array
    {
        return [
            'expiryAlertDays.min' => 'Expiry alert days must be between 1 and 365.',
            'expiryAlertDays.max' => 'Expiry alert days must be between 1 and 365.',
        ];
    }
}
```

- [ ] **Step 4: Add controller method**

In `app/Http/Controllers/Api/SettingsController.php`, add the import at the top with the other use statements:

```php
use App\Http\Requests\UpdateInventoryDatesRequest;
```

Then add this method directly after `updateJobCardMode` (after line 55):

```php
    public function updateInventoryDates(UpdateInventoryDatesRequest $request)
    {
        $user = $request->get('auth_user');
        $v    = $request->validated();

        Setting::updateOrCreate(
            ['company_id' => $user->company_id, 'key' => 'expiry_date_enabled'],
            ['value' => $v['expiryDateEnabled'] ? '1' : '0']
        );
        Setting::updateOrCreate(
            ['company_id' => $user->company_id, 'key' => 'mfg_date_enabled'],
            ['value' => $v['mfgDateEnabled'] ? '1' : '0']
        );
        Setting::updateOrCreate(
            ['company_id' => $user->company_id, 'key' => 'expiry_alert_days'],
            ['value' => (string) $v['expiryAlertDays']]
        );

        return response()->json([
            'success'           => true,
            'expiryDateEnabled' => (bool) $v['expiryDateEnabled'],
            'mfgDateEnabled'    => (bool) $v['mfgDateEnabled'],
            'expiryAlertDays'   => (int) $v['expiryAlertDays'],
        ]);
    }
```

- [ ] **Step 5: Add the route**

In `routes/api.php`, after line 88 (`Route::put('/settings/job-card-mode', ...)`) add:

```php
        Route::put('/settings/inventory-dates',    [SettingsController::class, 'updateInventoryDates']);
```

- [ ] **Step 6: Extend the sync/core payload**

In `app/Services/SyncService.php`:

Change line 65 from:

```php
            ->whereIn('key', ['currency', 'invoice_format', 'job_card_mode'])
```

to:

```php
            ->whereIn('key', ['currency', 'invoice_format', 'job_card_mode', 'expiry_date_enabled', 'mfg_date_enabled', 'expiry_alert_days'])
```

After line 114 (`'jobCardMode' => ...`), add:

```php
            'expiryDateEnabled'  => (bool) ($settings->get('expiry_date_enabled')?->value ?? false),
            'mfgDateEnabled'     => (bool) ($settings->get('mfg_date_enabled')?->value ?? false),
            'expiryAlertDays'    => (int) ($settings->get('expiry_alert_days')?->value ?? 30),
```

- [ ] **Step 7: Run tests to verify they pass**

Run: `/c/xampp/php/php artisan test --filter=InventoryDatesTest`
Expected: PASS (4 tests).

- [ ] **Step 8: Commit**

```bash
git add app/Http/Requests/UpdateInventoryDatesRequest.php app/Http/Controllers/Api/SettingsController.php routes/api.php app/Services/SyncService.php tests/Feature/InventoryDatesTest.php
git commit -m "feat: inventory-dates setup toggles (expiry/mfg tracking + alert days)"
```

---

### Task 2: Batch columns on purchase_receive_items + receive persistence

**Files:**
- Create: `database/migrations/2026_07_05_000001_add_batch_dates_to_purchase_receive_items.php`
- Modify: `app/Models/PurchaseReceiveItem.php` ($fillable + $casts)
- Modify: `app/Http/Requests/ReceivePurchaseOrderRequest.php`
- Modify: `app/Services/PurchaseService.php` (`receiveOrder`, the `PurchaseReceiveItem::create` block ~line 145)
- Modify: `app/Http/Resources/PurchaseReceiveItemResource.php`
- Test: `tests/Feature/InventoryDatesTest.php` (append tests)

**Interfaces:**
- Consumes: nothing from Task 1 (fields accepted regardless of toggles).
- Produces: `purchase_receive_items` columns `batch_no` (varchar 255 null), `mfg_date` (date null), `expiry_date` (date null). Receive API items accept `batchNo`, `mfgDate`, `expiryDate` (camelCase). `PurchaseReceiveItemResource` outputs `batchNo`, `mfgDate`, `expiryDate`. Task 3's report queries `PurchaseReceiveItem::whereNotNull('expiry_date')` and relies on `mfg_date`/`expiry_date` having `'date'` casts.

**Important:** `PurchaseController::receive()` passes `$request->validated()['items']` to the service — unvalidated keys are STRIPPED. The new fields must be in the Form Request rules or they silently disappear.

- [ ] **Step 1: Write failing tests**

Append to `tests/Feature/InventoryDatesTest.php` (inside the class). Also add this private helper below `enableInventoryDates`:

```php
    private function receiveWithDates(array $itemOverrides = []): \Illuminate\Testing\TestResponse
    {
        $po = $this->postJson('/api/purchases', [
            'vendorId' => $this->vendor['id'],
            'items'    => [['productId' => $this->product['id'], 'quantity' => 10, 'unitCost' => 50]],
        ], $this->auth());

        return $this->putJson('/api/purchases/' . $po->json('id') . '/receive', [
            'items' => [array_merge([
                'productId' => $this->product['id'],
                'quantity'  => 10,
                'unitCost'  => 50,
            ], $itemOverrides)],
        ], $this->auth());
    }
```

And these tests:

```php
    #[Test]
    public function receive_stores_batch_and_dates(): void
    {
        $this->receiveWithDates([
            'batchNo'    => 'LOT-001',
            'mfgDate'    => '2026-01-01',
            'expiryDate' => '2027-01-01',
        ])->assertOk();

        $this->assertDatabaseHas('purchase_receive_items', [
            'product_id'  => $this->product['id'],
            'batch_no'    => 'LOT-001',
            'mfg_date'    => '2026-01-01',
            'expiry_date' => '2027-01-01',
        ]);
    }

    #[Test]
    public function receive_without_batch_fields_still_works(): void
    {
        $this->receiveWithDates()->assertOk();

        $this->assertDatabaseHas('purchase_receive_items', [
            'product_id' => $this->product['id'],
            'batch_no'   => null,
            'mfg_date'   => null,
        ]);
    }

    #[Test]
    public function expiry_before_mfg_date_is_rejected(): void
    {
        $this->receiveWithDates([
            'mfgDate'    => '2026-06-01',
            'expiryDate' => '2026-05-01',
        ])->assertStatus(422);
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `/c/xampp/php/php artisan test --filter=InventoryDatesTest`
Expected: the three new tests FAIL (`batch_no` column missing / no 422). Task 1 tests still PASS.

- [ ] **Step 3: Create the migration**

Create `database/migrations/2026_07_05_000001_add_batch_dates_to_purchase_receive_items.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_receive_items', function (Blueprint $table) {
            $table->string('batch_no', 255)->nullable()->after('unit_cost');
            $table->date('mfg_date')->nullable()->after('batch_no');
            $table->date('expiry_date')->nullable()->after('mfg_date');
            $table->index('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_receive_items', function (Blueprint $table) {
            $table->dropIndex(['expiry_date']);
            $table->dropColumn(['batch_no', 'mfg_date', 'expiry_date']);
        });
    }
};
```

Run: `/c/xampp/php/php artisan migrate`
Expected: migration runs on the dev DB (`lean_erp`) without error. (Tests migrate `erppos_test` automatically via RefreshDatabase.)

- [ ] **Step 4: Update the model**

In `app/Models/PurchaseReceiveItem.php` replace `$fillable` and `$casts`:

```php
    protected $fillable = [
        'id', 'purchase_receive_id', 'purchase_item_id', 'product_id',
        'quantity', 'unit_cost', 'batch_no', 'mfg_date', 'expiry_date',
    ];

    protected $casts = [
        'quantity'    => 'integer',
        'unit_cost'   => 'float',
        'mfg_date'    => 'date',
        'expiry_date' => 'date',
    ];
```

- [ ] **Step 5: Update the Form Request**

In `app/Http/Requests/ReceivePurchaseOrderRequest.php`, add three rules inside `rules()` after `'items.*.purchaseItemId'`:

```php
            'items.*.batchNo'             => 'nullable|string|max:255',
            'items.*.mfgDate'             => 'nullable|date',
            'items.*.expiryDate'          => 'nullable|date',
```

And add cross-field validation after `rules()` (Laravel's `after:items.*.mfgDate` misbehaves when the referenced field is null, so validate explicitly):

```php
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ($this->input('items', []) as $i => $item) {
                if (!empty($item['mfgDate']) && !empty($item['expiryDate'])
                    && strtotime($item['expiryDate']) <= strtotime($item['mfgDate'])) {
                    $validator->errors()->add(
                        "items.$i.expiryDate",
                        'Expiry date must be after the manufacturing date.'
                    );
                }
            }
        });
    }
```

- [ ] **Step 6: Persist in the service**

In `app/Services/PurchaseService.php` `receiveOrder()`, replace the `PurchaseReceiveItem::create([...])` block (~line 145) with:

```php
            PurchaseReceiveItem::create([
                'id'                  => 'RCI-' . Str::random(9),
                'purchase_receive_id' => $receiveId,
                'purchase_item_id'    => $poItem->id,
                'product_id'          => $productId,
                'quantity'            => $actualQty,
                'unit_cost'           => $unitCost,
                'batch_no'            => $ri['batchNo'] ?? $ri['batch_no'] ?? null,
                'mfg_date'            => $ri['mfgDate'] ?? $ri['mfg_date'] ?? null,
                'expiry_date'         => $ri['expiryDate'] ?? $ri['expiry_date'] ?? null,
            ]);
```

- [ ] **Step 7: Update the Resource**

In `app/Http/Resources/PurchaseReceiveItemResource.php`, replace `toArray()`:

```php
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'purchaseItemId'  => $this->purchase_item_id,
            'productId'       => $this->product_id,
            'quantity'        => (int)   $this->quantity,
            'unitCost'        => (float) $this->unit_cost,
            'batchNo'         => $this->batch_no,
            'mfgDate'         => $this->mfg_date?->toDateString(),
            'expiryDate'      => $this->expiry_date?->toDateString(),
        ];
    }
```

- [ ] **Step 8: Run tests to verify they pass**

Run: `/c/xampp/php/php artisan test --filter=InventoryDatesTest`
Expected: PASS (7 tests).
Also run: `/c/xampp/php/php artisan test --filter=PurchaseTest`
Expected: PASS (no regression in existing receive flow).

- [ ] **Step 9: Commit**

```bash
git add database/migrations/2026_07_05_000001_add_batch_dates_to_purchase_receive_items.php app/Models/PurchaseReceiveItem.php app/Http/Requests/ReceivePurchaseOrderRequest.php app/Services/PurchaseService.php app/Http/Resources/PurchaseReceiveItemResource.php tests/Feature/InventoryDatesTest.php
git commit -m "feat: capture batch no + mfg/expiry dates on purchase receive"
```

---

### Task 3: Expiry report + summary endpoints

**Files:**
- Create: `app/Http/Requests/ExpiryReportRequest.php`
- Modify: `app/Services/ReportQueryService.php` (add 3 public + 2 protected methods, plus imports)
- Modify: `app/Http/Controllers/Api/ReportDataController.php` (add 2 methods)
- Modify: `routes/api.php` (after line 163, the `/reports/purchase-by-vendor` route)
- Test: `tests/Feature/InventoryDatesTest.php` (append tests)

**Interfaces:**
- Consumes: `purchase_receive_items.expiry_date/mfg_date/batch_no` (Task 2, with date casts); settings key `expiry_alert_days` (Task 1); existing `buildEnvelope()` helper in `ReportQueryService`.
- Produces:
  - `GET /api/reports/expiry?page&perPage&status` → `{data: [{id, productId, productName, sku, batchNo, mfgDate, expiryDate, receiveDate, quantity, status, daysToExpiry}], pagination: {page, perPage, total, lastPage}, summary: {expired, expiringSoon, ok, alertDays}}`. `status` values: `expired` | `expiring_soon` | `ok`.
  - `GET /api/reports/expiry-summary` → `{expired: int, expiringSoon: int, ok: int, alertDays: int}`.
  - Tasks 6–7 rely on these exact response shapes.

- [ ] **Step 1: Write failing tests**

Append to `tests/Feature/InventoryDatesTest.php`:

```php
    #[Test]
    public function expiry_report_classifies_batches(): void
    {
        $this->enableInventoryDates(30);
        $this->receiveWithDates(['batchNo' => 'EXP',  'expiryDate' => now()->subDays(5)->toDateString()]);
        $this->receiveWithDates(['batchNo' => 'SOON', 'expiryDate' => now()->addDays(10)->toDateString()]);
        $this->receiveWithDates(['batchNo' => 'OK',   'expiryDate' => now()->addDays(90)->toDateString()]);

        $resp = $this->getJson('/api/reports/expiry', $this->auth());
        $resp->assertOk()
             ->assertJsonPath('summary.expired', 1)
             ->assertJsonPath('summary.expiringSoon', 1)
             ->assertJsonPath('summary.ok', 1)
             ->assertJsonPath('summary.alertDays', 30);

        $rows = collect($resp->json('data'))->keyBy('batchNo');
        $this->assertEquals('expired',       $rows['EXP']['status']);
        $this->assertEquals('expiring_soon', $rows['SOON']['status']);
        $this->assertEquals('ok',            $rows['OK']['status']);
    }

    #[Test]
    public function expiry_report_status_filter_limits_rows(): void
    {
        $this->enableInventoryDates(30);
        $this->receiveWithDates(['batchNo' => 'EXP', 'expiryDate' => now()->subDays(5)->toDateString()]);
        $this->receiveWithDates(['batchNo' => 'OK',  'expiryDate' => now()->addDays(90)->toDateString()]);

        $this->getJson('/api/reports/expiry?status=expired', $this->auth())
             ->assertOk()
             ->assertJsonCount(1, 'data')
             ->assertJsonPath('data.0.batchNo', 'EXP');
    }

    #[Test]
    public function expiry_report_is_company_scoped(): void
    {
        $this->receiveWithDates(['batchNo' => 'MINE', 'expiryDate' => now()->addDays(10)->toDateString()]);

        $otherCo    = $this->createCompany(['name' => 'Other Co']);
        $otherAdmin = $this->createAdminUser($otherCo, ['username' => 'otheradmin']);
        $otherToken = $this->loginAndGetToken($otherAdmin);

        $this->getJson('/api/reports/expiry', $this->auth($otherToken))
             ->assertOk()
             ->assertJsonCount(0, 'data');
    }

    #[Test]
    public function expiry_summary_returns_counts_with_default_threshold(): void
    {
        // No settings saved — alertDays must default to 30
        $this->receiveWithDates(['expiryDate' => now()->subDay()->toDateString()]);

        $this->getJson('/api/reports/expiry-summary', $this->auth())
             ->assertOk()
             ->assertJsonPath('expired', 1)
             ->assertJsonPath('expiringSoon', 0)
             ->assertJsonPath('alertDays', 30);
    }

    #[Test]
    public function expiry_report_rejects_invalid_status(): void
    {
        $this->getJson('/api/reports/expiry?status=bogus', $this->auth())
             ->assertStatus(422);
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `/c/xampp/php/php artisan test --filter=InventoryDatesTest`
Expected: the five new tests FAIL with 404 (routes missing). Earlier tests still PASS.

- [ ] **Step 3: Create the Form Request**

Create `app/Http/Requests/ExpiryReportRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExpiryReportRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'page'    => 'integer|min:1',
            'perPage' => 'integer|min:1|max:200',
            'status'  => 'nullable|in:expired,expiring_soon,ok',
        ];
    }
}
```

- [ ] **Step 4: Add service methods**

In `app/Services/ReportQueryService.php`, add imports:

```php
use App\Models\PurchaseReceiveItem;
use App\Models\Setting;
```

Add these methods before the `// ── Shared helpers` section:

```php
    public function expiryReport(string $companyId, ?string $status, int $page, int $perPage): array
    {
        $alertDays = $this->expiryAlertDays($companyId);
        $today     = Carbon::today();
        $soonEnd   = $today->copy()->addDays($alertDays);

        $query = PurchaseReceiveItem::with(['product:id,name,sku', 'purchaseReceive:id,receive_date'])
            ->whereNotNull('expiry_date')
            ->whereHas('purchaseReceive', fn($q) => $q->where('company_id', $companyId));

        if ($status === 'expired')       $query->whereDate('expiry_date', '<', $today);
        if ($status === 'expiring_soon') $query->whereDate('expiry_date', '>=', $today)->whereDate('expiry_date', '<=', $soonEnd);
        if ($status === 'ok')            $query->whereDate('expiry_date', '>', $soonEnd);

        $query->orderBy('expiry_date');

        $summary = array_merge($this->expiryCounts($companyId, $alertDays), ['alertDays' => $alertDays]);

        $shape = function ($item) use ($today, $soonEnd) {
            $expiry    = $item->expiry_date;
            $rowStatus = $expiry->lt($today) ? 'expired' : ($expiry->lte($soonEnd) ? 'expiring_soon' : 'ok');
            return [
                'id'           => $item->id,
                'productId'    => $item->product_id,
                'productName'  => $item->product?->name ?? $item->product_id,
                'sku'          => $item->product?->sku,
                'batchNo'      => $item->batch_no,
                'mfgDate'      => $item->mfg_date?->toDateString(),
                'expiryDate'   => $expiry->toDateString(),
                'receiveDate'  => $item->purchaseReceive?->receive_date,
                'quantity'     => (int) $item->quantity,
                'status'       => $rowStatus,
                'daysToExpiry' => (int) $today->diffInDays($expiry, false),
            ];
        };

        return $this->buildEnvelope(
            $query->paginate($perPage, ['*'], 'page', $page),
            $shape,
            $summary,
            false
        );
    }

    public function expirySummary(string $companyId): array
    {
        $alertDays = $this->expiryAlertDays($companyId);
        return array_merge($this->expiryCounts($companyId, $alertDays), ['alertDays' => $alertDays]);
    }

    protected function expiryCounts(string $companyId, int $alertDays): array
    {
        $today   = Carbon::today();
        $soonEnd = $today->copy()->addDays($alertDays);
        $base    = fn() => PurchaseReceiveItem::whereNotNull('expiry_date')
            ->whereHas('purchaseReceive', fn($q) => $q->where('company_id', $companyId));

        return [
            'expired'      => $base()->whereDate('expiry_date', '<', $today)->count(),
            'expiringSoon' => $base()->whereDate('expiry_date', '>=', $today)->whereDate('expiry_date', '<=', $soonEnd)->count(),
            'ok'           => $base()->whereDate('expiry_date', '>', $soonEnd)->count(),
        ];
    }

    protected function expiryAlertDays(string $companyId): int
    {
        return (int) (Setting::forCompany($companyId)->where('key', 'expiry_alert_days')->value('value') ?? 30);
    }
```

- [ ] **Step 5: Add controller methods**

In `app/Http/Controllers/Api/ReportDataController.php`, add import:

```php
use App\Http\Requests\ExpiryReportRequest;
use Illuminate\Http\Request;
```

Add after `purchaseByVendor()` (before the private `run()` helper). The shared `run()` requires from/to dates which this stock-state report doesn't use, so these methods handle the request directly:

```php
    public function expiry(ExpiryReportRequest $request)
    {
        $user = $request->get('auth_user');
        if (!$user->company_id) {
            return response()->json(['error' => 'Not available for Super Admin.'], 403);
        }

        $page    = (int) $request->input('page', 1);
        $perPage = (int) $request->input('perPage', config('reports.default_per_page'));

        return response()->json(
            $this->service->expiryReport($user->company_id, $request->input('status'), $page, $perPage)
        );
    }

    public function expirySummary(Request $request)
    {
        $user = $request->get('auth_user');
        if (!$user->company_id) {
            return response()->json(['error' => 'Not available for Super Admin.'], 403);
        }

        return response()->json($this->service->expirySummary($user->company_id));
    }
```

- [ ] **Step 6: Add routes**

In `routes/api.php`, after line 163 (`/reports/purchase-by-vendor`) add:

```php
        Route::get('/reports/expiry',         [ReportDataController::class, 'expiry']);
        Route::get('/reports/expiry-summary', [ReportDataController::class, 'expirySummary']);
```

- [ ] **Step 7: Run tests to verify they pass**

Run: `/c/xampp/php/php artisan test --filter=InventoryDatesTest`
Expected: PASS (12 tests).

- [ ] **Step 8: Commit**

```bash
git add app/Http/Requests/ExpiryReportRequest.php app/Services/ReportQueryService.php app/Http/Controllers/Api/ReportDataController.php routes/api.php tests/Feature/InventoryDatesTest.php
git commit -m "feat: paginated expiry report and expiry-summary endpoints"
```

---

### Task 4: Settings page UI — Inventory Dates toggles

**Files:**
- Modify: `resources/views/pages/settings.blade.php` (Module Settings card, ~lines 366–382)
- Modify: `public/css/app.css` (new classes)
- Modify: `public/js/api.js` (new wrapper near `updateJobCardMode`, ~line 467)
- Modify: `public/js/app.js` (state defaults after `jobCardMode: false,` line 33)
- Modify: `public/js/pages/settings.js` (new init function, called from `renderPage()`)

**Interfaces:**
- Consumes: `PUT /api/settings/inventory-dates` (Task 1); sync/core keys `expiryDateEnabled`, `mfgDateEnabled`, `expiryAlertDays` (Task 1).
- Produces: `ERP.api.updateInventoryDates(expiryEnabled, mfgEnabled, alertDays)`; state defaults `ERP.state.expiryDateEnabled` (false), `ERP.state.mfgDateEnabled` (false), `ERP.state.expiryAlertDays` (30). Tasks 5–7 read these state flags. New CSS classes `set-switch-lg`, `set-alert-days-input`.

- [ ] **Step 1: Add CSS classes**

In `public/css/app.css`, append (near other `.set-*` settings-page rules if a section exists, otherwise at the end):

```css
/* Settings — Inventory Dates toggles */
.set-switch-lg { width: 2.5em; height: 1.4em; cursor: pointer; }
.set-alert-days-input { width: 90px; }
```

- [ ] **Step 2: Add markup to the Module Settings card**

In `resources/views/pages/settings.blade.php`, inside the Module Settings card body, after the Job Card Mode row's closing `</div>` (line 379, the one closing `d-flex align-items-center justify-content-between`) add:

```html
        <hr class="my-3">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <div class="fw-semibold set-module-title">Expiry Date Tracking</div>
            <div class="text-muted set-module-desc">Capture expiry dates per batch when receiving goods</div>
          </div>
          <div class="form-check form-switch ms-3">
            <input class="form-check-input set-switch-lg" type="checkbox" id="setting-expiry-date" role="switch">
          </div>
        </div>
        <div class="d-flex align-items-center justify-content-between mt-2 d-none" id="expiry-alert-days-row">
          <div>
            <div class="fw-semibold set-module-title">Expiry Alert Days</div>
            <div class="text-muted set-module-desc">Batches expiring within this many days show as &quot;Expiring Soon&quot;</div>
          </div>
          <input type="number" class="form-control inv-input ms-3 set-alert-days-input" id="setting-expiry-alert-days" min="1" max="365">
        </div>
        <hr class="my-3">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <div class="fw-semibold set-module-title">Manufacturing Date Tracking</div>
            <div class="text-muted set-module-desc">Capture manufacturing dates per batch when receiving goods</div>
          </div>
          <div class="form-check form-switch ms-3">
            <input class="form-check-input set-switch-lg" type="checkbox" id="setting-mfg-date" role="switch">
          </div>
        </div>
```

Also add to `public/css/app.css` (the existing rows use inline font-size styles; new rows use classes instead):

```css
.set-module-title { font-size: 0.9rem; }
.set-module-desc { font-size: 0.82rem; }
```

- [ ] **Step 3: Add the API wrapper**

In `public/js/api.js`, after the `updateJobCardMode` entry (~line 467–469), add (note: the previous entry needs a trailing comma):

```js
        updateInventoryDates: function(expiryEnabled, mfgEnabled, alertDays) {
            return request('PUT', '/settings/inventory-dates', {
                expiryDateEnabled: expiryEnabled,
                mfgDateEnabled: mfgEnabled,
                expiryAlertDays: alertDays
            });
        }
```

- [ ] **Step 4: Add state defaults**

In `public/js/app.js`, after `jobCardMode: false,` (line 33) add:

```js
        expiryDateEnabled: false,
        mfgDateEnabled: false,
        expiryAlertDays: 30,
```

- [ ] **Step 5: Wire the toggles in settings.js**

In `public/js/pages/settings.js`, add `initInventoryDatesToggles();` in `renderPage()` right after `initJobCardModeToggle();` (line 43). Then add below `initJobCardModeToggle`:

```js
function initInventoryDatesToggles(){
    var expToggle = document.getElementById('setting-expiry-date');
    var mfgToggle = document.getElementById('setting-mfg-date');
    var daysInput = document.getElementById('setting-expiry-alert-days');
    var daysRow   = document.getElementById('expiry-alert-days-row');
    if (!expToggle || !mfgToggle || !daysInput || !daysRow) return;

    var state = window.ERP.state;
    expToggle.checked = !!state.expiryDateEnabled;
    mfgToggle.checked = !!state.mfgDateEnabled;
    daysInput.value   = state.expiryAlertDays || 30;
    daysRow.classList.toggle('d-none', !state.expiryDateEnabled);

    function saveInventoryDates(revertFn){
        var days = parseInt(daysInput.value, 10);
        if (isNaN(days) || days < 1 || days > 365) {
            alert('Expiry alert days must be between 1 and 365.');
            revertFn();
            return;
        }
        ERP.api.updateInventoryDates(expToggle.checked, mfgToggle.checked, days).then(function(resp){
            window.ERP.state.expiryDateEnabled = resp.expiryDateEnabled;
            window.ERP.state.mfgDateEnabled    = resp.mfgDateEnabled;
            window.ERP.state.expiryAlertDays   = resp.expiryAlertDays;
            daysRow.classList.toggle('d-none', !resp.expiryDateEnabled);
        }).catch(function(e){
            alert('Error: ' + e.message);
            revertFn();
        });
    }

    expToggle.addEventListener('change', function(){
        saveInventoryDates(function(){ expToggle.checked = !expToggle.checked; });
    });
    mfgToggle.addEventListener('change', function(){
        saveInventoryDates(function(){ mfgToggle.checked = !mfgToggle.checked; });
    });
    daysInput.addEventListener('change', function(){
        saveInventoryDates(function(){ daysInput.value = window.ERP.state.expiryAlertDays || 30; });
    });
}
```

- [ ] **Step 6: Manual verify**

Open `http://localhost/erppos` → login as a Company Admin → Settings. Verify:
1. Module Settings card shows the two new switches; Expiry Alert Days row hidden.
2. Toggle Expiry Date Tracking ON → alert-days row appears; reload page → toggle stays ON (came back via sync/core).
3. Set alert days to 45 → reload → still 45. Set 0 → alert shown, value reverts.
4. Toggle Manufacturing Date Tracking ON → reload → stays ON.

- [ ] **Step 7: Commit**

```bash
git add resources/views/pages/settings.blade.php public/css/app.css public/js/api.js public/js/app.js public/js/pages/settings.js
git commit -m "feat: settings UI for expiry/mfg date tracking toggles"
```

---

### Task 5: Receive modal UI — batch/date inputs

**Files:**
- Modify: `resources/views/pages/purchases.blade.php` (receive modal, lines 177–217)
- Modify: `public/js/pages/purchases.js` (`openReceiveModal` ~line 411, `submitReceive` ~line 565)
- Modify: `public/css/app.css` (wide-modal class)

**Interfaces:**
- Consumes: `ERP.state.expiryDateEnabled` / `ERP.state.mfgDateEnabled` (Task 4); receive API accepting `batchNo`/`mfgDate`/`expiryDate` per item (Task 2).
- Produces: nothing consumed by later tasks.

- [ ] **Step 1: Add CSS class**

In `public/css/app.css` append:

```css
/* Receive modal — wider when batch/date columns are visible */
.modal-dialog-recv-wide { max-width: 920px; }
```

- [ ] **Step 2: Make the receive-table header dynamic**

In `resources/views/pages/purchases.blade.php`, replace the static `<thead>` block of the receive modal (lines 195–202):

```html
          <thead><tr id="recv-head"></tr></thead>
```

(The header cells are now built by JS so date columns appear only when enabled.)

- [ ] **Step 3: Build header + input columns in openReceiveModal**

In `public/js/pages/purchases.js` `openReceiveModal()`, after the `recv-date` line (line 422) and before `var tbody = ...`, add:

```js
  var expiryOn = !!window.ERP.state.expiryDateEnabled;
  var mfgOn    = !!window.ERP.state.mfgDateEnabled;
  var batchOn  = expiryOn || mfgOn;

  var dlg = document.querySelector('#receiveModal .modal-dialog');
  if (dlg) dlg.classList.toggle('modal-dialog-recv-wide', batchOn);

  var head = '<th class="po-th-col" style="width:36px;">#</th>' +
    '<th class="po-th-col">Product</th>' +
    '<th class="po-th-col text-center">Ordered</th>' +
    '<th class="po-th-col text-center">Received</th>' +
    '<th class="po-th-col text-center">Remaining</th>' +
    '<th class="po-th-col" style="width:120px;">Receive Qty</th>';
  if (batchOn)  head += '<th class="po-th-col">Batch No</th>';
  if (mfgOn)    head += '<th class="po-th-col">Mfg Date</th>';
  if (expiryOn) head += '<th class="po-th-col">Expiry Date</th>';
  document.getElementById('recv-head').innerHTML = head;
```

(The `style="width:36px;"` / `style="width:120px;"` header attributes replicate the removed static Blade markup verbatim — they move as-is, not new styling.)

Then inside the `(po.items || []).forEach` loop, the row currently ends with `'</div>' + '</td></tr>';`. Change the row build so the closing `</tr>` is appended after optional cells:

Replace (lines 430–443 ending):

```js
        '<div class="text-danger" style="font-size:0.72rem;min-height:14px;" id="recv-err-' + item.id + '"></div>' +
      '</td></tr>';
```

with:

```js
        '<div class="text-danger" style="font-size:0.72rem;min-height:14px;" id="recv-err-' + item.id + '"></div>' +
      '</td>';
    if (batchOn) {
      html += '<td class="po-td-input"><input type="text" class="form-control pm-input recv-batch po-input-sm" ' +
        'data-item-id="' + item.id + '" maxlength="255" placeholder="Batch/Lot"></td>';
    }
    if (mfgOn) {
      html += '<td class="po-td-input"><input type="date" class="form-control pm-input recv-mfg po-input-sm" ' +
        'data-item-id="' + item.id + '"></td>';
    }
    if (expiryOn) {
      html += '<td class="po-td-input"><input type="date" class="form-control pm-input recv-expiry po-input-sm" ' +
        'data-item-id="' + item.id + '"></td>';
    }
    html += '</tr>';
```

- [ ] **Step 4: Collect values + client-side date check in submitReceive**

In `submitReceive()` (line 565), inside the `inputs.forEach(function(inp) { ... if (qty > 0) { items.push({...}) } })` block, replace the `items.push({...})` call with:

```js
      var entry = {
        purchaseItemId: inp.dataset.itemId,
        productId: inp.dataset.productId,
        quantity: qty,
        unitCost: parseFloat(inp.dataset.unitCost) || 0
      };
      var itemId  = inp.dataset.itemId;
      var batchEl = document.querySelector('.recv-batch[data-item-id="' + itemId + '"]');
      var mfgEl   = document.querySelector('.recv-mfg[data-item-id="' + itemId + '"]');
      var expEl   = document.querySelector('.recv-expiry[data-item-id="' + itemId + '"]');
      if (batchEl && batchEl.value.trim()) entry.batchNo = batchEl.value.trim();
      if (mfgEl && mfgEl.value)   entry.mfgDate = mfgEl.value;
      if (expEl && expEl.value)   entry.expiryDate = expEl.value;
      items.push(entry);
```

Then, right after the `if (items.length === 0) { ... return; }` block, add a date sanity check before submitting:

```js
  var dateError = items.some(function(it) {
    return it.mfgDate && it.expiryDate && it.expiryDate <= it.mfgDate;
  });
  if (dateError) {
    var errBox2 = document.getElementById('recv-save-error');
    errBox2.textContent = 'Expiry date must be after the manufacturing date.';
    errBox2.classList.remove('d-none');
    return;
  }
```

Note: place this check BEFORE the `showConfirm(...)` call would be ideal, but the items array is built after it in the current flow — keep the existing order (confirm → build items → validate) and place the check where described; the server also rejects with 422 either way.

- [ ] **Step 5: Manual verify**

1. Both toggles OFF (Settings) → Purchases → Receive Goods: modal identical to before (6 columns, normal width).
2. Expiry ON only → modal wide, columns Batch No + Expiry Date visible (no Mfg Date).
3. Both ON → all three columns. Fill batch `LOT-A`, mfg today, expiry tomorrow → receive succeeds; check DB row:
   `/c/xampp/mysql/bin/mysql -u root -h 127.0.0.1 lean_erp -e "SELECT batch_no,mfg_date,expiry_date FROM purchase_receive_items ORDER BY created_at DESC LIMIT 1;"`
4. Expiry before mfg → inline error shown, nothing submitted.

- [ ] **Step 6: Commit**

```bash
git add resources/views/pages/purchases.blade.php public/js/pages/purchases.js public/css/app.css
git commit -m "feat: batch no + mfg/expiry date inputs in receive goods modal"
```

---

### Task 6: Reports UI — Expiry Report tile + panel

**Files:**
- Modify: `resources/views/pages/reports.blade.php` (tile in `rpt-tiles-grid` ~line 35; new panel after the Purchase Report Panel, ~line 501)
- Modify: `public/js/pages/reports.js` (`window.ERP.onReady` line 2; `rptOpen` branch; new run/render functions)
- Modify: `public/js/api.js` (two wrappers near the other report wrappers, ~line 220)

**Interfaces:**
- Consumes: `GET /api/reports/expiry` response shape (Task 3); `ERP.state.expiryDateEnabled` (Task 4); existing helpers `rptFetchReport`, `rptRenderPagination`, `rptBack`.
- Produces: `ERP.api.getExpiryReport(params)`, `ERP.api.getExpirySummary()` (Task 7 uses `getExpirySummary`).

- [ ] **Step 1: Add API wrappers**

In `public/js/api.js`, after `getPurchaseByVendorReport` (line 219–221), add:

```js
        getExpiryReport: function(params) {
            return request('GET', '/reports/expiry?' + new URLSearchParams(params).toString());
        },
        getExpirySummary: function() {
            return request('GET', '/reports/expiry-summary');
        },
```

- [ ] **Step 2: Add the tile**

In `resources/views/pages/reports.blade.php`, inside `.rpt-tiles-grid`, after the Detailed Purchase Report tile (line 75), add (hidden by default; JS shows it when the toggle is on):

```html
          <div class="rpt-tile d-none" id="rpt-tile-expiry" onclick="rptOpen('expiry')">
            <div class="rpt-tile-icon rpt-tile-icon-purchase"><i class="ti ti-calendar-x"></i></div>
            <div class="rpt-tile-body">
              <div class="rpt-tile-name">Expiry Report</div>
              <div class="rpt-tile-desc">Batch-wise expired and expiring-soon stock from goods receipts</div>
            </div>
            <div class="rpt-tile-arrow"><i class="ti ti-chevron-right"></i></div>
          </div>
```

- [ ] **Step 3: Add the panel**

After the Purchase Report Panel's closing `</div>` (line 501), add:

```html
      {{-- Expiry Report Panel --}}
      <div id="rpt-expiry-panel" class="d-none">
        <div class="rpt-report-header d-print-none">
          <button class="btn btn-light btn-sm" onclick="rptBack()"><i class="ti ti-arrow-left me-1"></i>Back</button>
          <span class="rpt-report-title"><i class="ti ti-calendar-x me-2"></i>Expiry Report</span>
          <div class="d-flex gap-2">
            <button class="btn btn-light btn-sm" onclick="window.print()"><i class="ti ti-printer me-1"></i>Print</button>
          </div>
        </div>

        <div class="rpt-filter-bar d-print-none">
          <div class="row g-2 align-items-end">
            <div class="col-auto">
              <label class="pm-label">Status</label>
              <select class="form-select inv-input" id="rptExpiryStatus">
                <option value="">All</option>
                <option value="expired">Expired</option>
                <option value="expiring_soon">Expiring Soon</option>
                <option value="ok">OK</option>
              </select>
            </div>
            <div class="col-auto">
              <button id="rptExpiryRunBtn" class="btn btn-primary rpt-btn" onclick="runExpiryReport()"><i class="ti ti-player-play me-1"></i>Run Report</button>
            </div>
          </div>
        </div>

        <div id="rpt-expiry-results">
          <div class="table-responsive">
            <table class="table table-vcenter inv-table mb-0 rpt-compact-table">
              <thead>
                <tr>
                  <th class="inv-th">Product</th>
                  <th class="inv-th">SKU</th>
                  <th class="inv-th">Batch No</th>
                  <th class="inv-th">Receive Date</th>
                  <th class="inv-th">Mfg Date</th>
                  <th class="inv-th">Expiry Date</th>
                  <th class="inv-th text-end">Received Qty</th>
                  <th class="inv-th">Status</th>
                </tr>
              </thead>
              <tbody id="rptExpiryBody"></tbody>
            </table>
          </div>
          <div id="rptExpirySummary"></div>
          <div id="rptExpiryPagination" class="d-print-none"></div>
          <div class="text-muted mt-2 rpt-expiry-note">Quantities shown are received quantities — a batch may have been partially or fully sold since receipt.</div>
        </div>
      </div>
```

Add to `public/css/app.css`:

```css
.rpt-expiry-note { font-size: 0.8rem; }
```

- [ ] **Step 4: Wire reports.js**

In `public/js/pages/reports.js`:

1. Replace line 2:

```js
window.ERP.onReady = function(){ /* reports page uses tile navigation — no auto-render needed */ };
```

with:

```js
window.ERP.onReady = function(){
    var expiryTile = document.getElementById('rpt-tile-expiry');
    if (expiryTile) expiryTile.classList.toggle('d-none', !window.ERP.state.expiryDateEnabled);
};
```

2. In `rptOpen(type)`, add a branch before the `} else if (type === 'profitLoss') {` branch:

```js
  } else if (type === 'expiry') {
    document.getElementById('rpt-expiry-panel').classList.remove('d-none');
    document.getElementById('rptExpiryStatus').value = '';
    document.getElementById('rptExpirySummary').innerHTML = '';
    var ePag = document.getElementById('rptExpiryPagination'); if (ePag) ePag.innerHTML = '';
    runExpiryReport();
```

3. Add these functions after `rptBuildPurchasePDF` (or at the end of the Detailed Purchase Report section):

```js
/* ====== Expiry Report ====== */
var _rptExpiryPage = 1;
function runExpiryReport(page) {
  _rptExpiryPage = page || 1;
  var params = { page: _rptExpiryPage, perPage: 50 };
  var status = document.getElementById('rptExpiryStatus').value;
  if (status) params.status = status;

  var btn = document.getElementById('rptExpiryRunBtn');
  if (btn) btn.disabled = true;
  document.getElementById('rptExpiryBody').innerHTML = '<tr><td colspan="8" class="text-center py-4"><span class="spinner-border spinner-border-sm"></span> Loading…</td></tr>';

  rptFetchReport(ERP.api.getExpiryReport, params)
    .then(function(resp){
      rptRenderExpiryRows(resp.data || []);
      rptRenderExpirySummary(resp.summary || {});
      rptRenderPagination('rptExpiryPagination', resp.pagination, function(p){ runExpiryReport(p); });
    })
    .catch(function(e){ alert('Error loading report: ' + e.message); })
    .finally(function(){ if (btn) btn.disabled = false; });
}

function rptRenderExpiryRows(rows) {
  var statusBadges = {
    expired:       '<span class="rpt-badge rpt-badge-red">Expired</span>',
    expiring_soon: '<span class="rpt-badge rpt-badge-amber">Expiring Soon</span>',
    ok:            '<span class="rpt-badge rpt-badge-green">OK</span>'
  };
  var html = '';
  rows.forEach(function(r){
    html += '<tr>' +
      '<td class="fw-bold">' + (r.productName || '—') + '</td>' +
      '<td>' + (r.sku || '—') + '</td>' +
      '<td>' + (r.batchNo || '—') + '</td>' +
      '<td>' + (r.receiveDate || '—') + '</td>' +
      '<td>' + (r.mfgDate || '—') + '</td>' +
      '<td>' + (r.expiryDate || '—') + '</td>' +
      '<td class="text-end">' + (r.quantity || 0) + '</td>' +
      '<td>' + (statusBadges[r.status] || '') + '</td>' +
      '</tr>';
  });
  if (!rows.length) {
    html = '<tr><td colspan="8" class="text-center text-muted py-5"><i class="ti ti-calendar-x d-block mb-2 fs-2"></i>No batches with expiry dates found</td></tr>';
  }
  document.getElementById('rptExpiryBody').innerHTML = html;
}

function rptRenderExpirySummary(summary) {
  var total = (summary.expired || 0) + (summary.expiringSoon || 0) + (summary.ok || 0);
  document.getElementById('rptExpirySummary').innerHTML = total
    ? '<div class="rpt-summary-bar d-print-none">' +
      '<span>Expired: <b>' + (summary.expired || 0) + '</b></span>' +
      '<span>Expiring Soon (&le;' + (summary.alertDays || 30) + ' days): <b>' + (summary.expiringSoon || 0) + '</b></span>' +
      '<span>OK: <b>' + (summary.ok || 0) + '</b></span>' +
      '</div>'
    : '';
}
```

- [ ] **Step 5: Manual verify**

1. Expiry toggle OFF → Reports page: no Expiry tile.
2. Toggle ON → tile appears. Open it → report auto-runs, shows the batches received in Task 5's verify with correct status badges and summary bar.
3. Status filter `Expired` → only expired rows. Pagination renders when > 50 rows (skip if few rows — verify no JS errors in console instead).

- [ ] **Step 6: Commit**

```bash
git add resources/views/pages/reports.blade.php public/js/pages/reports.js public/js/api.js public/css/app.css
git commit -m "feat: expiry report page with status filter and pagination"
```

---

### Task 7: Dashboard expiry alerts card

**Files:**
- Modify: `public/js/pages/dashboard.js` (`renderUserDashboard` KPI grid ~lines 110–137, and after `loadDashboardData(currentFilter)` ~line 161)
- Modify: `public/css/app.css` (red KPI icon class)

**Interfaces:**
- Consumes: `ERP.api.getExpirySummary()` (Task 6) returning `{expired, expiringSoon, ok, alertDays}`; `ERP.state.expiryDateEnabled` (Task 4).
- Produces: nothing consumed later.

- [ ] **Step 1: Add CSS class**

In `public/css/app.css`, find the existing `.db-kpi-icon-orange` rule and add a red variant next to it with the same structure (only the colors change). If the orange rule is e.g. `background: rgba(...); color: #F59E0B;`, mirror it:

```css
.db-kpi-icon-red { background: rgba(239, 68, 68, 0.12); color: #EF4444; }
```

(Match whatever property set `.db-kpi-icon-orange` actually uses — copy it and swap the color values.)

- [ ] **Step 2: Add the KPI card**

In `public/js/pages/dashboard.js` `renderUserDashboard()`, after the Pending Purchases card block (lines 131–136) and before `html += '</div>';` (line 137), add:

```js
  if (window.ERP.state.expiryDateEnabled) {
    html += '<div class="db-kpi-card">' +
      '<div class="db-kpi-icon-wrap db-kpi-icon-red"><i class="ti ti-calendar-x"></i></div>' +
      '<div class="db-kpi-label">Expiry Alerts</div>' +
      '<div class="db-kpi-value" id="kpi-expiry">--</div>' +
      '<div class="db-kpi-sub" id="kpi-expiry-sub">&nbsp;</div>' +
      '</div>';
  }
```

- [ ] **Step 3: Load the counts**

Still in `renderUserDashboard()`, after the `loadDashboardData(currentFilter);` call (line 161), add:

```js
  if (window.ERP.state.expiryDateEnabled) {
    ERP.api.getExpirySummary().then(function(s) {
      var el  = document.getElementById('kpi-expiry');
      var sub = document.getElementById('kpi-expiry-sub');
      if (el)  el.textContent  = (s.expired || 0) + (s.expiringSoon ? ' + ' + s.expiringSoon : '');
      if (sub) sub.textContent = (s.expired || 0) + ' expired · ' + (s.expiringSoon || 0) + ' expiring in ' + (s.alertDays || 30) + ' days';
    }).catch(function(e) {
      var el = document.getElementById('kpi-expiry');
      if (el) el.textContent = '—';
      console && console.error && console.error('Expiry summary failed: ' + e.message);
    });
  }
```

- [ ] **Step 4: Manual verify**

1. Expiry toggle OFF → Dashboard: 4 KPI cards, exactly as before.
2. Toggle ON → 5th card "Expiry Alerts" appears with the expired/expiring counts matching the Expiry Report summary.

- [ ] **Step 5: Commit**

```bash
git add public/js/pages/dashboard.js public/css/app.css
git commit -m "feat: dashboard expiry alerts card"
```

---

### Task 8: Full regression + end-to-end verification

**Files:** none (verification only).

- [ ] **Step 1: Run the entire test suite**

Run: `/c/xampp/php/php artisan test`
Expected: ALL tests pass (including pre-existing Auth/User/Party/Sale/Purchase/Payment suites). If any pre-existing test fails, fix the regression before proceeding.

- [ ] **Step 2: End-to-end smoke (browser)**

Full flow at `http://localhost/erppos`:
1. Settings → enable both toggles, alert days 30.
2. Purchases → create PO → Receive Goods with batch `LOT-E2E`, mfg = 2026-06-01, expiry = 2026-07-20 (within 30 days of 2026-07-05 → should classify Expiring Soon).
3. Reports → Expiry Report → row shows `LOT-E2E` with "Expiring Soon" badge.
4. Dashboard → Expiry Alerts card shows 1 expiring.
5. Settings → disable both toggles → receive modal back to normal, tile and card gone; the already-saved batch data remains in DB (re-enable to see it again).

- [ ] **Step 3: Commit any fixes**

```bash
git add -A
git commit -m "fix: address issues found in expiry-tracking end-to-end verification"
```

(Skip the commit if nothing changed.)
