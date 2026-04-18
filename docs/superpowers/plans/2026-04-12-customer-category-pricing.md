# Customer Category Pricing Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Allow each product to define per-category prices; when a sale is made to a customer, the server and POS automatically apply the price matching the customer's `Party.category` field, falling back to `unit_price` when no tier exists.

**Architecture:** A new `product_price_tiers` table stores `(product_id, company_id, category, price)` — one row per customer-category tier per product. `SaleService.createSale()` resolves tier price server-side using the customer's `Party.category`. The POS re-renders prices when a customer is selected. Purchases are unaffected — they use `unit_cost`, not `unit_price`. All existing sales without a matching tier continue to use `product.unit_price` (zero impact on existing data).

**Tech Stack:** Laravel 12 (PHP 8.2), Eloquent, MySQL, Vanilla JS, Tabler/Bootstrap 5, PHPUnit 11

---

## File Map

| Status | File | Change |
|--------|------|--------|
| **Create** | `database/migrations/2026_04_12_000001_create_product_price_tiers_table.php` | New table |
| **Create** | `app/Models/ProductPriceTier.php` | New model |
| **Create** | `app/Http/Resources/ProductPriceTierResource.php` | New resource |
| **Create** | `tests/Feature/ProductPriceTierTest.php` | Feature tests |
| **Modify** | `app/Models/Product.php` | Add `priceTiers()` hasMany |
| **Modify** | `app/Http/Resources/ProductResource.php` | Add `priceTiers` field |
| **Modify** | `app/Services/SyncService.php` | Eager load `priceTiers` on products |
| **Modify** | `routes/api.php` | 3 new price-tier routes |
| **Modify** | `app/Http/Controllers/Api/ProductController.php` | 3 new tier methods |
| **Modify** | `app/Services/SaleService.php` | Resolve tier price in `createSale()` |
| **Modify** | `public/js/api.js` | 3 new tier API wrappers |
| **Modify** | `resources/views/pages/inventory.blade.php` | Add `pf-price-tiers-section` div |
| **Modify** | `public/js/pages/inventory.js` | Tier management UI in product modal |
| **Modify** | `public/js/pages/pos.js` | Resolve tier price when customer selected |

---

## Task 1: Migration — `product_price_tiers` table

**Files:**
- Create: `database/migrations/2026_04_12_000001_create_product_price_tiers_table.php`

- [ ] **Step 1: Write the failing test for table existence**

File: `tests/Feature/ProductPriceTierTest.php` — create this file now (it will grow with each task):

```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPriceTierTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function product_price_tiers_table_exists(): void
    {
        $this->assertTrue(
            \Illuminate\Support\Facades\Schema::hasTable('product_price_tiers'),
            'product_price_tiers table should exist'
        );
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
/c/xampp/php/php artisan test tests/Feature/ProductPriceTierTest.php --filter product_price_tiers_table_exists
```

Expected: FAIL — `product_price_tiers table should exist`

- [ ] **Step 3: Create the migration**

```php
<?php
// database/migrations/2026_04_12_000001_create_product_price_tiers_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_price_tiers', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('product_id');
            $table->string('company_id');
            $table->string('category');
            $table->decimal('price', 15, 4);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->unique(['product_id', 'category'], 'unique_product_category_tier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_price_tiers');
    }
};
```

- [ ] **Step 4: Run migration**

```bash
/c/xampp/php/php artisan migrate
```

Expected: `2026_04_12_000001_create_product_price_tiers_table ...DONE`

- [ ] **Step 5: Run test to verify it passes**

```bash
/c/xampp/php/php artisan test tests/Feature/ProductPriceTierTest.php --filter product_price_tiers_table_exists
```

Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_04_12_000001_create_product_price_tiers_table.php tests/Feature/ProductPriceTierTest.php
git commit -m "feat: add product_price_tiers migration"
```

---

## Task 2: Model `ProductPriceTier` + `Product` relationship

**Files:**
- Create: `app/Models/ProductPriceTier.php`
- Modify: `app/Models/Product.php:12-27` (add relationship)

- [ ] **Step 1: Write failing test for relationship**

Add to `tests/Feature/ProductPriceTierTest.php`:

```php
#[Test]
public function product_has_many_price_tiers(): void
{
    $company = \App\Models\Company::create([
        'id' => 'CO-test00001', 'name' => 'Test Co', 'status' => 'active',
    ]);
    $product = \App\Models\Product::create([
        'id' => 'PRD-test00001', 'company_id' => $company->id,
        'sku' => 'SKU-01', 'item_number' => 'IT-001',
        'name' => 'Widget', 'type' => 'Product',
        'uom' => 'pcs', 'unit_cost' => 10, 'unit_price' => 20,
    ]);

    \App\Models\ProductPriceTier::create([
        'id' => 'PPT-test00001', 'product_id' => $product->id,
        'company_id' => $company->id, 'category' => 'Wholesale', 'price' => 15,
    ]);

    $this->assertCount(1, $product->priceTiers);
    $this->assertEquals('Wholesale', $product->priceTiers->first()->category);
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
/c/xampp/php/php artisan test tests/Feature/ProductPriceTierTest.php --filter product_has_many_price_tiers
```

Expected: FAIL — class `ProductPriceTier` not found

- [ ] **Step 3: Create `ProductPriceTier` model**

```php
<?php
// app/Models/ProductPriceTier.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductPriceTier extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'product_id',
        'company_id',
        'category',
        'price',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
```

- [ ] **Step 4: Add `priceTiers()` relationship to `Product` model**

In `app/Models/Product.php`, after the `uomConversions()` method (line ~42), add:

```php
public function priceTiers()
{
    return $this->hasMany(ProductPriceTier::class);
}
```

- [ ] **Step 5: Run test to verify it passes**

```bash
/c/xampp/php/php artisan test tests/Feature/ProductPriceTierTest.php --filter product_has_many_price_tiers
```

Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Models/ProductPriceTier.php app/Models/Product.php tests/Feature/ProductPriceTierTest.php
git commit -m "feat: add ProductPriceTier model and Product relationship"
```

---

## Task 3: `ProductPriceTierResource`

**Files:**
- Create: `app/Http/Resources/ProductPriceTierResource.php`

- [ ] **Step 1: Create the resource**

```php
<?php
// app/Http/Resources/ProductPriceTierResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductPriceTierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'productId' => $this->product_id,
            'companyId' => $this->company_id,
            'category'  => $this->category,
            'price'     => (float) $this->price,
        ];
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Http/Resources/ProductPriceTierResource.php
git commit -m "feat: add ProductPriceTierResource"
```

---

## Task 4: `ProductResource` — include `priceTiers`

**Files:**
- Modify: `app/Http/Resources/ProductResource.php`

- [ ] **Step 1: Add import and `priceTiers` field to `ProductResource`**

In `app/Http/Resources/ProductResource.php`, add the import at the top:

```php
use App\Http\Resources\ProductPriceTierResource;
```

Then in the `toArray()` return array, after `'uomConversions'`, add:

```php
'priceTiers' => ProductPriceTierResource::collection(
    $this->whenLoaded('priceTiers')
),
```

The full `toArray()` should look like:

```php
public function toArray(Request $request): array
{
    return [
        'id'             => $this->id,
        'companyId'      => $this->company_id,
        'sku'            => $this->sku          ?? '',
        'barcode'        => $this->barcode      ?? '',
        'itemNumber'     => $this->item_number  ?? '',
        'name'           => $this->name,
        'type'           => $this->type,
        'uom'            => $this->uom          ?? '',
        'baseUomId'      => $this->base_uom_id  ?? null,
        'categoryId'     => $this->category_id,
        'currentStock'   => (int)   $this->current_stock,
        'reorderLevel'   => (int)   $this->reorder_level,
        'unitCost'       => (float) $this->unit_cost,
        'unitPrice'      => (float) $this->unit_price,
        'uomConversions' => ProductUomConversionResource::collection(
            $this->whenLoaded('uomConversions')
        ),
        'priceTiers'     => ProductPriceTierResource::collection(
            $this->whenLoaded('priceTiers')
        ),
    ];
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Http/Resources/ProductResource.php
git commit -m "feat: expose priceTiers in ProductResource"
```

---

## Task 5: `SyncService` — eager load `priceTiers`

**Files:**
- Modify: `app/Services/SyncService.php:113`

- [ ] **Step 1: Update `getMasterData()` to eager load price tiers**

In `app/Services/SyncService.php`, change line 113 from:

```php
$products = $this->scopedQuery(Product::with('uomConversions.uom'), $isSuper, $coId);
```

to:

```php
$products = $this->scopedQuery(Product::with(['uomConversions.uom', 'priceTiers']), $isSuper, $coId);
```

- [ ] **Step 2: Verify no test regressions**

```bash
/c/xampp/php/php artisan test
```

Expected: All existing tests PASS

- [ ] **Step 3: Commit**

```bash
git add app/Services/SyncService.php
git commit -m "feat: eager load priceTiers in SyncService getMasterData"
```

---

## Task 6: API Routes for price tier CRUD

**Files:**
- Modify: `routes/api.php`

- [ ] **Step 1: Add three price-tier routes after the existing UOM conversion routes**

In `routes/api.php`, after line 46 (`Route::delete('/products/{id}/uom-conversions/{cid}', ...)`), add:

```php
// Price tiers per product
Route::post('/products/{id}/price-tiers', [ProductController::class, 'storePriceTier']);
Route::put('/products/{id}/price-tiers/{tid}', [ProductController::class, 'updatePriceTier']);
Route::delete('/products/{id}/price-tiers/{tid}', [ProductController::class, 'destroyPriceTier']);
```

- [ ] **Step 2: Verify routes registered**

```bash
/c/xampp/php/php artisan route:list --path=products
```

Expected: Lines for `POST products/{id}/price-tiers`, `PUT products/{id}/price-tiers/{tid}`, `DELETE products/{id}/price-tiers/{tid}`

- [ ] **Step 3: Commit**

```bash
git add routes/api.php
git commit -m "feat: add price tier API routes"
```

---

## Task 7: `ProductController` — price tier CRUD methods

**Files:**
- Modify: `app/Http/Controllers/Api/ProductController.php`

- [ ] **Step 1: Write failing tests for tier CRUD endpoints**

Add to `tests/Feature/ProductPriceTierTest.php`:

```php
private function makeProduct(string $companyId): \App\Models\Product
{
    return \App\Models\Product::create([
        'id' => 'PRD-' . \Illuminate\Support\Str::random(9),
        'company_id' => $companyId, 'sku' => 'SKU-' . uniqid(),
        'item_number' => 'IT-' . uniqid(), 'name' => 'Widget',
        'type' => 'Product', 'uom' => 'pcs',
        'unit_cost' => 10, 'unit_price' => 20,
    ]);
}

#[Test]
public function price_tier_can_be_created(): void
{
    [$company, $user] = $this->createCompanyAndAdmin();
    $product = $this->makeProduct($company->id);

    $res = $this->auth($user)
        ->postJson("/api/products/{$product->id}/price-tiers", [
            'category' => 'Wholesale', 'price' => 15,
        ]);

    $res->assertStatus(200)
        ->assertJsonPath('category', 'Wholesale')
        ->assertJsonPath('price', 15.0);

    $this->assertDatabaseHas('product_price_tiers', [
        'product_id' => $product->id, 'category' => 'Wholesale', 'price' => 15,
    ]);
}

#[Test]
public function duplicate_category_tier_is_rejected(): void
{
    [$company, $user] = $this->createCompanyAndAdmin();
    $product = $this->makeProduct($company->id);

    $this->auth($user)->postJson("/api/products/{$product->id}/price-tiers", [
        'category' => 'Wholesale', 'price' => 15,
    ]);

    $res = $this->auth($user)->postJson("/api/products/{$product->id}/price-tiers", [
        'category' => 'Wholesale', 'price' => 12,
    ]);

    $res->assertStatus(422);
}

#[Test]
public function price_tier_can_be_updated(): void
{
    [$company, $user] = $this->createCompanyAndAdmin();
    $product = $this->makeProduct($company->id);

    $createRes = $this->auth($user)->postJson("/api/products/{$product->id}/price-tiers", [
        'category' => 'VIP', 'price' => 10,
    ]);
    $tierId = $createRes->json('id');

    $res = $this->auth($user)->putJson("/api/products/{$product->id}/price-tiers/{$tierId}", [
        'price' => 8,
    ]);

    $res->assertStatus(200)->assertJsonPath('price', 8.0);
    $this->assertDatabaseHas('product_price_tiers', ['id' => $tierId, 'price' => 8]);
}

#[Test]
public function price_tier_can_be_deleted(): void
{
    [$company, $user] = $this->createCompanyAndAdmin();
    $product = $this->makeProduct($company->id);

    $createRes = $this->auth($user)->postJson("/api/products/{$product->id}/price-tiers", [
        'category' => 'Retail', 'price' => 18,
    ]);
    $tierId = $createRes->json('id');

    $res = $this->auth($user)->deleteJson("/api/products/{$product->id}/price-tiers/{$tierId}");
    $res->assertStatus(200)->assertJsonPath('success', true);
    $this->assertDatabaseMissing('product_price_tiers', ['id' => $tierId]);
}
```

> **Note:** `createCompanyAndAdmin()` is a helper you need to add to `ApiTestCase` (or inline in this test). Add this helper to `tests/Feature/ApiTestCase.php`:
>
> ```php
> protected function createCompanyAndAdmin(): array
> {
>     $company = $this->createCompany();
>     $user    = $this->createAdminUser($company->id);
>     return [$company, $user];
> }
> ```

- [ ] **Step 2: Run tests to verify they fail**

```bash
/c/xampp/php/php artisan test tests/Feature/ProductPriceTierTest.php
```

Expected: FAIL on all four new tests — `storePriceTier`, `updatePriceTier`, `destroyPriceTier` methods not found.

- [ ] **Step 3: Add imports to `ProductController`**

At the top of `app/Http/Controllers/Api/ProductController.php`, add these imports:

```php
use App\Http\Resources\ProductPriceTierResource;
use App\Models\ProductPriceTier;
```

- [ ] **Step 4: Add `storePriceTier` method to `ProductController`**

Add after the `destroyUomConversion` method:

```php
// ── Price Tiers ───────────────────────────────────────────────────────────

public function storePriceTier(Request $request, string $id)
{
    $product  = Product::findOrFail($id);
    $data     = $request->all();
    $category = trim($data['category'] ?? '');
    $price    = $data['price'] ?? null;

    if (empty($category)) {
        return response()->json(['error' => 'category is required'], 422);
    }
    if ($price === null || (float) $price < 0) {
        return response()->json(['error' => 'price must be a non-negative number'], 422);
    }

    $exists = ProductPriceTier::where('product_id', $product->id)
        ->where('category', $category)
        ->exists();

    if ($exists) {
        return response()->json(['error' => 'A price tier for this category already exists on this product'], 422);
    }

    $tier = ProductPriceTier::create([
        'id'         => 'PPT-' . Str::random(9),
        'product_id' => $product->id,
        'company_id' => $product->company_id,
        'category'   => $category,
        'price'      => (float) $price,
    ]);

    return new ProductPriceTierResource($tier);
}
```

- [ ] **Step 5: Add `updatePriceTier` method to `ProductController`**

```php
public function updatePriceTier(Request $request, string $id, string $tid)
{
    $tier  = ProductPriceTier::where('product_id', $id)->where('id', $tid)->firstOrFail();
    $data  = $request->all();
    $price = $data['price'] ?? null;

    if ($price === null || (float) $price < 0) {
        return response()->json(['error' => 'price must be a non-negative number'], 422);
    }

    $tier->price = (float) $price;
    $tier->save();

    return new ProductPriceTierResource($tier);
}
```

- [ ] **Step 6: Add `destroyPriceTier` method to `ProductController`**

```php
public function destroyPriceTier(string $id, string $tid)
{
    $tier = ProductPriceTier::where('product_id', $id)->where('id', $tid)->firstOrFail();
    $tier->delete();
    return response()->json(['success' => true]);
}
```

- [ ] **Step 7: Run tests to verify they pass**

```bash
/c/xampp/php/php artisan test tests/Feature/ProductPriceTierTest.php
```

Expected: All tests PASS

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/Api/ProductController.php tests/Feature/ProductPriceTierTest.php tests/Feature/ApiTestCase.php
git commit -m "feat: add price tier CRUD endpoints to ProductController"
```

---

## Task 8: `SaleService` — resolve tier price server-side

**Files:**
- Modify: `app/Services/SaleService.php`

- [ ] **Step 1: Write failing tests for tier price resolution**

Add to `tests/Feature/ProductPriceTierTest.php`:

```php
#[Test]
public function sale_applies_tier_price_for_customer_category(): void
{
    [$company, $user] = $this->createCompanyAndAdmin();
    $product = $this->makeProduct($company->id);

    // Add a Wholesale tier at price 15 (base unit_price is 20)
    \App\Models\ProductPriceTier::create([
        'id' => 'PPT-' . \Illuminate\Support\Str::random(9),
        'product_id' => $product->id, 'company_id' => $company->id,
        'category' => 'Wholesale', 'price' => 15,
    ]);

    // Stock the product
    $product->current_stock = 10;
    $product->save();

    // Create a customer with category = Wholesale
    $customer = \App\Models\Party::create([
        'id' => 'PAR-' . \Illuminate\Support\Str::random(9),
        'company_id' => $company->id, 'code' => 'C001',
        'type' => 'Customer', 'name' => 'Wholesale Co',
        'category' => 'Wholesale', 'current_balance' => 0,
    ]);

    $res = $this->auth($user)->postJson('/api/sales', [
        'customerId'    => $customer->id,
        'paymentMethod' => 'Cash',
        'items'         => [
            ['productId' => $product->id, 'quantity' => 2, 'discount' => 0],
        ],
    ]);

    $res->assertStatus(200);
    // unit_price on sale item should be 15 (tier price), not 20 (base price)
    $this->assertDatabaseHas('sale_items', [
        'product_id' => $product->id, 'unit_price' => 15,
    ]);
}

#[Test]
public function sale_falls_back_to_unit_price_when_no_tier(): void
{
    [$company, $user] = $this->createCompanyAndAdmin();
    $product = $this->makeProduct($company->id);
    $product->current_stock = 10;
    $product->save();

    // Customer with category = VIP but no VIP tier on product
    $customer = \App\Models\Party::create([
        'id' => 'PAR-' . \Illuminate\Support\Str::random(9),
        'company_id' => $company->id, 'code' => 'C002',
        'type' => 'Customer', 'name' => 'VIP Co',
        'category' => 'VIP', 'current_balance' => 0,
    ]);

    $res = $this->auth($user)->postJson('/api/sales', [
        'customerId'    => $customer->id,
        'paymentMethod' => 'Cash',
        'items'         => [
            ['productId' => $product->id, 'quantity' => 1, 'discount' => 0],
        ],
    ]);

    $res->assertStatus(200);
    // unit_price should be 20 (base unit_price), since no VIP tier exists
    $this->assertDatabaseHas('sale_items', [
        'product_id' => $product->id, 'unit_price' => 20,
    ]);
}

#[Test]
public function sale_falls_back_to_unit_price_when_customer_has_no_category(): void
{
    [$company, $user] = $this->createCompanyAndAdmin();
    $product = $this->makeProduct($company->id);

    \App\Models\ProductPriceTier::create([
        'id' => 'PPT-' . \Illuminate\Support\Str::random(9),
        'product_id' => $product->id, 'company_id' => $company->id,
        'category' => 'Wholesale', 'price' => 15,
    ]);

    $product->current_stock = 10;
    $product->save();

    // Customer with no category set
    $customer = \App\Models\Party::create([
        'id' => 'PAR-' . \Illuminate\Support\Str::random(9),
        'company_id' => $company->id, 'code' => 'C003',
        'type' => 'Customer', 'name' => 'Walk-in',
        'category' => null, 'current_balance' => 0,
    ]);

    $res = $this->auth($user)->postJson('/api/sales', [
        'customerId'    => $customer->id,
        'paymentMethod' => 'Cash',
        'items'         => [
            ['productId' => $product->id, 'quantity' => 1, 'discount' => 0],
        ],
    ]);

    $res->assertStatus(200);
    $this->assertDatabaseHas('sale_items', [
        'product_id' => $product->id, 'unit_price' => 20,
    ]);
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
/c/xampp/php/php artisan test tests/Feature/ProductPriceTierTest.php --filter "sale_applies_tier_price|sale_falls_back"
```

Expected: FAIL — `unit_price` will be 20 in all cases (tier not applied yet)

- [ ] **Step 3: Move `$customerId` extraction before the items loop in `SaleService.createSale()`**

In `app/Services/SaleService.php`, the current `createSale()` method sets `$customerId` AFTER the items loop (around line 63). Move these two lines to BEFORE the `foreach ($data['items'] ?? [] as $item)` loop:

```php
$customerId    = $data['customerId'] ?? $data['customer_id'] ?? null;
$paymentMethod = $data['paymentMethod'] ?? $data['payment_method'] ?? 'Cash';
```

Remove them from their original location after the loop.

- [ ] **Step 4: Change `$unitPrice` resolution in the items loop**

In `SaleService.createSale()`, replace:

```php
$unitPrice   = ($product?->unit_price ?? 0) * $multiplier;
```

with:

```php
$unitPrice   = $this->resolveTierPrice($product, $customerId) * $multiplier;
```

- [ ] **Step 5: Add `resolveTierPrice` private method to `SaleService`**

Add after the `updateSaleReturnStatus` method at the bottom of the class:

```php
private function resolveTierPrice(?Product $product, ?string $customerId): float
{
    if (!$product) return 0.0;
    if (!$customerId) return (float) $product->unit_price;

    $customer = Party::find($customerId);
    if (!$customer || empty($customer->category)) {
        return (float) $product->unit_price;
    }

    // Load tiers lazily — Eloquent will cache after first access
    $tier = $product->priceTiers->firstWhere('category', $customer->category);
    return $tier ? (float) $tier->price : (float) $product->unit_price;
}
```

- [ ] **Step 6: Run tests to verify they pass**

```bash
/c/xampp/php/php artisan test tests/Feature/ProductPriceTierTest.php
```

Expected: All tests PASS

- [ ] **Step 7: Run full test suite to confirm no regressions**

```bash
/c/xampp/php/php artisan test
```

Expected: All tests PASS

- [ ] **Step 8: Commit**

```bash
git add app/Services/SaleService.php tests/Feature/ProductPriceTierTest.php
git commit -m "feat: resolve customer category tier price in SaleService"
```

---

## Task 9: `api.js` — tier API wrappers

**Files:**
- Modify: `public/js/api.js`

- [ ] **Step 1: Add three tier API methods after `deleteUomConversion`**

In `public/js/api.js`, after the `deleteUomConversion` method, add:

```js
savePriceTier: function(productId, data) {
    return request('POST', '/products/' + productId + '/price-tiers', data);
},
updatePriceTier: function(productId, tid, data) {
    return request('PUT', '/products/' + productId + '/price-tiers/' + tid, data);
},
deletePriceTier: function(productId, tid) {
    return request('DELETE', '/products/' + productId + '/price-tiers/' + tid);
},
```

- [ ] **Step 2: Commit**

```bash
git add public/js/api.js
git commit -m "feat: add price tier API wrappers to api.js"
```

---

## Task 10: `inventory.blade.php` — add price tiers section div

**Files:**
- Modify: `resources/views/pages/inventory.blade.php`

- [ ] **Step 1: Add price tiers div after `pf-uom-section`**

In `resources/views/pages/inventory.blade.php`, find line 292:

```html
{{-- UOM Conversions (edit mode only, populated by JS) --}}
<div id="pf-uom-section" style="display:none;"></div>
```

Add immediately after it:

```html
{{-- Price Tiers (edit mode only, populated by JS) --}}
<div id="pf-price-tiers-section" style="display:none;"></div>
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/pages/inventory.blade.php
git commit -m "feat: add pf-price-tiers-section div to product modal"
```

---

## Task 11: `inventory.js` — price tier management UI

**Files:**
- Modify: `public/js/pages/inventory.js`

- [ ] **Step 1: Add `renderPriceTiersSection` and helpers after `renderUomConversionsSection`**

Find the existing `renderUomConversionsSection` function in `public/js/pages/inventory.js`. After it ends, add these functions:

```js
function renderPriceTiersSection(product) {
  var section = document.getElementById('pf-price-tiers-section');
  section.style.display = '';
  var tiers = product.priceTiers || [];

  var html = '<div class="pm-tier-wrap">' +
    '<div class="pm-tier-header">' +
      '<span><i class="ti ti-tag me-1"></i>Customer Category Pricing</span>' +
      '<button type="button" class="pm-tier-add-btn" onclick="openAddPriceTierRow()"><i class="ti ti-plus me-1"></i>Add Tier</button>' +
    '</div>';

  if (tiers.length === 0) {
    html += '<div class="pm-tier-empty">No price tiers — all customers pay the base unit price.</div>';
  } else {
    html += '<table class="pm-tier-table"><thead><tr><th>Customer Category</th><th class="text-end">Price</th><th></th></tr></thead><tbody>';
    tiers.forEach(function(t) {
      html += '<tr>' +
        '<td>' + escHtml(t.category) + '</td>' +
        '<td class="text-end">' + ERP.formatCurrency(t.price) + '</td>' +
        '<td class="text-center">' +
          '<button class="inv-action-btn inv-action-danger" onclick="confirmDeletePriceTier(\'' + escHtml(t.id) + '\',\'' + escHtml(t.category) + '\')" title="Remove tier"><i class="ti ti-trash"></i></button>' +
        '</td></tr>';
    });
    html += '</tbody></table>';
  }

  html += '<div id="pm-tier-add-row" style="display:none;">' +
    '<div class="pm-tier-add-form row g-2 mt-1">' +
      '<div class="col-5"><input type="text" class="form-control pm-input" id="pm-tier-cat" placeholder="e.g. Wholesale, VIP, Retail"></div>' +
      '<div class="col-4"><div class="input-group"><span class="input-group-text pm-prefix">Rs.</span><input type="number" step="0.01" min="0" class="form-control pm-input" id="pm-tier-price" placeholder="0.00"></div></div>' +
      '<div class="col-3 d-flex gap-1">' +
        '<button type="button" class="pm-btn-save" onclick="savePriceTier()"><i class="ti ti-check me-1"></i>Add</button>' +
        '<button type="button" class="pm-btn-cancel" onclick="document.getElementById(\'pm-tier-add-row\').style.display=\'none\'">Cancel</button>' +
      '</div>' +
    '</div>' +
  '</div>' +
  '</div>';

  section.innerHTML = html;
}

function openAddPriceTierRow() {
  document.getElementById('pm-tier-add-row').style.display = '';
  document.getElementById('pm-tier-cat').value = '';
  document.getElementById('pm-tier-price').value = '';
  document.getElementById('pm-tier-cat').focus();
}

async function savePriceTier() {
  var productId = document.getElementById('pf-id').value;
  var category  = (document.getElementById('pm-tier-cat').value || '').trim();
  var price     = parseFloat(document.getElementById('pm-tier-price').value);
  if (!category) { alert('Customer category name is required'); return; }
  if (isNaN(price) || price < 0) { alert('Price must be 0 or greater'); return; }
  try {
    await ERP.api.savePriceTier(productId, { category: category, price: price });
    await ERP.sync();
    var product = (window.ERP.state.products || []).find(function(p) { return p.id === productId; });
    if (product) { renderPriceTiersSection(product); }
  } catch(e) {
    alert('Error: ' + e.message);
  }
}

function confirmDeletePriceTier(tierId, categoryName) {
  if (!confirm('Remove price tier for "' + categoryName + '"?')) return;
  deletePriceTierById(tierId);
}

async function deletePriceTierById(tierId) {
  var productId = document.getElementById('pf-id').value;
  try {
    await ERP.api.deletePriceTier(productId, tierId);
    await ERP.sync();
    var product = (window.ERP.state.products || []).find(function(p) { return p.id === productId; });
    if (product) { renderPriceTiersSection(product); }
  } catch(e) {
    alert('Error: ' + e.message);
  }
}
```

- [ ] **Step 2: Call `renderPriceTiersSection(p)` in `openProductModal` edit mode**

In `openProductModal()`, find this existing call (around line 184):

```js
renderUomConversionsSection(p);
```

Add directly after it:

```js
renderPriceTiersSection(p);
```

- [ ] **Step 3: Hide/reset price tiers section in add mode**

In `openProductModal()`, in the `else` (add mode) branch, find where `uomSection.innerHTML = '';` is set, and add after it:

```js
var tierSection = document.getElementById('pf-price-tiers-section');
tierSection.style.display = 'none';
tierSection.innerHTML = '';
```

- [ ] **Step 4: Add CSS for tier UI to `public/css/app.css`**

Append to `public/css/app.css`:

```css
/* ── Product Price Tiers ─────────────────────────────────────── */
.pm-tier-wrap          { margin-top: 1rem; border: 1px solid #e6e7ee; border-radius: 6px; overflow: hidden; }
.pm-tier-header        { display: flex; align-items: center; justify-content: space-between;
                         padding: .5rem .75rem; background: #f8f9fc; font-size: .8rem; font-weight: 600; color: #4a4c6a; }
.pm-tier-add-btn       { font-size: .78rem; padding: .2rem .6rem; border: 1px solid #c5c7db;
                         background: #fff; border-radius: 5px; cursor: pointer; color: #4a4c6a; }
.pm-tier-add-btn:hover { background: #eef0ff; }
.pm-tier-empty         { padding: .6rem .75rem; font-size: .8rem; color: #9193a6; font-style: italic; }
.pm-tier-table         { width: 100%; font-size: .82rem; border-collapse: collapse; }
.pm-tier-table th,
.pm-tier-table td      { padding: .4rem .75rem; border-top: 1px solid #eef0f7; }
.pm-tier-table th      { background: #f3f4fb; font-weight: 600; font-size: .78rem; color: #6163a0; }
.pm-tier-add-form      { padding: .5rem .75rem; background: #f8f9fc; }
```

- [ ] **Step 5: Commit**

```bash
git add public/js/pages/inventory.js public/css/app.css
git commit -m "feat: add price tier management UI to product modal"
```

---

## Task 12: `pos.js` — apply tier price when customer is selected

**Files:**
- Modify: `public/js/pages/pos.js`

- [ ] **Step 1: Add `posSelectedCustomerCategory` variable**

At the top of `public/js/pages/pos.js`, after the existing variable declarations (`var posCart = [];` etc.), add:

```js
var posSelectedCustomerCategory = null;
```

- [ ] **Step 2: Add `resolveProductPrice` helper**

After the `getDefaultSalesConversion` function, add:

```js
function resolveProductPrice(product) {
  if (!posSelectedCustomerCategory) return product.unitPrice || 0;
  var tiers = product.priceTiers || [];
  for (var i = 0; i < tiers.length; i++) {
    if (tiers[i].category === posSelectedCustomerCategory) return tiers[i].price;
  }
  return product.unitPrice || 0;
}
```

- [ ] **Step 3: Update `sddSelectCustomer` to resolve customer category**

Find the existing `sddSelectCustomer` function (around line 59):

```js
function sddSelectCustomer(customerId, customerName) {
  document.getElementById('pos-customer').value = customerId;
  document.getElementById('pos-customer-disp').textContent = customerName;
  document.getElementById('pos-customer-disp').style.color = '#1A1D2E';
  var trigger = document.getElementById('pos-customer-trigger');
  trigger.classList.remove('is-invalid');
  document.querySelectorAll('.sdd-wrap.open').forEach(function(w) { w.classList.remove('open'); });
}
```

Replace it with:

```js
function sddSelectCustomer(customerId, customerName) {
  document.getElementById('pos-customer').value = customerId;
  document.getElementById('pos-customer-disp').textContent = customerName;
  document.getElementById('pos-customer-disp').style.color = '#1A1D2E';
  var trigger = document.getElementById('pos-customer-trigger');
  trigger.classList.remove('is-invalid');
  document.querySelectorAll('.sdd-wrap.open').forEach(function(w) { w.classList.remove('open'); });

  // Resolve customer category for tier pricing
  var parties = window.ERP.state.parties || [];
  var customer = parties.find(function(p) { return p.id === customerId; });
  posSelectedCustomerCategory = customer ? (customer.category || null) : null;

  // Re-render with resolved prices
  renderProducts();
  renderCart();
}
```

- [ ] **Step 4: Update `renderProducts` to show resolved tier price**

In `renderProducts()`, find the product card price line (around line 124):

```js
'<span class="pos-product-price">' + ERP.formatCurrency(p.unitPrice || 0) + '</span>' +
```

Replace with:

```js
'<span class="pos-product-price">' + ERP.formatCurrency(resolveProductPrice(p)) + '</span>' +
```

- [ ] **Step 5: Update `renderCart` to use resolved tier price**

In `renderCart()`, find line 238:

```js
var unitPriceInUom = p.unitPrice * multiplier;
```

Replace with:

```js
var unitPriceInUom = resolveProductPrice(p) * multiplier;
```

- [ ] **Step 6: Reset `posSelectedCustomerCategory` when cart is cleared**

In `clearCart()`, add the reset:

```js
function clearCart() {
  posCart = [];
  posSelectedCustomerCategory = null;
  document.getElementById('pos-customer').value = '';
  document.getElementById('pos-customer-disp').textContent = 'Select Customer (Optional)';
  document.getElementById('pos-customer-disp').style.color = '';
  renderProducts();
  renderCart();
}
```

> **Note:** Only add the two new lines (`posSelectedCustomerCategory = null;` and the customer display reset) if `clearCart()` doesn't already reset the customer display. Check the existing `clearCart()` body and add only what's missing.

- [ ] **Step 7: Commit**

```bash
git add public/js/pages/pos.js
git commit -m "feat: apply customer category tier price in POS"
```

---

## Task 13: End-to-end smoke test (manual)

- [ ] **Step 1: Run migrations fresh with seed**

```bash
/c/xampp/php/php artisan migrate:fresh --seed
```

- [ ] **Step 2: Run full test suite**

```bash
/c/xampp/php/php artisan test
```

Expected: All tests PASS — no regressions

- [ ] **Step 3: Manual POS smoke test**

1. Log in to `http://localhost/erppos`
2. Go to **Product Master** → Edit any product → verify **Customer Category Pricing** section appears with "Add Tier" button
3. Add tier: category = `Wholesale`, price = lower than unit price → verify it appears in the table
4. Go to **POS** → add product to cart → note price shows base `unitPrice`
5. Select a customer whose `category` is `Wholesale` → verify product price in grid and cart updates to the tier price
6. Select a customer with no category → price stays at `unitPrice`
7. Complete a sale with the wholesale customer → go to Sales → verify `unit_price` on the sale item matches the tier price

- [ ] **Step 4: Final commit**

```bash
git add -A
git commit -m "feat: customer category-based pricing complete"
```

---

## Self-Review Checklist

### Spec Coverage
| Requirement | Task |
|-------------|------|
| Price tiers on Product Master | Tasks 1–4, 10–11 |
| Linked with supply chain (sales) | Task 8 |
| No impact on existing application | `unit_price` is always the fallback; no existing fields changed; purchases untouched |
| Customer category determines price | Party.category field (already exists) used as tier key |
| POS applies tier price | Task 12 |
| Server-side validation | Task 8 — server resolves price, not trusting frontend |

### Impact Analysis
- **Purchases** — no change. `PurchaseService` uses `unitCost`, never `unitPrice` or tiers.
- **Sale Returns** — no change. Returns inherit `unit_price` from the original `sale_items` snapshot.
- **Existing sales** — no change. Historical data unaffected.
- **Walk-in (no customer)** — falls back to `unit_price`. Zero change.
- **Customers without category** — falls back to `unit_price`. Zero change.
- **Products without tiers** — `priceTiers` is empty; `whenLoaded` returns `[]` in JSON. Zero change.
