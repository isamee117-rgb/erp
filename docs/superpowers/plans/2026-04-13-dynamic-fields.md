# Dynamic Fields Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add 22 predefined toggleable industry-specific fields to Product and Party master records, configurable per company from the Settings page, with inline rendering in modals, filtering, and column visibility in list pages.

**Architecture:** Actual nullable DB columns on `products` and `parties` tables (always exist, UI shows/hides based on per-company settings stored in `company_field_settings`). A hardcoded PHP registry in `app/Config/DynamicFields.php` defines all 22 fields. `FieldSettingService` enforces the "cannot disable if data exists" rule.

**Tech Stack:** Laravel 12, PHP 8.2, MySQL, Vanilla JS, Bootstrap 5 / Tabler CSS. Tests via `php artisan test` (PHPUnit 11, `erppos_test` DB). XAMPP PHP at `/c/xampp/php/php`.

---

## File Map

**Create:**
| File | Purpose |
|------|---------|
| `app/Config/DynamicFields.php` | Hardcoded registry of all 22 predefined fields |
| `app/Models/CompanyFieldSetting.php` | Eloquent model for per-company field on/off state |
| `app/Services/FieldSettingService.php` | Business logic: getSettings, updateSetting, canDisable |
| `app/Http/Controllers/Api/FieldSettingController.php` | Thin API controller |
| `app/Http/Resources/FieldSettingResource.php` | camelCase JSON for field setting rows |
| `app/Http/Requests/StoreProductRequest.php` | Validation for product store (standard + dynamic fields) |
| `app/Http/Requests/UpdateProductRequest.php` | Validation for product update (standard + dynamic fields) |
| `database/migrations/2026_04_13_000001_add_dynamic_fields_to_products.php` | 18 nullable columns on products |
| `database/migrations/2026_04_13_000002_add_dynamic_fields_to_parties.php` | 4 nullable columns on parties |
| `database/migrations/2026_04_13_000003_create_company_field_settings_table.php` | company_field_settings table |
| `tests/Feature/FieldSettingTest.php` | API tests for field settings |

**Modify:**
| File | Change |
|------|--------|
| `app/Models/Product.php` | Add 18 dynamic field keys to `$fillable` |
| `app/Models/Party.php` | Add 4 dynamic field keys to `$fillable` |
| `app/Http/Resources/ProductResource.php` | Add all 18 dynamic field keys to toArray() |
| `app/Http/Resources/PartyResource.php` | Add all 4 dynamic field keys to toArray() |
| `app/Http/Requests/StorePartyRequest.php` | Add 4 customer field nullable rules |
| `app/Http/Requests/UpdatePartyRequest.php` | Add 4 customer field nullable rules |
| `app/Http/Controllers/Api/ProductController.php` | Use StoreProductRequest/UpdateProductRequest, handle dynamic fields |
| `app/Http/Controllers/Api/PartyController.php` | Add dynamic fields to store/update arrays |
| `app/Services/SyncService.php` | Add `fieldSettings` key to getMasterData() |
| `routes/api.php` | Register field-settings routes + use FieldSettingController |
| `resources/views/pages/settings.blade.php` | Add Dynamic Fields card section |
| `public/js/pages/settings.js` | Dynamic Fields toggle logic |
| `public/js/api.js` | Add `getFieldSettings()`, `updateFieldSetting()` wrappers |
| `resources/views/pages/inventory.blade.php` | Add `pf-dynamic-fields` container div in product modal |
| `public/js/pages/inventory.js` | Render dynamic fields in product modal, collect on save, filter+columns |
| `resources/views/pages/parties.blade.php` | Add `pty-dynamic-fields` container div in party modal |
| `public/js/pages/parties.js` | Render dynamic fields in party modal, collect on save, filter+columns |

---

## Task 1: Database Migrations

**Files:**
- Create: `database/migrations/2026_04_13_000001_add_dynamic_fields_to_products.php`
- Create: `database/migrations/2026_04_13_000002_add_dynamic_fields_to_parties.php`
- Create: `database/migrations/2026_04_13_000003_create_company_field_settings_table.php`

- [ ] **Step 1: Create products dynamic fields migration**

```php
<?php
// database/migrations/2026_04_13_000001_add_dynamic_fields_to_products.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('brand_name', 255)->nullable()->after('unit_price');
            $table->string('size', 100)->nullable()->after('brand_name');
            $table->string('color', 100)->nullable()->after('size');
            $table->string('style', 100)->nullable()->after('color');
            $table->string('bin_shelf_location', 255)->nullable()->after('style');
            $table->date('expiry_date')->nullable()->after('bin_shelf_location');
            $table->string('batch_lot_number', 255)->nullable()->after('expiry_date');
            $table->string('storage_condition', 50)->nullable()->after('batch_lot_number');
            $table->string('drug_composition', 255)->nullable()->after('storage_condition');
            $table->string('schedule_category', 20)->nullable()->after('drug_composition');
            $table->string('manufacturer_name', 255)->nullable()->after('schedule_category');
            $table->string('dosage_form', 50)->nullable()->after('manufacturer_name');
            $table->string('storage_temp_req', 255)->nullable()->after('dosage_form');
            $table->string('part_number', 255)->nullable()->after('storage_temp_req');
            $table->string('vehicle_compatibility', 255)->nullable()->after('part_number');
            $table->boolean('core_charge_flag')->nullable()->after('vehicle_compatibility');
            $table->string('warranty_period', 255)->nullable()->after('core_charge_flag');
            $table->text('technical_specs')->nullable()->after('warranty_period');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'brand_name', 'size', 'color', 'style', 'bin_shelf_location',
                'expiry_date', 'batch_lot_number', 'storage_condition',
                'drug_composition', 'schedule_category', 'manufacturer_name',
                'dosage_form', 'storage_temp_req', 'part_number',
                'vehicle_compatibility', 'core_charge_flag',
                'warranty_period', 'technical_specs',
            ]);
        });
    }
};
```

- [ ] **Step 2: Create parties dynamic fields migration**

```php
<?php
// database/migrations/2026_04_13_000002_add_dynamic_fields_to_parties.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            $table->string('vehicle_reg_number', 100)->nullable()->after('current_balance');
            $table->string('vin_chassis_number', 100)->nullable()->after('vehicle_reg_number');
            $table->string('engine_number', 100)->nullable()->after('vin_chassis_number');
            $table->decimal('last_odometer_reading', 10, 2)->nullable()->after('engine_number');
        });
    }

    public function down(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            $table->dropColumn([
                'vehicle_reg_number', 'vin_chassis_number',
                'engine_number', 'last_odometer_reading',
            ]);
        });
    }
};
```

- [ ] **Step 3: Create company_field_settings migration**

```php
<?php
// database/migrations/2026_04_13_000003_create_company_field_settings_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_field_settings', function (Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->string('company_id', 20);
            $table->string('entity_type', 20); // 'product' | 'customer'
            $table->string('field_key', 50);
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->unique(['company_id', 'entity_type', 'field_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_field_settings');
    }
};
```

- [ ] **Step 4: Run migrations**

```bash
/c/xampp/php/php artisan migrate
```

Expected: `2026_04_13_000001_add_dynamic_fields_to_products ... done`
`2026_04_13_000002_add_dynamic_fields_to_parties ... done`
`2026_04_13_000003_create_company_field_settings_table ... done`

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_04_13_000001_add_dynamic_fields_to_products.php
git add database/migrations/2026_04_13_000002_add_dynamic_fields_to_parties.php
git add database/migrations/2026_04_13_000003_create_company_field_settings_table.php
git commit -m "feat: add dynamic field migrations (products, parties, company_field_settings)"
```

---

## Task 2: Field Registry

**Files:**
- Create: `app/Config/DynamicFields.php`

- [ ] **Step 1: Create the registry file**

```php
<?php
// app/Config/DynamicFields.php
namespace App\Config;

class DynamicFields
{
    public static function all(): array
    {
        return array_merge(self::productFields(), self::customerFields());
    }

    public static function productFields(): array
    {
        return [
            ['key' => 'brand_name',           'label' => 'Brand Name',                          'entity' => 'product', 'type' => 'text',     'options' => [], 'industry_hint' => 'retail'],
            ['key' => 'size',                  'label' => 'Size',                                'entity' => 'product', 'type' => 'text',     'options' => [], 'industry_hint' => 'retail'],
            ['key' => 'color',                 'label' => 'Color',                               'entity' => 'product', 'type' => 'text',     'options' => [], 'industry_hint' => 'retail'],
            ['key' => 'style',                 'label' => 'Style',                               'entity' => 'product', 'type' => 'text',     'options' => [], 'industry_hint' => 'retail'],
            ['key' => 'bin_shelf_location',    'label' => 'Bin/Shelf Location',                  'entity' => 'product', 'type' => 'text',     'options' => [], 'industry_hint' => 'retail'],
            ['key' => 'expiry_date',           'label' => 'Expiry Date',                         'entity' => 'product', 'type' => 'date',     'options' => [], 'industry_hint' => 'grocery/pharmacy'],
            ['key' => 'batch_lot_number',      'label' => 'Batch/Lot Number',                    'entity' => 'product', 'type' => 'text',     'options' => [], 'industry_hint' => 'grocery/pharmacy'],
            ['key' => 'storage_condition',     'label' => 'Storage Condition',                   'entity' => 'product', 'type' => 'dropdown', 'options' => ['Ambient', 'Chilled', 'Frozen'], 'industry_hint' => 'grocery/pharmacy'],
            ['key' => 'drug_composition',      'label' => 'Drug Composition / Generic Name',     'entity' => 'product', 'type' => 'text',     'options' => [], 'industry_hint' => 'pharmacy'],
            ['key' => 'schedule_category',     'label' => 'Schedule Category',                   'entity' => 'product', 'type' => 'dropdown', 'options' => ['H', 'H1', 'X', 'OTC'], 'industry_hint' => 'pharmacy'],
            ['key' => 'manufacturer_name',     'label' => 'Manufacturer Name',                   'entity' => 'product', 'type' => 'text',     'options' => [], 'industry_hint' => 'grocery/pharmacy'],
            ['key' => 'dosage_form',           'label' => 'Dosage Form',                         'entity' => 'product', 'type' => 'dropdown', 'options' => ['Tablet', 'Syrup', 'Injection', 'Capsule'], 'industry_hint' => 'pharmacy'],
            ['key' => 'storage_temp_req',      'label' => 'Storage Temperature Requirements',    'entity' => 'product', 'type' => 'text',     'options' => [], 'industry_hint' => 'grocery/pharmacy'],
            ['key' => 'part_number',           'label' => 'Part Number (OEM/Aftermarket)',        'entity' => 'product', 'type' => 'text',     'options' => [], 'industry_hint' => 'automobile'],
            ['key' => 'vehicle_compatibility', 'label' => 'Vehicle Compatibility (Make/Model/Year)', 'entity' => 'product', 'type' => 'text', 'options' => [], 'industry_hint' => 'automobile'],
            ['key' => 'core_charge_flag',      'label' => 'Core Charge/Exchange Item Flag',      'entity' => 'product', 'type' => 'boolean',  'options' => [], 'industry_hint' => 'automobile'],
            ['key' => 'warranty_period',       'label' => 'Warranty Period',                     'entity' => 'product', 'type' => 'text',     'options' => [], 'industry_hint' => 'automobile'],
            ['key' => 'technical_specs',       'label' => 'Technical Specifications',            'entity' => 'product', 'type' => 'textarea', 'options' => [], 'industry_hint' => 'automobile'],
        ];
    }

    public static function customerFields(): array
    {
        return [
            ['key' => 'vehicle_reg_number',    'label' => 'Vehicle Registration Number (Plate)', 'entity' => 'customer', 'type' => 'text',   'options' => [], 'industry_hint' => 'automobile'],
            ['key' => 'vin_chassis_number',    'label' => 'VIN/Chassis Number',                  'entity' => 'customer', 'type' => 'text',   'options' => [], 'industry_hint' => 'automobile'],
            ['key' => 'engine_number',         'label' => 'Engine Number',                       'entity' => 'customer', 'type' => 'text',   'options' => [], 'industry_hint' => 'automobile'],
            ['key' => 'last_odometer_reading', 'label' => 'Last Odometer Reading',               'entity' => 'customer', 'type' => 'number', 'options' => [], 'industry_hint' => 'automobile'],
        ];
    }

    /** Returns all valid field keys for an entity type */
    public static function keysFor(string $entityType): array
    {
        $fields = $entityType === 'product' ? self::productFields() : self::customerFields();
        return array_column($fields, 'key');
    }

    /** Returns a single field definition by key, or null */
    public static function find(string $key): ?array
    {
        foreach (self::all() as $field) {
            if ($field['key'] === $key) return $field;
        }
        return null;
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Config/DynamicFields.php
git commit -m "feat: add DynamicFields registry (22 predefined fields)"
```

---

## Task 3: CompanyFieldSetting Model

**Files:**
- Create: `app/Models/CompanyFieldSetting.php`

- [ ] **Step 1: Create model**

```php
<?php
// app/Models/CompanyFieldSetting.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyFieldSetting extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'entity_type',
        'field_key',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public function scopeForCompany($query, string $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Models/CompanyFieldSetting.php
git commit -m "feat: add CompanyFieldSetting model"
```

---

## Task 4: FieldSettingService (TDD)

**Files:**
- Create: `tests/Feature/FieldSettingTest.php`
- Create: `app/Services/FieldSettingService.php`

- [ ] **Step 1: Write failing tests**

```php
<?php
// tests/Feature/FieldSettingTest.php
namespace Tests\Feature;

use App\Models\Party;
use App\Models\Product;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;

class FieldSettingTest extends ApiTestCase
{
    #[Test]
    public function can_list_all_field_settings(): void
    {
        $res = $this->getJson('/api/field-settings', $this->auth());
        $res->assertOk();
        $data = $res->json('data');
        $this->assertCount(22, $data);
        $first = $data[0];
        $this->assertArrayHasKey('fieldKey', $first);
        $this->assertArrayHasKey('entityType', $first);
        $this->assertArrayHasKey('isEnabled', $first);
        $this->assertArrayHasKey('label', $first);
        $this->assertArrayHasKey('type', $first);
        $this->assertArrayHasKey('options', $first);
        $this->assertArrayHasKey('industryHint', $first);
    }

    #[Test]
    public function can_enable_a_field(): void
    {
        $res = $this->putJson('/api/field-settings/brand_name', [
            'entity_type' => 'product',
            'is_enabled'  => true,
        ], $this->auth());

        $res->assertOk();
        $this->assertDatabaseHas('company_field_settings', [
            'company_id'  => $this->company->id,
            'field_key'   => 'brand_name',
            'entity_type' => 'product',
            'is_enabled'  => 1,
        ]);
    }

    #[Test]
    public function can_disable_field_when_no_data_exists(): void
    {
        // Enable first
        $this->putJson('/api/field-settings/brand_name', [
            'entity_type' => 'product',
            'is_enabled'  => true,
        ], $this->auth());

        // No products have brand_name set — safe to disable
        $res = $this->putJson('/api/field-settings/brand_name', [
            'entity_type' => 'product',
            'is_enabled'  => false,
        ], $this->auth());

        $res->assertOk();
        $this->assertDatabaseHas('company_field_settings', [
            'company_id' => $this->company->id,
            'field_key'  => 'brand_name',
            'is_enabled' => 0,
        ]);
    }

    #[Test]
    public function cannot_disable_field_when_data_exists(): void
    {
        // Create a product with brand_name set
        Product::create([
            'id'            => 'PRD-' . Str::random(9),
            'company_id'    => $this->company->id,
            'name'          => 'Test Product',
            'type'          => 'Product',
            'brand_name'    => 'Nike',
            'current_stock' => 0,
            'reorder_level' => 0,
            'unit_cost'     => 0,
            'unit_price'    => 0,
        ]);

        // Enable field
        $this->putJson('/api/field-settings/brand_name', [
            'entity_type' => 'product',
            'is_enabled'  => true,
        ], $this->auth());

        // Try to disable — should be blocked
        $res = $this->putJson('/api/field-settings/brand_name', [
            'entity_type' => 'product',
            'is_enabled'  => false,
        ], $this->auth());

        $res->assertStatus(422);
        $this->assertStringContainsString('Cannot disable', $res->json('error'));
    }

    #[Test]
    public function super_admin_gets_403_on_field_settings(): void
    {
        $super = $this->createSuperAdmin();
        $token = $this->loginAndGetToken($super);

        $this->getJson('/api/field-settings', $this->auth($token))->assertStatus(403);
        $this->putJson('/api/field-settings/brand_name', [
            'entity_type' => 'product',
            'is_enabled'  => true,
        ], $this->auth($token))->assertStatus(403);
    }

    #[Test]
    public function returns_422_for_unknown_field_key(): void
    {
        $res = $this->putJson('/api/field-settings/nonexistent_field', [
            'entity_type' => 'product',
            'is_enabled'  => true,
        ], $this->auth());

        $res->assertStatus(422);
    }

    #[Test]
    public function cannot_disable_customer_field_when_party_has_data(): void
    {
        Party::create([
            'id'              => 'PT-' . Str::random(9),
            'company_id'      => $this->company->id,
            'code'            => 'C-001',
            'type'            => 'Customer',
            'name'            => 'John',
            'vehicle_reg_number' => 'ABC-123',
            'current_balance' => 0,
            'opening_balance' => 0,
        ]);

        $this->putJson('/api/field-settings/vehicle_reg_number', [
            'entity_type' => 'customer',
            'is_enabled'  => true,
        ], $this->auth());

        $res = $this->putJson('/api/field-settings/vehicle_reg_number', [
            'entity_type' => 'customer',
            'is_enabled'  => false,
        ], $this->auth());

        $res->assertStatus(422);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
/c/xampp/php/php artisan test tests/Feature/FieldSettingTest.php
```

Expected: All 7 tests FAIL (routes/controller don't exist yet).

- [ ] **Step 3: Create FieldSettingService**

```php
<?php
// app/Services/FieldSettingService.php
namespace App\Services;

use App\Config\DynamicFields;
use App\Models\CompanyFieldSetting;
use App\Models\Party;
use App\Models\Product;
use Illuminate\Support\Str;

class FieldSettingService
{
    /**
     * Returns all 22 field definitions merged with their enabled state for this company.
     * Fields with no DB row default to is_enabled = false.
     */
    public function getSettings(string $companyId): array
    {
        $rows = CompanyFieldSetting::forCompany($companyId)
            ->get()
            ->keyBy(fn($r) => $r->entity_type . '.' . $r->field_key);

        return array_map(function ($field) use ($rows) {
            $rowKey  = $field['entity'] . '.' . $field['key'];
            $row     = $rows->get($rowKey);
            return array_merge($field, [
                'is_enabled' => $row ? (bool) $row->is_enabled : false,
            ]);
        }, DynamicFields::all());
    }

    /**
     * Enable or disable a field for a company.
     * Throws RuntimeException if trying to disable a field that has data.
     */
    public function updateSetting(string $companyId, string $entityType, string $fieldKey, bool $isEnabled): void
    {
        // Validate field key exists in registry
        $field = DynamicFields::find($fieldKey);
        if (!$field || $field['entity'] !== $entityType) {
            throw new \RuntimeException("Unknown field key: {$fieldKey}");
        }

        if (!$isEnabled) {
            [$canDisable, $count] = $this->canDisable($companyId, $entityType, $fieldKey);
            if (!$canDisable) {
                throw new \RuntimeException("Cannot disable — {$count} record(s) have data in this field");
            }
        }

        // Upsert
        $existing = CompanyFieldSetting::where('company_id', $companyId)
            ->where('entity_type', $entityType)
            ->where('field_key', $fieldKey)
            ->first();

        if ($existing) {
            $existing->update(['is_enabled' => $isEnabled]);
        } else {
            CompanyFieldSetting::create([
                'id'          => 'CFS-' . Str::random(9),
                'company_id'  => $companyId,
                'entity_type' => $entityType,
                'field_key'   => $fieldKey,
                'is_enabled'  => $isEnabled,
            ]);
        }
    }

    /**
     * Check if a field can be safely disabled.
     * Returns [true, 0] if safe, [false, $count] if records have data.
     */
    public function canDisable(string $companyId, string $entityType, string $fieldKey): array
    {
        $count = match ($entityType) {
            'product'  => Product::where('company_id', $companyId)
                ->whereNotNull($fieldKey)
                ->where($fieldKey, '!=', '')
                ->count(),
            'customer' => Party::where('company_id', $companyId)
                ->whereNotNull($fieldKey)
                ->where($fieldKey, '!=', '')
                ->count(),
            default    => 0,
        };

        return $count > 0 ? [false, $count] : [true, 0];
    }
}
```

- [ ] **Step 4: Create FieldSettingResource**

```php
<?php
// app/Http/Resources/FieldSettingResource.php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FieldSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'fieldKey'     => $this->resource['key'],
            'entityType'   => $this->resource['entity'],
            'isEnabled'    => $this->resource['is_enabled'],
            'label'        => $this->resource['label'],
            'type'         => $this->resource['type'],
            'options'      => $this->resource['options'],
            'industryHint' => $this->resource['industry_hint'],
        ];
    }
}
```

- [ ] **Step 5: Create FieldSettingController**

```php
<?php
// app/Http/Controllers/Api/FieldSettingController.php
namespace App\Http\Controllers\Api;

use App\Config\DynamicFields;
use App\Http\Controllers\Controller;
use App\Http\Resources\FieldSettingResource;
use App\Services\FieldSettingService;
use Illuminate\Http\Request;

class FieldSettingController extends Controller
{
    public function __construct(protected FieldSettingService $service) {}

    public function index(Request $request)
    {
        $user = $request->get('auth_user');

        if ($user->company_id === null) {
            return response()->json(['error' => 'Super Admin cannot manage company field settings.'], 403);
        }

        $settings = $this->service->getSettings($user->company_id);

        return response()->json([
            'data' => array_map(fn($s) => (new FieldSettingResource($s))->resolve(), $settings),
        ]);
    }

    public function update(Request $request, string $fieldKey)
    {
        $user = $request->get('auth_user');

        if ($user->company_id === null) {
            return response()->json(['error' => 'Super Admin cannot manage company field settings.'], 403);
        }

        $entityType = $request->input('entity_type');
        $isEnabled  = (bool) $request->input('is_enabled');

        // Validate field key is in registry
        $field = DynamicFields::find($fieldKey);
        if (!$field) {
            return response()->json(['error' => "Unknown field key: {$fieldKey}"], 422);
        }

        try {
            $this->service->updateSetting($user->company_id, $entityType, $fieldKey, $isEnabled);
            return response()->json(['success' => true]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
```

- [ ] **Step 6: Register routes in api.php**

In `routes/api.php`, add these two lines after the existing imports and inside the `ApiTokenAuth` middleware group:

```php
// At top with other use statements:
use App\Http\Controllers\Api\FieldSettingController;

// Inside Route::middleware(ApiTokenAuth::class)->group(function () { ... }):
Route::get('/field-settings', [FieldSettingController::class, 'index']);
Route::put('/field-settings/{fieldKey}', [FieldSettingController::class, 'update']);
```

- [ ] **Step 7: Run tests — expect them to pass**

```bash
/c/xampp/php/php artisan test tests/Feature/FieldSettingTest.php
```

Expected: All 7 tests PASS.

- [ ] **Step 8: Commit**

```bash
git add app/Services/FieldSettingService.php
git add app/Http/Controllers/Api/FieldSettingController.php
git add app/Http/Resources/FieldSettingResource.php
git add tests/Feature/FieldSettingTest.php
git add routes/api.php
git commit -m "feat: add FieldSettingService, controller, resource, routes, and tests"
```

---

## Task 5: Update Product Model, Resource, and Controller

**Files:**
- Modify: `app/Models/Product.php`
- Modify: `app/Http/Resources/ProductResource.php`
- Create: `app/Http/Requests/StoreProductRequest.php`
- Create: `app/Http/Requests/UpdateProductRequest.php`
- Modify: `app/Http/Controllers/Api/ProductController.php`

- [ ] **Step 1: Add dynamic fields to Product $fillable**

In `app/Models/Product.php`, extend the `$fillable` array to include all 18 dynamic field keys:

```php
protected $fillable = [
    'id',
    'company_id',
    'sku',
    'barcode',
    'item_number',
    'name',
    'type',
    'uom',
    'base_uom_id',
    'category_id',
    'current_stock',
    'reorder_level',
    'unit_cost',
    'unit_price',
    // Dynamic fields
    'brand_name',
    'size',
    'color',
    'style',
    'bin_shelf_location',
    'expiry_date',
    'batch_lot_number',
    'storage_condition',
    'drug_composition',
    'schedule_category',
    'manufacturer_name',
    'dosage_form',
    'storage_temp_req',
    'part_number',
    'vehicle_compatibility',
    'core_charge_flag',
    'warranty_period',
    'technical_specs',
];
```

- [ ] **Step 2: Add dynamic fields to ProductResource::toArray()**

In `app/Http/Resources/ProductResource.php`, add all 18 dynamic fields to the return array:

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
        'priceTiers' => ProductPriceTierResource::collection(
            $this->whenLoaded('priceTiers')
        ),
        // Dynamic fields
        'brand_name'           => $this->brand_name           ?? null,
        'size'                 => $this->size                 ?? null,
        'color'                => $this->color                ?? null,
        'style'                => $this->style                ?? null,
        'bin_shelf_location'   => $this->bin_shelf_location   ?? null,
        'expiry_date'          => $this->expiry_date          ?? null,
        'batch_lot_number'     => $this->batch_lot_number     ?? null,
        'storage_condition'    => $this->storage_condition    ?? null,
        'drug_composition'     => $this->drug_composition     ?? null,
        'schedule_category'    => $this->schedule_category    ?? null,
        'manufacturer_name'    => $this->manufacturer_name    ?? null,
        'dosage_form'          => $this->dosage_form          ?? null,
        'storage_temp_req'     => $this->storage_temp_req     ?? null,
        'part_number'          => $this->part_number          ?? null,
        'vehicle_compatibility' => $this->vehicle_compatibility ?? null,
        'core_charge_flag'     => $this->core_charge_flag !== null ? (bool) $this->core_charge_flag : null,
        'warranty_period'      => $this->warranty_period      ?? null,
        'technical_specs'      => $this->technical_specs      ?? null,
    ];
}
```

- [ ] **Step 3: Create StoreProductRequest**

```php
<?php
// app/Http/Requests/StoreProductRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            // Standard fields
            'name'          => 'required|string|max:255',
            'sku'           => 'sometimes|nullable|string|max:100',
            'barcode'       => 'sometimes|nullable|string|max:100',
            'type'          => 'sometimes|string|max:50',
            'uom'           => 'sometimes|nullable|string|max:50',
            'baseUomId'     => 'sometimes|nullable|string|max:20',
            'categoryId'    => 'sometimes|nullable|string|max:20',
            'unitCost'      => 'sometimes|numeric|min:0',
            'unitPrice'     => 'sometimes|numeric|min:0',
            'reorderLevel'  => 'sometimes|integer|min:0',
            'initialStock'  => 'sometimes|numeric|min:0',
            // Dynamic product fields
            'brand_name'           => 'nullable|string|max:255',
            'size'                 => 'nullable|string|max:100',
            'color'                => 'nullable|string|max:100',
            'style'                => 'nullable|string|max:100',
            'bin_shelf_location'   => 'nullable|string|max:255',
            'expiry_date'          => 'nullable|date',
            'batch_lot_number'     => 'nullable|string|max:255',
            'storage_condition'    => 'nullable|string|in:Ambient,Chilled,Frozen',
            'drug_composition'     => 'nullable|string|max:255',
            'schedule_category'    => 'nullable|string|in:H,H1,X,OTC',
            'manufacturer_name'    => 'nullable|string|max:255',
            'dosage_form'          => 'nullable|string|in:Tablet,Syrup,Injection,Capsule',
            'storage_temp_req'     => 'nullable|string|max:255',
            'part_number'          => 'nullable|string|max:255',
            'vehicle_compatibility' => 'nullable|string|max:255',
            'core_charge_flag'     => 'nullable|boolean',
            'warranty_period'      => 'nullable|string|max:255',
            'technical_specs'      => 'nullable|string|max:2000',
        ];
    }
}
```

- [ ] **Step 4: Create UpdateProductRequest**

```php
<?php
// app/Http/Requests/UpdateProductRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            // Standard fields (all sometimes for partial update)
            'name'          => 'sometimes|string|max:255',
            'sku'           => 'sometimes|nullable|string|max:100',
            'barcode'       => 'sometimes|nullable|string|max:100',
            'type'          => 'sometimes|string|max:50',
            'uom'           => 'sometimes|nullable|string|max:50',
            'baseUomId'     => 'sometimes|nullable|string|max:20',
            'categoryId'    => 'sometimes|nullable|string|max:20',
            'unitCost'      => 'sometimes|numeric|min:0',
            'unitPrice'     => 'sometimes|numeric|min:0',
            'reorderLevel'  => 'sometimes|integer|min:0',
            'currentStock'  => 'sometimes|numeric|min:0',
            // Dynamic product fields
            'brand_name'           => 'nullable|string|max:255',
            'size'                 => 'nullable|string|max:100',
            'color'                => 'nullable|string|max:100',
            'style'                => 'nullable|string|max:100',
            'bin_shelf_location'   => 'nullable|string|max:255',
            'expiry_date'          => 'nullable|date',
            'batch_lot_number'     => 'nullable|string|max:255',
            'storage_condition'    => 'nullable|string|in:Ambient,Chilled,Frozen',
            'drug_composition'     => 'nullable|string|max:255',
            'schedule_category'    => 'nullable|string|in:H,H1,X,OTC',
            'manufacturer_name'    => 'nullable|string|max:255',
            'dosage_form'          => 'nullable|string|in:Tablet,Syrup,Injection,Capsule',
            'storage_temp_req'     => 'nullable|string|max:255',
            'part_number'          => 'nullable|string|max:255',
            'vehicle_compatibility' => 'nullable|string|max:255',
            'core_charge_flag'     => 'nullable|boolean',
            'warranty_period'      => 'nullable|string|max:255',
            'technical_specs'      => 'nullable|string|max:2000',
        ];
    }
}
```

- [ ] **Step 5: Update ProductController store() and update() methods**

Replace the `store()` and `update()` method signatures and bodies in `app/Http/Controllers/Api/ProductController.php`:

Add at the top (use statements):
```php
use App\Config\DynamicFields;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
```

Replace `store()`:
```php
public function store(StoreProductRequest $request)
{
    $user     = $request->get('auth_user');
    $validated = $request->validated();

    $productId = $this->sequenceService->getNextNumber($user->company_id, 'item_no');
    $sku = $validated['sku'] ?? '';
    if (empty($sku)) {
        $sku = $this->sequenceService->getNextNumber($user->company_id, 'sku');
    }
    $initialStock = $validated['initialStock'] ?? 0;

    // Collect enabled dynamic field values
    $dynamicFields = [];
    foreach (DynamicFields::productFields() as $field) {
        $key = $field['key'];
        if (array_key_exists($key, $validated)) {
            $val = $validated[$key];
            $dynamicFields[$key] = ($val === '' || $val === null) ? null : $val;
        }
    }

    $product = Product::create(array_merge([
        'id'            => 'PRD-' . Str::random(9),
        'company_id'    => $user->company_id,
        'sku'           => $sku,
        'barcode'       => $validated['barcode'] ?? null,
        'item_number'   => $productId,
        'name'          => $validated['name'],
        'type'          => $validated['type'] ?? 'Product',
        'uom'           => $validated['uom'] ?? '',
        'base_uom_id'   => $validated['baseUomId'] ?? null,
        'category_id'   => $validated['categoryId'] ?? null,
        'current_stock' => $initialStock,
        'reorder_level' => $validated['reorderLevel'] ?? 0,
        'unit_cost'     => $validated['unitCost'] ?? 0,
        'unit_price'    => $validated['unitPrice'] ?? 0,
    ], $dynamicFields));

    if ($initialStock > 0) {
        InventoryLedger::create([
            'id'               => 'LEG-' . Str::random(9),
            'company_id'       => $user->company_id,
            'product_id'       => $product->id,
            'transaction_type' => 'Adjustment_Internal',
            'quantity_change'  => $initialStock,
            'reference_id'     => 'OPENING',
        ]);
    }

    return new ProductResource($product);
}
```

Replace `update()`:
```php
public function update(UpdateProductRequest $request, $id)
{
    $product   = Product::findOrFail($id);
    $validated = $request->validated();

    // Standard field update — only update keys that were sent
    $updateData = [];
    $standardMap = [
        'sku'          => 'sku',
        'barcode'      => 'barcode',
        'name'         => 'name',
        'type'         => 'type',
        'uom'          => 'uom',
        'baseUomId'    => 'base_uom_id',
        'categoryId'   => 'category_id',
        'currentStock' => 'current_stock',
        'reorderLevel' => 'reorder_level',
        'unitCost'     => 'unit_cost',
        'unitPrice'    => 'unit_price',
    ];
    foreach ($standardMap as $inputKey => $dbKey) {
        if (array_key_exists($inputKey, $validated)) {
            $updateData[$dbKey] = $validated[$inputKey];
        }
    }

    // Dynamic fields
    foreach (DynamicFields::productFields() as $field) {
        $key = $field['key'];
        if (array_key_exists($key, $validated)) {
            $val = $validated[$key];
            $updateData[$key] = ($val === '' || $val === null) ? null : $val;
        }
    }

    $product->update($updateData);
    return new ProductResource($product);
}
```

- [ ] **Step 6: Run full test suite to verify no regressions**

```bash
/c/xampp/php/php artisan test
```

Expected: All existing tests pass (FieldSettingTest + AuthTest + UserTest + PartyTest + SaleTest + PurchaseTest + PaymentTest).

- [ ] **Step 7: Commit**

```bash
git add app/Models/Product.php
git add app/Http/Resources/ProductResource.php
git add app/Http/Requests/StoreProductRequest.php
git add app/Http/Requests/UpdateProductRequest.php
git add app/Http/Controllers/Api/ProductController.php
git commit -m "feat: add dynamic fields to Product model, resource, form requests, and controller"
```

---

## Task 6: Update Party Model, Resource, Requests, and Controller

**Files:**
- Modify: `app/Models/Party.php`
- Modify: `app/Http/Resources/PartyResource.php`
- Modify: `app/Http/Requests/StorePartyRequest.php`
- Modify: `app/Http/Requests/UpdatePartyRequest.php`
- Modify: `app/Http/Controllers/Api/PartyController.php`

- [ ] **Step 1: Add dynamic fields to Party $fillable**

In `app/Models/Party.php`, add to the `$fillable` array:

```php
protected $fillable = [
    'id',
    'company_id',
    'code',
    'type',
    'name',
    'phone',
    'email',
    'address',
    'sub_type',
    'payment_terms',
    'credit_limit',
    'bank_details',
    'category',
    'opening_balance',
    'current_balance',
    // Dynamic customer fields
    'vehicle_reg_number',
    'vin_chassis_number',
    'engine_number',
    'last_odometer_reading',
];
```

- [ ] **Step 2: Add dynamic fields to PartyResource::toArray()**

```php
public function toArray(Request $request): array
{
    return [
        'id'             => $this->id,
        'companyId'      => $this->company_id,
        'code'           => $this->code          ?? '',
        'type'           => $this->type,
        'name'           => $this->name,
        'phone'          => $this->phone          ?? '',
        'email'          => $this->email          ?? '',
        'address'        => $this->address        ?? '',
        'subType'        => $this->sub_type       ?? '',
        'paymentTerms'   => $this->payment_terms  ?? '',
        'creditLimit'    => (float) $this->credit_limit,
        'bankDetails'    => $this->bank_details   ?? '',
        'category'       => $this->category       ?? '',
        'openingBalance' => (float) $this->opening_balance,
        'currentBalance' => (float) $this->current_balance,
        // Dynamic customer fields
        'vehicle_reg_number'    => $this->vehicle_reg_number    ?? null,
        'vin_chassis_number'    => $this->vin_chassis_number    ?? null,
        'engine_number'         => $this->engine_number         ?? null,
        'last_odometer_reading' => $this->last_odometer_reading !== null
            ? (float) $this->last_odometer_reading
            : null,
    ];
}
```

- [ ] **Step 3: Add rules to StorePartyRequest**

Add these lines to the `rules()` array in `app/Http/Requests/StorePartyRequest.php`:

```php
// Dynamic customer fields
'vehicle_reg_number'    => 'nullable|string|max:100',
'vin_chassis_number'    => 'nullable|string|max:100',
'engine_number'         => 'nullable|string|max:100',
'last_odometer_reading' => 'nullable|numeric|min:0',
```

- [ ] **Step 4: Add rules to UpdatePartyRequest**

Add same lines to `app/Http/Requests/UpdatePartyRequest.php`:

```php
'vehicle_reg_number'    => 'nullable|string|max:100',
'vin_chassis_number'    => 'nullable|string|max:100',
'engine_number'         => 'nullable|string|max:100',
'last_odometer_reading' => 'nullable|numeric|min:0',
```

- [ ] **Step 5: Update PartyController store() to save dynamic fields**

In `app/Http/Controllers/Api/PartyController.php`, update `Party::create([...])` inside `store()` to include:

```php
// Dynamic customer fields (add to the existing create array)
'vehicle_reg_number'    => $data['vehicle_reg_number']    ?? null,
'vin_chassis_number'    => $data['vin_chassis_number']    ?? null,
'engine_number'         => $data['engine_number']         ?? null,
'last_odometer_reading' => isset($data['last_odometer_reading'])
    ? (float) $data['last_odometer_reading']
    : null,
```

- [ ] **Step 6: Update PartyController update() to save dynamic fields**

In `update()`, add to `$party->update([...])`:

```php
'vehicle_reg_number'    => $request->input('vehicle_reg_number',    $party->vehicle_reg_number),
'vin_chassis_number'    => $request->input('vin_chassis_number',    $party->vin_chassis_number),
'engine_number'         => $request->input('engine_number',         $party->engine_number),
'last_odometer_reading' => $request->has('last_odometer_reading')
    ? ($request->input('last_odometer_reading') !== null
        ? (float) $request->input('last_odometer_reading')
        : null)
    : $party->last_odometer_reading,
```

- [ ] **Step 7: Run full test suite**

```bash
/c/xampp/php/php artisan test
```

Expected: All tests pass.

- [ ] **Step 8: Commit**

```bash
git add app/Models/Party.php
git add app/Http/Resources/PartyResource.php
git add app/Http/Requests/StorePartyRequest.php
git add app/Http/Requests/UpdatePartyRequest.php
git add app/Http/Controllers/Api/PartyController.php
git commit -m "feat: add dynamic fields to Party model, resource, requests, and controller"
```

---

## Task 7: Update SyncService + api.js

**Files:**
- Modify: `app/Services/SyncService.php`
- Modify: `public/js/api.js`

- [ ] **Step 1: Update SyncService::getMasterData()**

In `app/Services/SyncService.php`, add these use statements at the top:

```php
use App\Config\DynamicFields;
use App\Models\CompanyFieldSetting;
```

In `getMasterData()`, add this block before the `return` statement:

```php
// Field settings payload
$fieldSettingsPayload = [
    'enabledKeys' => ['product' => [], 'customer' => []],
    'definitions' => DynamicFields::all(),
];

if (!$isSuper && $coId) {
    CompanyFieldSetting::where('company_id', $coId)
        ->where('is_enabled', true)
        ->get()
        ->each(function ($row) use (&$fieldSettingsPayload) {
            $entityType = $row->entity_type; // 'product' or 'customer'
            if (isset($fieldSettingsPayload['enabledKeys'][$entityType])) {
                $fieldSettingsPayload['enabledKeys'][$entityType][] = $row->field_key;
            }
        });
}
```

Then add `'fieldSettings' => $fieldSettingsPayload,` to the return array:

```php
return [
    'products'           => ProductResource::collection($products),
    'parties'            => PartyResource::collection($parties),
    'categories'         => CategoryResource::collection($categories),
    'uoms'               => UnitOfMeasureResource::collection($uoms),
    'entityTypes'        => EntityTypeResource::collection($entityTypes),
    'businessCategories' => BusinessCategoryResource::collection($businessCategories),
    'fieldSettings'      => $fieldSettingsPayload,
];
```

- [ ] **Step 2: Add API wrappers to api.js**

In `public/js/api.js`, find the end of the `var api = { ... }` object and add before the closing `};`:

```js
getFieldSettings: function() {
    return request('GET', '/field-settings');
},
updateFieldSetting: function(fieldKey, entityType, isEnabled) {
    return request('PUT', '/field-settings/' + fieldKey, {
        entity_type: entityType,
        is_enabled: isEnabled,
    });
},
```

- [ ] **Step 3: Verify sync includes fieldSettings**

Boot the server with XAMPP Apache running and call the sync/master endpoint manually:

```bash
curl -s -H "Authorization: Bearer YOUR_TOKEN" http://localhost/erppos/api/sync/master | python -m json.tool | grep -A 5 "fieldSettings"
```

Expected: Response includes `"fieldSettings"` key with `enabledKeys` and `definitions`.

- [ ] **Step 4: Commit**

```bash
git add app/Services/SyncService.php
git add public/js/api.js
git commit -m "feat: add fieldSettings to getMasterData sync payload and api.js wrappers"
```

---

## Task 8: Settings Page — Dynamic Fields Section

**Files:**
- Modify: `resources/views/pages/settings.blade.php`
- Modify: `public/js/pages/settings.js`

- [ ] **Step 1: Add Dynamic Fields card to settings.blade.php**

Add the following card block in `resources/views/pages/settings.blade.php` right before the closing `</div>` of the `row g-3` div (before the Document Sequences card for visibility, or after it — place it just before the closing `</div></div>` at end of the file before `@endsection`):

```html
{{-- Dynamic Fields --}}
<div class="col-12">
  <div class="card inv-section-card">
    <div class="set-card-header"><i class="ti ti-layout-columns me-2 text-indigo"></i>Dynamic Fields</div>
    <div class="set-card-body">
      <p class="set-desc mb-3">Enable fields to appear in Product and Customer forms. Fields with data cannot be disabled.</p>

      {{-- Product Fields --}}
      <div class="mb-4">
        <div class="fw-bold mb-2" style="font-size:0.82rem;color:#1e293b;">Product Fields</div>
        <div id="dynfields-product"></div>
      </div>

      {{-- Customer Fields --}}
      <div>
        <div class="fw-bold mb-2" style="font-size:0.82rem;color:#1e293b;">Customer Fields</div>
        <div id="dynfields-customer"></div>
      </div>
    </div>
  </div>
</div>
```

Also add the error overlay for disable-blocked errors, inside the existing overlays section at the top of the file (after the existing `stgDeleteError` overlay):

```html
{{-- Dynamic Fields Disable Error Overlay --}}
<div id="dynFieldDisableError" class="d-none ms-overlay">
  <div class="ms-box">
    <div class="ms-body">
      <div class="ms-icon ms-icon-error"><i class="ti ti-alert-triangle"></i></div>
      <div class="ms-title">Cannot Disable Field</div>
      <div class="ms-sub" id="dynFieldDisableErrorMsg"></div>
    </div>
    <div class="ms-footer">
      <button class="ms-btn-ok" onclick="document.getElementById('dynFieldDisableError').classList.add('d-none')">OK</button>
    </div>
  </div>
</div>
```

- [ ] **Step 2: Add Dynamic Fields CSS to settings.blade.php @push('styles') section**

Inside the existing `<style>` block in `@push('styles')`:

```css
/* Dynamic Fields */
.dynfield-row{display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid #F0F2F8;}
.dynfield-row:last-child{border-bottom:none;}
.dynfield-row-left{display:flex;flex-direction:column;gap:2px;}
.dynfield-label{font-size:0.82rem;font-weight:600;color:#1e293b;}
.dynfield-meta{font-size:0.72rem;color:#94a3b8;}
.dynfield-industry{display:inline-block;font-size:0.68rem;font-weight:600;padding:2px 7px;border-radius:10px;background:rgba(59,79,228,0.08);color:#3B4FE4;margin-left:6px;}
.dynfield-type-badge{display:inline-block;font-size:0.68rem;padding:2px 7px;border-radius:10px;background:#F1F5F9;color:#64748b;}
.dynfield-group-header{font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;padding:10px 0 4px;border-bottom:2px solid #E8EAF0;margin-bottom:4px;}
```

- [ ] **Step 3: Add renderDynamicFieldSettings() to settings.js**

In `public/js/pages/settings.js`, add the following function (can be placed at the end of the file):

```js
// ── Dynamic Fields ────────────────────────────────────────────────────────────

function renderDynamicFieldSettings() {
    var fs = (window.ERP.state.fieldSettings) || { enabledKeys: { product: [], customer: [] }, definitions: [] };
    var definitions = fs.definitions || [];
    var enabledProduct  = fs.enabledKeys ? (fs.enabledKeys.product  || []) : [];
    var enabledCustomer = fs.enabledKeys ? (fs.enabledKeys.customer || []) : [];

    ['product', 'customer'].forEach(function(entity) {
        var fields = definitions.filter(function(f) { return f.entity === entity; });
        var enabled = entity === 'product' ? enabledProduct : enabledCustomer;
        var container = document.getElementById('dynfields-' + entity);
        if (!container) return;

        // Group by industry_hint
        var groups = {};
        fields.forEach(function(f) {
            var hint = f.industry_hint || 'general';
            if (!groups[hint]) groups[hint] = [];
            groups[hint].push(f);
        });

        var html = '';
        Object.keys(groups).forEach(function(hint) {
            html += '<div class="dynfield-group-header">' + hint.charAt(0).toUpperCase() + hint.slice(1) + '</div>';
            groups[hint].forEach(function(f) {
                var isOn = enabled.indexOf(f.key) !== -1;
                var toggleId = 'dynfield-toggle-' + f.key;
                html += '<div class="dynfield-row">' +
                    '<div class="dynfield-row-left">' +
                        '<span class="dynfield-label">' + escHtml(f.label) + '</span>' +
                        '<span class="dynfield-meta">' +
                            '<span class="dynfield-type-badge">' + f.type + '</span>' +
                            '<span class="dynfield-industry">' + escHtml(f.industry_hint) + '</span>' +
                        '</span>' +
                    '</div>' +
                    '<div class="form-check form-switch">' +
                        '<input class="form-check-input" type="checkbox" id="' + toggleId + '" ' +
                            'data-field-key="' + f.key + '" data-entity="' + f.entity + '" ' +
                            (isOn ? 'checked' : '') + ' onchange="toggleDynamicField(this)">' +
                    '</div>' +
                '</div>';
            });
        });
        container.innerHTML = html || '<p class="text-muted" style="font-size:0.82rem;">No fields available.</p>';
    });
}

function escHtml(s) {
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

async function toggleDynamicField(checkbox) {
    var fieldKey   = checkbox.dataset.fieldKey;
    var entityType = checkbox.dataset.entity;
    var isEnabled  = checkbox.checked;

    // Optimistically update UI — revert on error
    checkbox.disabled = true;
    try {
        await ERP.api.updateFieldSetting(fieldKey, entityType, isEnabled);
        await ERP.sync();
        renderDynamicFieldSettings();
    } catch(e) {
        checkbox.checked = !isEnabled; // revert toggle
        document.getElementById('dynFieldDisableErrorMsg').textContent = e.message || 'An error occurred.';
        document.getElementById('dynFieldDisableError').classList.remove('d-none');
    } finally {
        checkbox.disabled = false;
    }
}
```

- [ ] **Step 4: Call renderDynamicFieldSettings() from renderPage()**

In `public/js/pages/settings.js`, find the `renderPage` function (or `window.ERP.onReady`) and add a call to `renderDynamicFieldSettings()`:

Find this line (it exists in settings.js):
```js
window.ERP.onReady = function(){ renderPage(); };
```

And find `function renderPage()` — add at the bottom of that function:
```js
renderDynamicFieldSettings();
```

- [ ] **Step 5: Open Settings page in browser, verify Dynamic Fields card renders with all 22 fields grouped by industry hint, toggles work**

Navigate to `http://localhost/erppos/settings` and check the Dynamic Fields section renders correctly.

- [ ] **Step 6: Commit**

```bash
git add resources/views/pages/settings.blade.php
git add public/js/pages/settings.js
git commit -m "feat: add Dynamic Fields toggle section to Settings page"
```

---

## Task 9: Product Modal — Dynamic Fields

**Files:**
- Modify: `resources/views/pages/inventory.blade.php`
- Modify: `public/js/pages/inventory.js`

- [ ] **Step 1: Add dynamic fields container to product modal**

In `resources/views/pages/inventory.blade.php`, find this line:

```html
<div id="pf-uom-section" style="display:none;"></div>
```

Add directly before it:

```html
<div id="pf-dynamic-fields" class="row pm-field-row g-3"></div>
```

- [ ] **Step 2: Add renderProductDynamicFields() to inventory.js**

Add the following functions at the top of `public/js/pages/inventory.js`, after the existing `escHtml` function:

```js
// ── Dynamic Fields Rendering ──────────────────────────────────────────────────

function renderProductDynamicFields(productData) {
    var container = document.getElementById('pf-dynamic-fields');
    if (!container) return;

    var fs = window.ERP.state.fieldSettings || { enabledKeys: { product: [] }, definitions: [] };
    var enabledKeys = (fs.enabledKeys && fs.enabledKeys.product) ? fs.enabledKeys.product : [];
    var definitions = fs.definitions || [];

    var enabledFields = definitions.filter(function(f) {
        return f.entity === 'product' && enabledKeys.indexOf(f.key) !== -1;
    });

    if (enabledFields.length === 0) {
        container.innerHTML = '';
        return;
    }

    var html = '';
    enabledFields.forEach(function(f) {
        var val = (productData && productData[f.key] !== undefined && productData[f.key] !== null)
            ? productData[f.key]
            : '';
        html += '<div class="col-md-6"><label class="pm-label">' + escHtml(f.label) + '</label>';
        html += buildDynamicInput(f, val, 'pf-dyn-');
        html += '</div>';
    });

    container.innerHTML = html;
}

function buildDynamicInput(field, value, idPrefix) {
    var id = idPrefix + field.key;
    var attrs = 'id="' + id + '" data-dynamic-field="' + field.key + '" ';
    switch (field.type) {
        case 'date':
            return '<input type="date" class="form-control pm-input" ' + attrs + 'value="' + escHtml(value) + '">';
        case 'number':
            return '<input type="number" step="0.01" class="form-control pm-input" ' + attrs + 'value="' + escHtml(value) + '">';
        case 'textarea':
            return '<textarea class="form-control pm-input" ' + attrs + 'rows="2" style="height:auto;">' + escHtml(value) + '</textarea>';
        case 'dropdown':
            var opts = '<option value="">— Select —</option>';
            (field.options || []).forEach(function(o) {
                opts += '<option value="' + escHtml(o) + '"' + (value === o ? ' selected' : '') + '>' + escHtml(o) + '</option>';
            });
            return '<select class="form-select pm-input" ' + attrs + '>' + opts + '</select>';
        case 'boolean':
            return '<div class="form-check mt-2"><input class="form-check-input" type="checkbox" ' + attrs +
                (value ? ' checked' : '') + '><label class="form-check-label" for="' + id + '">' + escHtml(field.label) + '</label></div>';
        default: // text
            return '<input type="text" class="form-control pm-input" ' + attrs + 'value="' + escHtml(value) + '">';
    }
}

function collectDynamicFields(idPrefix) {
    var result = {};
    var inputs = document.querySelectorAll('[data-dynamic-field]');
    inputs.forEach(function(input) {
        var key = input.getAttribute('data-dynamic-field');
        if (!key || input.id.indexOf(idPrefix) !== 0) return;
        if (input.type === 'checkbox') {
            result[key] = input.checked;
        } else {
            var v = input.value.trim();
            result[key] = v === '' ? null : v;
        }
    });
    return result;
}
```

- [ ] **Step 3: Call renderProductDynamicFields() when opening product modal**

In `openProductModal()` inside `inventory.js`, find where it sets field values for edit mode:

```js
document.getElementById('pf-id').value = p.id;
document.getElementById('pf-name').value = p.name || '';
// ...etc
```

Add at the end of both edit AND add branches (before `new bootstrap.Modal(...).show()`):

```js
// After the if/else that fills standard fields:
renderProductDynamicFields(mode === 'edit' ? p : null);
```

- [ ] **Step 4: Collect dynamic fields in doSaveProduct()**

In `doSaveProduct()`, find where `var data = { ... }` is built and add after it:

```js
// Collect dynamic field values
var dynFields = collectDynamicFields('pf-dyn-');
Object.keys(dynFields).forEach(function(k) { data[k] = dynFields[k]; });
```

- [ ] **Step 5: Add optional dynamic columns to inventory list table**

In `resources/views/pages/inventory.blade.php`, find the "Columns" area (or the area near the filter bar) and add a Columns dropdown button after the existing filter buttons. Find the filter bar div and add inside it:

```html
<div class="ms-auto">
  <div class="dropdown">
    <button class="btn btn-light btn-sm dropdown-toggle" type="button" id="invColsDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="font-size:0.8rem;border:1px solid #DDE1EC;">
      <i class="ti ti-columns me-1"></i>Columns
    </button>
    <ul class="dropdown-menu dropdown-menu-end" id="invColsMenu" style="min-width:220px;padding:8px 12px;font-size:0.82rem;"></ul>
  </div>
</div>
```

- [ ] **Step 6: Add column visibility logic to inventory.js**

Add these functions to `inventory.js`:

```js
// ── Column Visibility ─────────────────────────────────────────────────────────

var _invVisibleDynCols = null; // null = load from localStorage

function getInvVisibleDynCols() {
    if (_invVisibleDynCols !== null) return _invVisibleDynCols;
    var companyId = (window.ERP.state.currentUser || {}).companyId || 'default';
    var stored = localStorage.getItem('inv_dyn_cols_' + companyId);
    _invVisibleDynCols = stored ? JSON.parse(stored) : {};
    return _invVisibleDynCols;
}

function saveInvVisibleDynCols() {
    var companyId = (window.ERP.state.currentUser || {}).companyId || 'default';
    localStorage.setItem('inv_dyn_cols_' + companyId, JSON.stringify(_invVisibleDynCols));
}

function renderInvColumnsMenu() {
    var menu = document.getElementById('invColsMenu');
    if (!menu) return;
    var fs = window.ERP.state.fieldSettings || { enabledKeys: { product: [] }, definitions: [] };
    var enabledKeys = (fs.enabledKeys && fs.enabledKeys.product) ? fs.enabledKeys.product : [];
    var definitions = fs.definitions || [];
    var enabledFields = definitions.filter(function(f) {
        return f.entity === 'product' && enabledKeys.indexOf(f.key) !== -1;
    });
    var visible = getInvVisibleDynCols();

    if (enabledFields.length === 0) { menu.innerHTML = '<li class="text-muted" style="font-size:0.78rem;padding:4px 0;">No dynamic fields enabled</li>'; return; }

    var html = '';
    enabledFields.forEach(function(f) {
        var checked = visible[f.key] !== false; // default visible
        html += '<li><label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:3px 0;">' +
            '<input type="checkbox" ' + (checked ? 'checked' : '') + ' onchange="toggleInvDynCol(\'' + f.key + '\',this.checked)">' +
            escHtml(f.label) + '</label></li>';
    });
    menu.innerHTML = html;
}

function toggleInvDynCol(key, visible) {
    var cols = getInvVisibleDynCols();
    cols[key] = visible;
    _invVisibleDynCols = cols;
    saveInvVisibleDynCols();
    renderPage();
}
```

- [ ] **Step 7: Render dynamic columns in the products table**

In `renderPage()` in `inventory.js`, find where table rows are built and update to include dynamic columns:

In the `<thead>` rendering (find the existing hardcoded th tags), update the header row building to also add enabled dynamic column headers. Since the thead is in the Blade template (static), we need to dynamically show/hide `<th>` elements. The cleanest approach: add a `<th>` with `id="inv-dyn-th-{key}"` for each possible dynamic field in the blade template (hidden by default), and show them based on column visibility.

Instead, add dynamic column headers via JS by inserting into the table head:

At the start of `renderPage()`, add:

```js
// Sync dynamic column headers
var fs = window.ERP.state.fieldSettings || { enabledKeys: { product: [] }, definitions: [] };
var enabledProdKeys = (fs.enabledKeys && fs.enabledKeys.product) ? fs.enabledKeys.product : [];
var definitions = fs.definitions || [];
var visible = getInvVisibleDynCols();
var visibleDynFields = definitions.filter(function(f) {
    return f.entity === 'product' && enabledProdKeys.indexOf(f.key) !== -1 && visible[f.key] !== false;
});

// Update thead — find the dynamic cols placeholder th
var dynThRow = document.getElementById('inv-dyn-th-row');
if (dynThRow) {
    dynThRow.innerHTML = visibleDynFields.map(function(f) {
        return '<th class="inv-th" onclick="toggleSort(\'' + f.key + '\')" style="cursor:pointer;">' + escHtml(f.label) + '</th>';
    }).join('');
}
renderInvColumnsMenu();
```

In `resources/views/pages/inventory.blade.php`, add a placeholder row in the `<thead>` right before the Actions column `<th>`:

```html
<tr id="inv-dyn-th-row" style="display:contents;"></tr>
```

Wait — `display:contents` on a `tr` won't work. Instead, insert an empty `<th id="inv-dyn-ths">` placeholder and use a different approach. Since the `<thead>` has `<tr>` with `<th>` cells, add a placeholder `<th>` element and replace it with multiple `<th>` elements using `insertAdjacentHTML`.

A cleaner approach: use a `<th>` with a specific id just before the Actions column, and use `outerHTML` replacement. Actually, the simplest approach for this codebase is to build the entire thead row dynamically in JS.

In `inventory.blade.php`, change the thead to have an `id`:

Find:
```html
<thead>
    <tr>
```
Change to:
```html
<thead>
    <tr id="inv-thead-row">
```

Then in `renderPage()` in `inventory.js`, build the header row dynamically:

```js
var theadRow = document.getElementById('inv-thead-row');
if (theadRow) {
    var dynThHtml = visibleDynFields.map(function(f) {
        return '<th class="inv-th" style="cursor:pointer;" onclick="toggleSort(\'' + f.key + '\')">' + escHtml(f.label) + '</th>';
    }).join('');

    // Only rebuild if dynamic cols changed (avoid flicker)
    var existingDynCount = theadRow.querySelectorAll('.inv-dyn-th').length;
    if (existingDynCount !== visibleDynFields.length) {
        // Remove old dyn ths
        theadRow.querySelectorAll('.inv-dyn-th').forEach(function(th) { th.remove(); });
        // Insert before the last Actions th
        var lastTh = theadRow.querySelector('th:last-child');
        visibleDynFields.forEach(function(f) {
            var th = document.createElement('th');
            th.className = 'inv-th inv-dyn-th';
            th.style.cursor = 'pointer';
            th.onclick = (function(key) { return function() { toggleSort(key); }; })(f.key);
            th.textContent = f.label;
            theadRow.insertBefore(th, lastTh);
        });
    }
}
```

And in the `page.forEach` tbody rendering, add dynamic field cells before the Actions `</td>`:

Find `'<button class="inv-action-btn"` and insert before it:

```js
var dynCells = visibleDynFields.map(function(f) {
    var val = p[f.key];
    var display = (val === null || val === undefined || val === '') ? '<span class="text-muted">—</span>' :
        (f.type === 'boolean' ? (val ? '<i class="ti ti-check text-success"></i>' : '<i class="ti ti-x text-danger"></i>') :
        escHtml(String(val)));
    return '<td>' + display + '</td>';
}).join('');
```

Then in the `html +=` string, place `dynCells` right before the Actions `<td>`.

- [ ] **Step 8: Add dynamic field filters to inventory**

In `getFilteredProducts()` in `inventory.js`, after the existing filter logic, add:

```js
// Dynamic field filters
var dynFilters = {};
document.querySelectorAll('[data-dyn-filter]').forEach(function(el) {
    var key = el.getAttribute('data-dyn-filter');
    var val = el.value ? el.value.trim() : '';
    if (val) dynFilters[key] = val.toLowerCase();
});

var list = (state.products || []).filter(function(p) {
    var ms = p.name.toLowerCase().indexOf(search) !== -1 || ...existing...;
    // ... existing filters ...

    // Dynamic field filters
    var md = true;
    Object.keys(dynFilters).forEach(function(key) {
        var pval = p[key];
        if (pval === null || pval === undefined) { md = false; return; }
        if (String(pval).toLowerCase().indexOf(dynFilters[key]) === -1) md = false;
    });

    return ms && mc && mt && ml && md;
});
```

Add dynamic filter inputs in `renderPage()` by updating a `<div id="inv-dyn-filters">` container (add this div to the filter bar in the blade template):

In `inventory.blade.php`, add inside the filter bar div:
```html
<div id="inv-dyn-filters" class="d-flex flex-wrap gap-2 mt-2" style="display:none!important;"></div>
```

In `renderPage()` in `inventory.js`, add:
```js
// Render dynamic filter inputs
var dynFiltersContainer = document.getElementById('inv-dyn-filters');
if (dynFiltersContainer) {
    if (visibleDynFields.length > 0) {
        dynFiltersContainer.style.removeProperty('display');
        var filterHtml = visibleDynFields.map(function(f) {
            var existing = document.querySelector('[data-dyn-filter="' + f.key + '"]');
            var currentVal = existing ? existing.value : '';
            if (f.type === 'dropdown') {
                var opts = '<option value="">All ' + escHtml(f.label) + '</option>';
                (f.options || []).forEach(function(o) {
                    opts += '<option value="' + escHtml(o) + '"' + (currentVal === o ? ' selected' : '') + '>' + escHtml(o) + '</option>';
                });
                return '<select class="form-select inv-input" data-dyn-filter="' + f.key + '" style="min-width:140px;max-width:180px;" onchange="currentPage=1;renderPage();">' + opts + '</select>';
            }
            return '<input type="' + (f.type === 'date' ? 'date' : 'text') + '" class="form-control inv-input" ' +
                'data-dyn-filter="' + f.key + '" placeholder="Filter ' + escHtml(f.label) + '..." ' +
                'value="' + escHtml(currentVal) + '" oninput="currentPage=1;renderPage();" style="min-width:140px;max-width:200px;">';
        }).join('');
        dynFiltersContainer.innerHTML = filterHtml;
    } else {
        dynFiltersContainer.style.display = 'none';
        dynFiltersContainer.innerHTML = '';
    }
}
```

- [ ] **Step 9: Test product modal shows dynamic fields when enabled**

1. Navigate to `http://localhost/erppos/settings`
2. Enable "Brand Name" and "Expiry Date" under Product Fields
3. Navigate to `http://localhost/erppos/inventory`
4. Click "Add Product" — verify Brand Name and Expiry Date inputs appear
5. Fill them in, save — verify data appears in product list
6. Edit the product — verify fields are pre-filled

- [ ] **Step 10: Commit**

```bash
git add resources/views/pages/inventory.blade.php
git add public/js/pages/inventory.js
git commit -m "feat: render dynamic fields in product modal, add column visibility and filters to inventory list"
```

---

## Task 10: Party Modal — Dynamic Fields

**Files:**
- Modify: `resources/views/pages/parties.blade.php`
- Modify: `public/js/pages/parties.js`

- [ ] **Step 1: Add dynamic fields container to party modal**

In `resources/views/pages/parties.blade.php`, find:
```html
<label class="pm-label">Bank Details</label><input type="text" class="form-control pm-input" id="pBank" ...>
```

Add directly after the enclosing `</div>` of that bank details row:

```html
<div id="pty-dynamic-fields" class="row pm-field-row g-3 mt-1"></div>
```

- [ ] **Step 2: Add renderPartyDynamicFields() to parties.js**

Add the following functions in `public/js/pages/parties.js` (the `buildDynamicInput` and `collectDynamicFields` functions are shared logic — add them here as well since this project has no shared utility file):

```js
// ── Dynamic Fields ────────────────────────────────────────────────────────────

function escHtml(s) {
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function buildDynamicInput(field, value, idPrefix) {
    var id = idPrefix + field.key;
    var attrs = 'id="' + id + '" data-dynamic-field="' + field.key + '" ';
    switch (field.type) {
        case 'date':
            return '<input type="date" class="form-control pm-input" ' + attrs + 'value="' + escHtml(value) + '">';
        case 'number':
            return '<input type="number" step="0.01" class="form-control pm-input" ' + attrs + 'value="' + escHtml(value || '') + '">';
        case 'textarea':
            return '<textarea class="form-control pm-input" ' + attrs + 'rows="2" style="height:auto;">' + escHtml(value) + '</textarea>';
        case 'dropdown':
            var opts = '<option value="">— Select —</option>';
            (field.options || []).forEach(function(o) {
                opts += '<option value="' + escHtml(o) + '"' + (value === o ? ' selected' : '') + '>' + escHtml(o) + '</option>';
            });
            return '<select class="form-select pm-input" ' + attrs + '>' + opts + '</select>';
        case 'boolean':
            return '<div class="form-check mt-2"><input class="form-check-input" type="checkbox" ' + attrs +
                (value ? ' checked' : '') + '><label class="form-check-label" for="' + id + '">' + escHtml(field.label) + '</label></div>';
        default:
            return '<input type="text" class="form-control pm-input" ' + attrs + 'value="' + escHtml(value || '') + '">';
    }
}

function collectDynamicFields(idPrefix) {
    var result = {};
    var inputs = document.querySelectorAll('[data-dynamic-field]');
    inputs.forEach(function(input) {
        var key = input.getAttribute('data-dynamic-field');
        if (!key || input.id.indexOf(idPrefix) !== 0) return;
        if (input.type === 'checkbox') {
            result[key] = input.checked;
        } else {
            var v = input.value.trim();
            result[key] = v === '' ? null : v;
        }
    });
    return result;
}

function renderPartyDynamicFields(partyData) {
    var container = document.getElementById('pty-dynamic-fields');
    if (!container) return;

    var fs = window.ERP.state.fieldSettings || { enabledKeys: { customer: [] }, definitions: [] };
    var enabledKeys = (fs.enabledKeys && fs.enabledKeys.customer) ? fs.enabledKeys.customer : [];
    var definitions = fs.definitions || [];

    var enabledFields = definitions.filter(function(f) {
        return f.entity === 'customer' && enabledKeys.indexOf(f.key) !== -1;
    });

    if (enabledFields.length === 0) { container.innerHTML = ''; return; }

    var html = '';
    enabledFields.forEach(function(f) {
        var val = (partyData && partyData[f.key] !== undefined && partyData[f.key] !== null)
            ? partyData[f.key] : '';
        html += '<div class="col-md-6"><label class="pm-label">' + escHtml(f.label) + '</label>';
        html += buildDynamicInput(f, val, 'pty-dyn-');
        html += '</div>';
    });

    container.innerHTML = html;
}
```

- [ ] **Step 3: Call renderPartyDynamicFields() when opening party modal**

In `parties.js`, find the `openAddModal()` and `openEditModal()` functions (or wherever the modal is populated). Find where party fields are set (e.g., `document.getElementById('pName').value = p.name`). Add at the end of both functions, before `new bootstrap.Modal(...).show()`:

```js
renderPartyDynamicFields(/* pass party object for edit, null for add */);
```

For edit: `renderPartyDynamicFields(p);`
For add: `renderPartyDynamicFields(null);`

- [ ] **Step 4: Collect dynamic fields in doSaveParty()**

In `doSaveParty()`, find where `var data = { ... }` is built. After it, add:

```js
var dynFields = collectDynamicFields('pty-dyn-');
Object.keys(dynFields).forEach(function(k) { data[k] = dynFields[k]; });
```

- [ ] **Step 5: Add dynamic columns and filters to parties list**

In `resources/views/pages/parties.blade.php`, add `id="pty-thead-row"` to the `<tr>` in `<thead>`. Also add a Columns dropdown and `<div id="pty-dyn-filters">` similar to inventory (same pattern as Task 9 Step 5–8, but for `'customer'` entity and `'pty-dyn-filter'` data attribute prefix).

In `parties.js`, add the same column visibility and filter logic as inventory.js (same pattern: `getPtyVisibleDynCols`, `savePtyVisibleDynCols`, `renderPtyColumnsMenu`, `togglePtyDynCol`, and integrate into the existing `renderPage()` and filter functions).

The key difference: use `fs.enabledKeys.customer` instead of `fs.enabledKeys.product`, and prefix localStorage key as `pty_dyn_cols_`.

- [ ] **Step 6: Test party modal shows dynamic fields when enabled**

1. Enable "Vehicle Registration Number" under Customer Fields in Settings
2. Navigate to `http://localhost/erppos/parties`
3. Open Add Customer modal — verify "Vehicle Registration Number" field appears
4. Fill it in, save — verify it shows in the list

- [ ] **Step 7: Commit**

```bash
git add resources/views/pages/parties.blade.php
git add public/js/pages/parties.js
git commit -m "feat: render dynamic fields in party modal, add column visibility and filters to parties list"
```

---

## Task 11: Final Test Run & Cleanup

- [ ] **Step 1: Run full test suite**

```bash
/c/xampp/php/php artisan test
```

Expected: All tests pass (FieldSettingTest x7 + all pre-existing tests).

- [ ] **Step 2: Run test DB migration**

```bash
/c/xampp/php/php artisan migrate --database=testing
```

Expected: Migrations run cleanly on `erppos_test`.

- [ ] **Step 3: Verify disable validation works end-to-end**

1. Enable "Brand Name" in Settings
2. Add a product with Brand Name = "Nike"
3. Go back to Settings → try to toggle off "Brand Name"
4. Verify error overlay shows: "Cannot disable — 1 record(s) have data in this field"

- [ ] **Step 4: Final commit**

```bash
git add -A
git commit -m "feat: dynamic fields feature complete — toggleable industry-specific fields on products and parties"
```

---

## Self-Review Notes

**Spec coverage check:**
- ✅ 22 predefined fields (18 product + 4 customer)
- ✅ Settings page toggle UI per company
- ✅ Cannot disable if data exists (FieldSettingService::canDisable)
- ✅ Super Admin blocked from field settings (403)
- ✅ Fields appear inline in Product modal (no separate section)
- ✅ Fields appear inline in Party modal (no separate section)
- ✅ Dynamic columns in inventory list with toggle visibility
- ✅ Dynamic columns in parties list with toggle visibility
- ✅ Filtering on dynamic fields in both lists
- ✅ sync payload includes fieldSettings (enabledKeys + definitions)
- ✅ All field types: text, date, number, dropdown, boolean, textarea
- ✅ Validation rules in StoreProductRequest / UpdateProductRequest / StorePartyRequest / UpdatePartyRequest
- ✅ All field keys in $fillable and Resources

**Type consistency check:**
- `DynamicFields::productFields()` → array of field definitions used in `FieldSettingService`, `ProductController`, `SyncService`
- `DynamicFields::all()` → used in `FieldSettingService::getSettings()` and `SyncService::getMasterData()`
- `DynamicFields::find($key)` → used in `FieldSettingController::update()` and `FieldSettingService::updateSetting()`
- `window.ERP.state.fieldSettings.enabledKeys.product` → array of string keys → used in `renderProductDynamicFields()`, `renderInvColumnsMenu()`, `getFilteredProducts()`
- `window.ERP.state.fieldSettings.definitions` → array of field objects → same consumers
- `collectDynamicFields('pf-dyn-')` → returns `{ brand_name: 'Nike', expiry_date: '2026-01-01', ... }` → merged into save data payload
