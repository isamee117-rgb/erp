# Configurable GRN Mode Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a per-company `grn_enabled` setting; when ON, the current PO → Receive (GRN) flow is used; when OFF, creating a purchase order immediately receives the full quantity and adds it to inventory (no separate GRN step).

**Architecture:** Reuse the existing `PurchaseService::receiveOrder()` code path. When GRN is OFF, `createOrder()` builds a full-quantity receive payload and calls `receiveOrder()` internally, so stock, cost layers, inventory ledger, and vendor balance all update through the one tested path. The setting follows the established `job_card_mode` toggle pattern (Setting row → sync/core payload → `ERP.state`).

**Tech Stack:** Laravel 12 / PHP 8.2, MySQL/Eloquent, vanilla JS frontend (`window.ERP`), Blade views, PHPUnit (`erppos_test`).

## Global Constraints

- Controllers thin; business logic in `app/Services/`. Validation via Form Requests. JSON via API Resources.
- Get user via `$request->get('auth_user')`. All tenant queries scoped by `company_id`.
- Setting values are stored as string `'1'`/`'0'`. `grn_enabled` default is `'1'` (GRN ON) — existing companies unchanged.
- Frontend: no ES6 modules; follow the existing inline-handler style already used in `purchases.js` generated HTML (matching surrounding code). API calls only via `ERP.api.*`.
- Tests use PHPUnit `#[Test]` attributes, run on `erppos_test`.
- Run PHP with XAMPP: `/c/xampp/php/php artisan test`.

---

### Task 1: Backend — `grn_enabled` setting (save + sync)

**Files:**
- Modify: `app/Http/Controllers/Api/SettingsController.php` (add `updateGrnMode`, after `updateJobCardMode` ~line 56)
- Modify: `routes/api.php` (add route near `/settings/job-card-mode` ~line 88)
- Modify: `app/Services/SyncService.php` (add key to `whereIn` ~line 65; add payload field ~line 114)
- Test: `tests/Feature/GrnModeTest.php` (create)

**Interfaces:**
- Produces: `PUT /api/settings/grn-mode` accepts `{ grnEnabled: bool }`, returns `{ success: true, grnEnabled: bool }`; persists `settings` row `key=grn_enabled`, `value` `'1'`/`'0'` scoped by `company_id`.
- Produces: `GET /api/sync/core` returns `grnEnabled` (bool, default `true`).

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/GrnModeTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\DocumentSequenceService;
use PHPUnit\Framework\Attributes\Test;

class GrnModeTest extends ApiTestCase
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

    private function setGrn(bool $enabled): void
    {
        $this->putJson('/api/settings/grn-mode', ['grnEnabled' => $enabled], $this->auth())->assertOk();
    }

    private function createPO(array $itemOverrides = [], int $qty = 10, float $cost = 50): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/purchases', [
            'vendorId' => $this->vendor['id'],
            'items'    => [array_merge([
                'productId' => $this->product['id'],
                'quantity'  => $qty,
                'unitCost'  => $cost,
            ], $itemOverrides)],
        ], $this->auth());
    }

    #[Test]
    public function grn_mode_setting_is_saved(): void
    {
        $this->setGrn(false);
        $this->assertDatabaseHas('settings', [
            'company_id' => $this->company->id,
            'key'        => 'grn_enabled',
            'value'      => '0',
        ]);
    }

    #[Test]
    public function sync_core_returns_grn_setting(): void
    {
        $this->setGrn(false);
        $this->getJson('/api/sync/core', $this->auth())
            ->assertOk()
            ->assertJson(['grnEnabled' => false]);
    }

    #[Test]
    public function sync_core_defaults_grn_enabled_true(): void
    {
        $this->getJson('/api/sync/core', $this->auth())
            ->assertOk()
            ->assertJson(['grnEnabled' => true]);
    }
}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `/c/xampp/php/php artisan test --filter=GrnModeTest`
Expected: FAIL — route `/api/settings/grn-mode` not defined (404) / `grnEnabled` missing from sync payload.

- [ ] **Step 3: Add the controller method**

In `app/Http/Controllers/Api/SettingsController.php`, after `updateJobCardMode()` (~line 56):

```php
    public function updateGrnMode(Request $request)
    {
        $user = $request->get('auth_user');
        $mode = $request->input('grnEnabled') ? '1' : '0';
        Setting::updateOrCreate(
            ['company_id' => $user->company_id, 'key' => 'grn_enabled'],
            ['value' => $mode]
        );
        return response()->json(['success' => true, 'grnEnabled' => (bool) $mode]);
    }
```

- [ ] **Step 4: Register the route**

In `routes/api.php`, directly after the `/settings/job-card-mode` line (~line 88):

```php
        Route::put('/settings/grn-mode',           [SettingsController::class, 'updateGrnMode']);
```

- [ ] **Step 5: Expose the setting through sync/core**

In `app/Services/SyncService.php`, add `'grn_enabled'` to the `whereIn` key list (~line 65):

```php
            ->whereIn('key', ['currency', 'invoice_format', 'job_card_mode', 'expiry_date_enabled', 'mfg_date_enabled', 'expiry_alert_days', 'grn_enabled'])
```

Then add to the returned payload array, next to `jobCardMode` (~line 114):

```php
            'grnEnabled'         => (bool) ($settings->get('grn_enabled')?->value ?? true),
```

- [ ] **Step 6: Run the tests to verify they pass**

Run: `/c/xampp/php/php artisan test --filter=GrnModeTest`
Expected: PASS (3 tests: saved, sync returns, sync default).

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Api/SettingsController.php routes/api.php app/Services/SyncService.php tests/Feature/GrnModeTest.php
git commit -m "feat: grn_enabled setting save + sync"
```

---

### Task 2: Backend — Auto-receive on PO create when GRN is OFF

**Files:**
- Modify: `app/Http/Requests/StorePurchaseOrderRequest.php` (add batch/date item rules + `withValidator`)
- Modify: `app/Services/PurchaseService.php` (`createOrder`, ~line 27–80: read setting, auto-receive)
- Modify: `app/Http/Controllers/Api/PurchaseController.php` (`store`, ~line 60–64: post journal when a receive was created)
- Test: `tests/Feature/GrnModeTest.php` (add cases)

**Interfaces:**
- Consumes: `Setting` model, `PurchaseService::receiveOrder(User $user, string $id, array $receiveItems, string $notes, ?string $receiveDate = null)` (existing), `JournalPostingService::postPurchaseReceive($receive, $userId)` (existing).
- Produces: `createOrder()` returns a `PurchaseOrder` with status `Received` and a loaded `receives` relation when GRN is OFF; returns a `Draft` PO (unchanged) when GRN is ON. Receive-item date fields accepted on create: `items.*.batchNo`, `items.*.mfgDate`, `items.*.expiryDate`.

- [ ] **Step 1: Write the failing tests**

Append to `tests/Feature/GrnModeTest.php` (inside the class):

```php
    #[Test]
    public function grn_off_receives_po_immediately(): void
    {
        $this->setGrn(false);
        $stockBefore   = Product::find($this->product['id'])->current_stock;
        $vendorBalBefore = \App\Models\Party::find($this->vendor['id'])->current_balance;

        $resp = $this->createPO([], 10, 50);
        $resp->assertOk()->assertJsonPath('status', 'Received');

        $this->assertEquals($stockBefore + 10, Product::find($this->product['id'])->current_stock);
        $this->assertEquals($vendorBalBefore + 500, \App\Models\Party::find($this->vendor['id'])->current_balance);

        $this->assertDatabaseHas('inventory_ledger', [
            'company_id'       => $this->company->id,
            'product_id'       => $this->product['id'],
            'transaction_type' => 'Purchase_Receive',
            'quantity_change'  => 10,
        ]);
        $this->assertDatabaseHas('inventory_cost_layers', [
            'company_id' => $this->company->id,
            'product_id' => $this->product['id'],
        ]);
    }

    #[Test]
    public function grn_on_leaves_po_draft_with_no_stock_change(): void
    {
        // Default is GRN ON — do not change the setting.
        $stockBefore = Product::find($this->product['id'])->current_stock;

        $this->createPO([], 10, 50)->assertOk()->assertJsonPath('status', 'Draft');

        $this->assertEquals($stockBefore, Product::find($this->product['id'])->current_stock);
        $this->assertDatabaseMissing('inventory_ledger', [
            'company_id'       => $this->company->id,
            'product_id'       => $this->product['id'],
            'transaction_type' => 'Purchase_Receive',
        ]);
    }

    #[Test]
    public function grn_off_stores_batch_and_dates(): void
    {
        $this->setGrn(false);
        $this->createPO([
            'batchNo'    => 'LOT-9',
            'mfgDate'    => '2026-01-01',
            'expiryDate' => '2027-01-01',
        ], 5, 20)->assertOk();

        $this->assertDatabaseHas('purchase_receive_items', [
            'product_id'  => $this->product['id'],
            'batch_no'    => 'LOT-9',
            'mfg_date'    => '2026-01-01',
            'expiry_date' => '2027-01-01',
        ]);
    }

    #[Test]
    public function po_create_rejects_expiry_before_mfg(): void
    {
        $this->setGrn(false);
        $this->createPO([
            'mfgDate'    => '2026-06-01',
            'expiryDate' => '2026-05-01',
        ], 5, 20)->assertStatus(422);
    }

    #[Test]
    public function grn_setting_is_company_scoped(): void
    {
        $this->setGrn(false); // this company: GRN OFF

        $otherCo    = $this->createCompany(['name' => 'Other Co']);
        $otherAdmin = $this->createAdminUser($otherCo, ['username' => 'otheradmin']);
        $otherToken = $this->loginAndGetToken($otherAdmin);
        app(DocumentSequenceService::class)->ensureSequencesExist($otherCo->id);

        $vendorB  = $this->createParty('Vendor', ['company_id' => $otherCo->id]);
        $productB = $this->createProduct(['company_id' => $otherCo->id]);

        // Other company never set grn_enabled → default ON → PO stays Draft.
        $this->postJson('/api/purchases', [
            'vendorId' => $vendorB['id'],
            'items'    => [['productId' => $productB['id'], 'quantity' => 3, 'unitCost' => 10]],
        ], $this->auth($otherToken))->assertOk()->assertJsonPath('status', 'Draft');
    }
```

> Note: confirm `createParty()` / `createProduct()` accept a `company_id` override in `ApiTestCase`. If they do not, create the vendor/product for the other company using the model factory pattern already used elsewhere in that helper. Check `tests/Feature/ApiTestCase.php` before running.

- [ ] **Step 2: Run the tests to verify they fail**

Run: `/c/xampp/php/php artisan test --filter=GrnModeTest`
Expected: the 5 new tests FAIL — GRN-off PO currently returns `Draft` with no stock change; batch/date fields on create are ignored; expiry-before-mfg is not rejected on create.

- [ ] **Step 3: Add validation rules to `StorePurchaseOrderRequest`**

Replace the `rules()` array in `app/Http/Requests/StorePurchaseOrderRequest.php` and add `withValidator()`:

```php
    public function rules(): array
    {
        return [
            'vendorId'             => 'required|string|exists:parties,id',
            'items'                => 'required|array|min:1',
            'items.*.productId'    => 'required|string|exists:products,id',
            'items.*.uomId'        => 'sometimes|nullable|string|exists:units_of_measure,id',
            'items.*.quantity'     => 'required|integer|min:1',
            'items.*.unitCost'     => 'sometimes|numeric|min:0',
            'items.*.batchNo'      => 'nullable|string|max:255',
            'items.*.mfgDate'      => 'nullable|date',
            'items.*.expiryDate'   => 'nullable|date',
            'orderDate'            => 'sometimes|nullable|date',
        ];
    }

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

- [ ] **Step 4: Auto-receive in `PurchaseService::createOrder`**

Add the `Setting` import at the top of `app/Services/PurchaseService.php` (with the other `use App\Models\...` lines):

```php
use App\Models\Setting;
```

In `createOrder()`, replace the final two lines:

```php
        $po->load('items');
        return $po;
```

with:

```php
        if ($this->grnEnabled($user->company_id)) {
            $po->load('items');
            return $po;
        }

        $receiveItems = [];
        foreach (($data['items'] ?? []) as $i => $item) {
            $receiveItems[] = [
                'purchaseItemId' => $mappedItems[$i]['id'],
                'productId'      => $mappedItems[$i]['product_id'],
                'quantity'       => $mappedItems[$i]['quantity'],
                'unitCost'       => $mappedItems[$i]['unit_cost'],
                'batchNo'        => $item['batchNo'] ?? $item['batch_no'] ?? null,
                'mfgDate'        => $item['mfgDate'] ?? $item['mfg_date'] ?? null,
                'expiryDate'     => $item['expiryDate'] ?? $item['expiry_date'] ?? null,
            ];
        }

        return $this->receiveOrder($user, $po->id, $receiveItems, 'Auto-received (GRN disabled)');
```

Then add this private helper at the end of the class (with the other private helpers, before the closing brace):

```php
    private function grnEnabled(?string $companyId): bool
    {
        $value = Setting::where('company_id', $companyId)
            ->where('key', 'grn_enabled')
            ->value('value');

        return $value === null ? true : $value === '1';
    }
```

- [ ] **Step 5: Post the journal for auto-received POs in the controller**

In `app/Http/Controllers/Api/PurchaseController.php`, replace `store()` (~line 60–64) with:

```php
    public function store(StorePurchaseOrderRequest $request)
    {
        $user = $request->get('auth_user');
        $po   = $this->purchaseService->createOrder($user, $request->validated());

        $journalWarning = null;
        $latestReceive  = $po->receives()->latest()->first();
        if ($latestReceive) {
            try {
                $this->journalService->postPurchaseReceive($latestReceive, $user->id);
            } catch (\Throwable $e) {
                Log::error('Journal posting failed for auto-received PO', ['po_id' => $po->id, 'error' => $e->getMessage()]);
                $journalWarning = $e->getMessage();
            }
        }

        return (new PurchaseOrderResource($po))->additional(array_filter(['warning' => $journalWarning]));
    }
```

(`Log`, `JournalPostingService`, and `PurchaseOrderResource` are already imported/injected in this controller.)

- [ ] **Step 6: Run the tests to verify they pass**

Run: `/c/xampp/php/php artisan test --filter=GrnModeTest`
Expected: PASS (all 8 tests). Then run the existing purchase suite to confirm no regression:

Run: `/c/xampp/php/php artisan test --filter=PurchaseTest`
Expected: PASS (GRN-ON default path unchanged).

- [ ] **Step 7: Commit**

```bash
git add app/Http/Requests/StorePurchaseOrderRequest.php app/Services/PurchaseService.php app/Http/Controllers/Api/PurchaseController.php tests/Feature/GrnModeTest.php
git commit -m "feat: auto-receive purchase order when GRN mode is off"
```

---

### Task 3: Frontend — settings toggle + PO create date inputs

**Files:**
- Modify: `public/js/app.js` (state default ~line 33)
- Modify: `public/js/api.js` (add `updateGrnMode` ~line 475)
- Modify: `resources/views/pages/settings.blade.php` (add GRN toggle row ~line 379)
- Modify: `public/js/pages/settings.js` (add `initGrnModeToggle` + call it ~line 44)
- Modify: `resources/views/pages/purchases.blade.php` (give PO-create header row an id, ~line 152)
- Modify: `public/js/pages/purchases.js` (`renderPOItems`, `updatePOItem`, `createPO`)

**Interfaces:**
- Consumes: `ERP.state.grnEnabled` (bool), `ERP.state.expiryDateEnabled`, `ERP.state.mfgDateEnabled`; `ERP.api.updateGrnMode(enabled)`.
- Produces: `ERP.api.updateGrnMode(enabled)` → `PUT /settings/grn-mode`. PO create payload items carry optional `batchNo`/`mfgDate`/`expiryDate` when GRN is OFF and expiry/mfg tracking is ON.

- [ ] **Step 1: Add the state default**

In `public/js/app.js`, in the `state` object next to `jobCardMode: false,` (~line 33), add:

```js
        grnEnabled: true,
```

- [ ] **Step 2: Add the API wrapper**

In `public/js/api.js`, after the `updateJobCardMode` wrapper (~line 475), add:

```js
        updateGrnMode: function(enabled) {
            return request('PUT', '/settings/grn-mode', { grnEnabled: enabled });
        },
```

- [ ] **Step 3: Add the settings toggle markup**

In `resources/views/pages/settings.blade.php`, inside the Module Settings card, immediately after the Job Card Mode row's closing `</div>` (~line 379, before the `<hr class="my-3">` that precedes Expiry Date Tracking), insert:

```html
        <hr class="my-3">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <div class="fw-semibold set-module-title">Goods Receipt (GRN)</div>
            <div class="text-muted set-module-desc">When on, purchase orders need a separate receive step. When off, creating a PO adds stock immediately.</div>
          </div>
          <div class="form-check form-switch ms-3">
            <input class="form-check-input set-switch-lg" type="checkbox" id="setting-grn-mode" role="switch">
          </div>
        </div>
```

- [ ] **Step 4: Wire the toggle in settings.js**

In `public/js/pages/settings.js`, in the init function where `initJobCardModeToggle();` and `initInventoryDatesToggles();` are called (~line 44), add:

```js
    initGrnModeToggle();
```

Then add the function (place it next to `initJobCardModeToggle`):

```js
function initGrnModeToggle(){
    var toggle = document.getElementById('setting-grn-mode');
    if (!toggle) return;
    toggle.checked = !!(window.ERP.state.grnEnabled);
    toggle.addEventListener('change', function(){
        var enabled = toggle.checked;
        ERP.api.updateGrnMode(enabled).then(function(){
            window.ERP.state.grnEnabled = enabled;
        }).catch(function(e){ alert('Error: ' + e.message); toggle.checked = !enabled; });
    });
}
```

- [ ] **Step 5: Give the PO-create header row an id**

In `resources/views/pages/purchases.blade.php` (~line 152), change:

```html
          <thead><tr>
```

to:

```html
          <thead><tr id="npo-head">
```

- [ ] **Step 6: Build header + date cells in `renderPOItems`**

In `public/js/pages/purchases.js`, replace the whole `renderPOItems` function (~line 311–326) with:

```js
function renderPOItems() {
  var grnOff   = window.ERP.state.grnEnabled === false;
  var expiryOn = grnOff && !!window.ERP.state.expiryDateEnabled;
  var mfgOn    = grnOff && !!window.ERP.state.mfgDateEnabled;
  var batchOn  = expiryOn || mfgOn;

  var head = '<th class="po-th-col" style="width:36px;">#</th>' +
    '<th class="po-th-col">Product</th>' +
    '<th class="po-th-col" style="width:90px;">UOM</th>' +
    '<th class="po-th-col" style="width:80px;">Qty</th>' +
    '<th class="po-th-col" style="width:110px;">Unit Cost</th>';
  if (batchOn)  head += '<th class="po-th-col">Batch No</th>';
  if (mfgOn)    head += '<th class="po-th-col">Mfg Date</th>';
  if (expiryOn) head += '<th class="po-th-col">Expiry Date</th>';
  head += '<th class="po-th-col" style="width:110px;">Line Total</th>' +
    '<th class="po-th-act" style="width:36px;"></th>';
  document.getElementById('npo-head').innerHTML = head;

  var tbody = document.getElementById('npo-items');
  var html = '';
  poItems.forEach(function(item, idx) {
    html += '<tr>' +
      '<td class="po-td-input" style="color:#9CA3AF;font-size:0.78rem;text-align:center;">' + (idx + 1) + '</td>' +
      '<td class="po-td-input">' + renderProductSDD(idx, item.productId) + '</td>' +
      renderUomCell(idx, item) +
      '<td class="po-td-input"><input type="number" class="form-control pm-input text-center po-input-sm" value="' + item.quantity + '" onchange="updatePOItem(' + idx + ',\'quantity\',this.value)"></td>' +
      '<td class="po-td-input"><input type="number" step="0.01" class="form-control pm-input po-input-sm" value="' + item.unitCost + '" onchange="updatePOItem(' + idx + ',\'unitCost\',this.value)"></td>';
    if (batchOn) {
      html += '<td class="po-td-input"><input type="text" class="form-control pm-input po-input-sm" maxlength="255" placeholder="Batch/Lot" value="' + (item.batchNo || '') + '" onchange="updatePOItem(' + idx + ',\'batchNo\',this.value)"></td>';
    }
    if (mfgOn) {
      html += '<td class="po-td-input"><input type="date" class="form-control pm-input po-input-sm" value="' + (item.mfgDate || '') + '" onchange="updatePOItem(' + idx + ',\'mfgDate\',this.value)"></td>';
    }
    if (expiryOn) {
      html += '<td class="po-td-input"><input type="date" class="form-control pm-input po-input-sm" value="' + (item.expiryDate || '') + '" onchange="updatePOItem(' + idx + ',\'expiryDate\',this.value)"></td>';
    }
    html += '<td class="po-td-input text-end" id="po-line-total-' + idx + '" style="font-weight:600;color:#1A1D2E;white-space:nowrap;">' + ERP.formatCurrency(item.quantity * item.unitCost) + '</td>' +
      '<td class="po-td-input text-center"><button type="button" class="po-del-btn" onclick="removePOItem(' + idx + ')"><i class="ti ti-x"></i></button></td></tr>';
  });
  tbody.innerHTML = html;
  updatePOTotal();
}
```

- [ ] **Step 7: Store the date fields in `updatePOItem`**

In `public/js/pages/purchases.js`, in `updatePOItem` (~line 328), add these branches before the closing brace of the `if/else if` chain (after the `unitCost` branch, ~line 367):

```js
  } else if (field === 'batchNo') {
    poItems[idx].batchNo = value;
  } else if (field === 'mfgDate') {
    poItems[idx].mfgDate = value;
  } else if (field === 'expiryDate') {
    poItems[idx].expiryDate = value;
```

- [ ] **Step 8: Include the date fields in the create payload**

In `public/js/pages/purchases.js`, in `createPO` (~line 392), replace the `validItems` mapping with:

```js
  var validItems = poItems.filter(function(i) { return i.productId; }).map(function(i) {
    var out = { productId: i.productId, uomId: i.uomId || null, quantity: i.quantity, unitCost: i.unitCost };
    if (i.batchNo)    out.batchNo = i.batchNo;
    if (i.mfgDate)    out.mfgDate = i.mfgDate;
    if (i.expiryDate) out.expiryDate = i.expiryDate;
    return out;
  });
```

- [ ] **Step 9: Manual verification (no JS test harness in this project)**

Start the app and confirm both modes. Use the `run` skill if available, otherwise:

Run: `/c/xampp/php/php artisan serve` and open `http://localhost/erppos`.

Verify:
1. Settings → Module Settings shows the **Goods Receipt (GRN)** toggle; toggling it persists (reload page, state holds).
2. GRN ON: create a PO → it appears as **Draft** with a **Receive Goods** button (unchanged behaviour).
3. GRN OFF: create a PO → it appears as **Received**, no Receive button, and product stock increased by the ordered qty.
4. GRN OFF + Expiry/Mfg tracking ON: the New PO modal item rows show Batch No / Mfg Date / Expiry Date inputs; after saving, the values appear in the Expiry Report.

Note: hiding the Receive button in GRN-OFF mode needs **no code change** — auto-received POs have status `Received`, which the existing PO-list render already treats as non-receivable.

- [ ] **Step 10: Commit**

```bash
git add public/js/app.js public/js/api.js public/js/pages/settings.js public/js/pages/purchases.js resources/views/pages/settings.blade.php resources/views/pages/purchases.blade.php
git commit -m "feat: GRN mode settings toggle + PO-create batch/date inputs"
```

---

## Self-Review

**Spec coverage:**
- §1 Setup toggle → Task 1 (controller, route, sync) + Task 3 (state, api, blade, settings.js). ✓
- §2 Auto-receive → Task 2 (service + controller journal). ✓
- §3 Frontend PO create date inputs + hidden receive button → Task 3 (steps 5–8; button hiding is automatic, noted step 9). ✓
- §4 Validation → Task 2 step 3. ✓
- §5 Error handling (journal non-fatal warning) → Task 2 step 5. ✓
- §6 Out of scope — no POS/report/status-label/existing-PO changes made. ✓
- §7 Testing → Task 1 (3 tests) + Task 2 (5 tests) cover settings persist/sync/default, GRN-off receive, GRN-on draft, dates stored, expiry<mfg reject, company scoping. ✓

**Placeholder scan:** No TBD/TODO; all code shown. One explicit pre-check note added (verify `createParty`/`createProduct` accept `company_id` override in `ApiTestCase`) — this is a verification instruction, not a placeholder.

**Type consistency:** `grnEnabled` (JSON/JS) ↔ `grn_enabled` (setting key) used consistently; `updateGrnMode` name matches route→controller→api.js; `grnEnabled()` service helper returns bool; receive payload keys (`purchaseItemId`, `productId`, `quantity`, `unitCost`, `batchNo`, `mfgDate`, `expiryDate`) match the shapes `receiveOrder()` already reads.
