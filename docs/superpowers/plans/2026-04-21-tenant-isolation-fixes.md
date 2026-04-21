# Tenant Isolation Fixes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix all cross-tenant data leaks and unauthorized ownership access across the LeanERP system, and make EntityType + BusinessCategory tenant-specific.

**Architecture:** Three layers of fixes — (1) DB schema migrations to add `company_id` to `settings`, `entity_types`, `business_categories`; (2) Controller ownership guards on update/delete endpoints; (3) SyncService scoping so each tenant only receives their own lookup data.

**Tech Stack:** Laravel 12, PHP 8.2+, MySQL, PHPUnit 11 (`#[Test]`), XAMPP (`/c/xampp/php/php artisan`)

---

## Files Changed / Created

| File | Change |
|------|--------|
| `database/migrations/2026_04_21_000001_add_company_id_to_settings_table.php` | New — adds `company_id` to settings, drops old unique, adds composite unique |
| `database/migrations/2026_04_21_000002_add_company_id_to_entity_types_table.php` | New — adds `company_id` to entity_types |
| `database/migrations/2026_04_21_000003_add_company_id_to_business_categories_table.php` | New — adds `company_id` to business_categories |
| `app/Models/Setting.php` | Add `company_id` fillable + `scopeForCompany` |
| `app/Models/EntityType.php` | Add `company_id` fillable |
| `app/Models/BusinessCategory.php` | Add `company_id` fillable |
| `app/Models/Company.php` | Add `entityTypes()` + `businessCategories()` relationships |
| `app/Http/Controllers/Api/SettingsController.php` | Fix all 8 issues: scope settings by company, remove client companyId, add ownership checks |
| `app/Http/Controllers/Api/RoleController.php` | Add company ownership check on update + destroy |
| `app/Http/Controllers/Api/UserController.php` | Add company ownership check on update + setStatus + updatePassword |
| `app/Http/Controllers/Api/PartyController.php` | Add company ownership check on update + destroy |
| `app/Http/Controllers/Api/PaymentController.php` | Add company ownership check on destroy |
| `app/Http/Controllers/Api/ProductController.php` | Add company ownership check on update + destroy + adjustStock |
| `app/Services/SyncService.php` | Scope settings, entityTypes, businessCategories by company_id |
| `tests/Feature/TenantIsolationTest.php` | New — all cross-tenant ownership tests |

---

## Task 1: Migrations — Add `company_id` to settings, entity_types, business_categories

**Files:**
- Create: `database/migrations/2026_04_21_000001_add_company_id_to_settings_table.php`
- Create: `database/migrations/2026_04_21_000002_add_company_id_to_entity_types_table.php`
- Create: `database/migrations/2026_04_21_000003_add_company_id_to_business_categories_table.php`

- [ ] **Step 1: Create settings migration**

```php
<?php
// database/migrations/2026_04_21_000001_add_company_id_to_settings_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('company_id')->nullable()->after('id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->dropUnique(['key']);
            $table->unique(['company_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'key']);
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
            $table->unique(['key']);
        });
    }
};
```

- [ ] **Step 2: Create entity_types migration**

```php
<?php
// database/migrations/2026_04_21_000002_add_company_id_to_entity_types_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('entity_types', function (Blueprint $table) {
            $table->string('company_id')->nullable()->after('id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('entity_types', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
```

- [ ] **Step 3: Create business_categories migration**

```php
<?php
// database/migrations/2026_04_21_000003_add_company_id_to_business_categories_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('business_categories', function (Blueprint $table) {
            $table->string('company_id')->nullable()->after('id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('business_categories', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
```

- [ ] **Step 4: Run migrations**

```bash
/c/xampp/php/php artisan migrate
```

Expected: `3 migrations` run successfully, no errors.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_04_21_000001_add_company_id_to_settings_table.php
git add database/migrations/2026_04_21_000002_add_company_id_to_entity_types_table.php
git add database/migrations/2026_04_21_000003_add_company_id_to_business_categories_table.php
git commit -m "feat: add company_id to settings, entity_types, business_categories"
```

---

## Task 2: Model Updates

**Files:**
- Modify: `app/Models/Setting.php`
- Modify: `app/Models/EntityType.php`
- Modify: `app/Models/BusinessCategory.php`
- Modify: `app/Models/Company.php`

- [ ] **Step 1: Update Setting model**

Replace full file content:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public $incrementing = true;

    protected $fillable = [
        'company_id',
        'key',
        'value',
    ];

    public function scopeForCompany($query, ?string $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
```

- [ ] **Step 2: Update EntityType model**

Replace full file content:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntityType extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'name',
    ];
}
```

- [ ] **Step 3: Update BusinessCategory model**

Replace full file content:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessCategory extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'name',
    ];
}
```

- [ ] **Step 4: Add relationships to Company model**

Add inside the `Company` class, after the `payments()` method:

```php
    public function entityTypes()
    {
        return $this->hasMany(EntityType::class);
    }

    public function businessCategories()
    {
        return $this->hasMany(BusinessCategory::class);
    }
```

- [ ] **Step 5: Commit**

```bash
git add app/Models/Setting.php app/Models/EntityType.php app/Models/BusinessCategory.php app/Models/Company.php
git commit -m "feat: add company_id fillable and relationships to tenant lookup models"
```

---

## Task 3: Fix SettingsController — All 8 Issues

**Files:**
- Modify: `app/Http/Controllers/Api/SettingsController.php`

- [ ] **Step 1: Replace full SettingsController**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BusinessCategoryResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\EntityTypeResource;
use App\Http\Resources\UnitOfMeasureResource;
use App\Models\Setting;
use App\Models\Category;
use App\Models\UnitOfMeasure;
use App\Models\EntityType;
use App\Models\BusinessCategory;
use App\Models\Company;
use App\Models\Product;
use App\Models\Party;
use App\Services\DocumentSequenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SettingsController extends Controller
{

    public function updateCurrency(Request $request)
    {
        $user = $request->get('auth_user');
        Setting::updateOrCreate(
            ['company_id' => $user->company_id, 'key' => 'currency'],
            ['value' => $request->input('currency')]
        );
        return response()->json(['success' => true]);
    }

    public function updateInvoiceFormat(Request $request)
    {
        $user = $request->get('auth_user');
        Setting::updateOrCreate(
            ['company_id' => $user->company_id, 'key' => 'invoice_format'],
            ['value' => $request->input('format') ?? $request->input('invoiceFormat')]
        );
        return response()->json(['success' => true]);
    }

    public function updateJobCardMode(Request $request)
    {
        $user = $request->get('auth_user');
        $mode = $request->input('jobCardMode') ? '1' : '0';
        Setting::updateOrCreate(
            ['company_id' => $user->company_id, 'key' => 'job_card_mode'],
            ['value' => $mode]
        );
        return response()->json(['success' => true, 'jobCardMode' => (bool) $mode]);
    }

    public function updateCostingMethod(Request $request)
    {
        $user = $request->get('auth_user');
        $method = $request->input('costingMethod') ?? $request->input('costing_method') ?? 'moving_average';

        if (!in_array($method, ['fifo', 'moving_average'])) {
            return response()->json(['error' => 'Invalid costing method'], 400);
        }

        $company = Company::findOrFail($user->company_id);
        $company->update(['costing_method' => $method]);

        return response()->json(['success' => true, 'costingMethod' => $method]);
    }

    public function createCategory(Request $request)
    {
        $user = $request->get('auth_user');
        $category = Category::create([
            'id'         => 'CAT-' . Str::random(9),
            'company_id' => $user->company_id,
            'name'       => $request->input('name'),
        ]);
        return new CategoryResource($category);
    }

    public function deleteCategory(Request $request, $id)
    {
        $user     = $request->get('auth_user');
        $category = Category::where('id', $id)
            ->where('company_id', $user->company_id)
            ->firstOrFail();

        if (Product::where('category_id', $id)->exists()) {
            return response()->json(['error' => 'Cannot delete: this category is used by one or more products.'], 422);
        }
        $category->delete();
        return response()->json(['success' => true]);
    }

    public function createUOM(Request $request)
    {
        $user = $request->get('auth_user');
        $uom = UnitOfMeasure::create([
            'id'         => 'UOM-' . Str::random(9),
            'company_id' => $user->company_id,
            'name'       => $request->input('name'),
        ]);
        return new UnitOfMeasureResource($uom);
    }

    public function deleteUOM(Request $request, $id)
    {
        $user = $request->get('auth_user');
        $uom  = UnitOfMeasure::where('id', $id)
            ->where('company_id', $user->company_id)
            ->firstOrFail();

        if (Product::where('uom', $uom->name)->exists()) {
            return response()->json(['error' => 'Cannot delete: this unit of measure is used by one or more products.'], 422);
        }
        $uom->delete();
        return response()->json(['success' => true]);
    }

    public function createEntityType(Request $request)
    {
        $user = $request->get('auth_user');
        $et   = EntityType::create([
            'id'         => 'ET-' . Str::random(9),
            'company_id' => $user->company_id,
            'name'       => $request->input('name'),
        ]);
        return new EntityTypeResource($et);
    }

    public function deleteEntityType(Request $request, $id)
    {
        $user = $request->get('auth_user');
        $et   = EntityType::where('id', $id)
            ->where('company_id', $user->company_id)
            ->firstOrFail();

        if (Party::where('sub_type', $et->name)->exists()) {
            return response()->json(['error' => 'Cannot delete: this entity type is used by one or more parties.'], 422);
        }
        $et->delete();
        return response()->json(['success' => true]);
    }

    public function createBusinessCategory(Request $request)
    {
        $user = $request->get('auth_user');
        $bc   = BusinessCategory::create([
            'id'         => 'BC-' . Str::random(9),
            'company_id' => $user->company_id,
            'name'       => $request->input('name'),
        ]);
        return new BusinessCategoryResource($bc);
    }

    public function deleteBusinessCategory(Request $request, $id)
    {
        $user = $request->get('auth_user');
        $bc   = BusinessCategory::where('id', $id)
            ->where('company_id', $user->company_id)
            ->firstOrFail();

        if (Party::where('category', $bc->name)->exists()) {
            return response()->json(['error' => 'Cannot delete: this business category is used by one or more parties.'], 422);
        }
        $bc->delete();
        return response()->json(['success' => true]);
    }

    public function getDocumentSequences(Request $request)
    {
        $user    = $request->get('auth_user');
        $service = new DocumentSequenceService();
        $sequences = $service->getSequences($user->company_id);

        return response()->json(array_map(function ($seq) {
            return [
                'id'         => $seq['id'],
                'companyId'  => $seq['company_id'],
                'type'       => $seq['type'],
                'prefix'     => $seq['prefix'],
                'nextNumber' => $seq['next_number'],
                'isLocked'   => (bool) $seq['is_locked'],
            ];
        }, $sequences));
    }

    public function updateDocumentSequence(Request $request)
    {
        $user       = $request->get('auth_user');
        $type       = $request->input('type');
        $prefix     = $request->input('prefix', '');
        $nextNumber = (int) $request->input('nextNumber', 1);

        $service = new DocumentSequenceService();

        try {
            $seq = $service->updateSequence($user->company_id, $type, $prefix, $nextNumber);
            return response()->json([
                'id'         => $seq->id,
                'companyId'  => $seq->company_id,
                'type'       => $seq->type,
                'prefix'     => $seq->prefix,
                'nextNumber' => $seq->next_number,
                'isLocked'   => (bool) $seq->is_locked,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Http/Controllers/Api/SettingsController.php
git commit -m "fix: scope settings by company_id, remove client-supplied companyId, add ownership checks"
```

---

## Task 4: Fix RoleController — Ownership Checks

**Files:**
- Modify: `app/Http/Controllers/Api/RoleController.php`

- [ ] **Step 1: Replace full RoleController**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomRoleResource;
use App\Models\CustomRole;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->get('auth_user');
        $data = $request->all();

        $role = CustomRole::create([
            'id'          => 'ROLE-' . Str::random(9),
            'company_id'  => $user->company_id,
            'name'        => $data['name']        ?? '',
            'description' => $data['description'] ?? '',
            'permissions' => $data['permissions'] ?? [],
        ]);

        return new CustomRoleResource($role);
    }

    public function update(Request $request, $id)
    {
        $user = $request->get('auth_user');
        $role = CustomRole::where('id', $id)
            ->where('company_id', $user->company_id)
            ->firstOrFail();

        $data = $request->all();
        $role->update([
            'name'        => $data['name']        ?? $role->name,
            'description' => $data['description'] ?? $role->description,
            'permissions' => $data['permissions'] ?? $role->permissions,
        ]);

        return new CustomRoleResource($role);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->get('auth_user');
        CustomRole::where('id', $id)
            ->where('company_id', $user->company_id)
            ->firstOrFail()
            ->delete();

        return response()->json(['success' => true]);
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Http/Controllers/Api/RoleController.php
git commit -m "fix: add company ownership check to role update and destroy"
```

---

## Task 5: Fix UserController — Ownership Checks

**Files:**
- Modify: `app/Http/Controllers/Api/UserController.php`

- [ ] **Step 1: Replace full UserController**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserController extends Controller
{

    public function store(StoreUserRequest $request)
    {
        $authUser = $request->get('auth_user');
        $data     = $request->validated();

        User::create([
            'id'          => 'user-' . Str::random(9),
            'username'    => $data['username'],
            'name'        => $data['name'] ?? '',
            'password'    => $data['password'],
            'system_role' => 'Standard User',
            'role_id'     => $data['roleId'] ?? null,
            'company_id'  => $authUser->company_id,
            'is_active'   => $data['isActive'] ?? true,
        ]);

        return response()->json(['success' => true]);
    }

    public function update(UpdateUserRequest $request, $id)
    {
        $authUser = $request->get('auth_user');
        $user     = User::where('id', $id)
            ->where('company_id', $authUser->company_id)
            ->firstOrFail();

        $data    = $request->validated();
        $updates = [];

        if (isset($data['name']))     $updates['name']     = $data['name'];
        if (isset($data['username'])) $updates['username'] = $data['username'];
        if (array_key_exists('roleId', $data)) {
            $updates['role_id'] = $data['roleId'] ?: null;
        }

        if (!empty($updates)) $user->update($updates);

        return response()->json(['success' => true]);
    }

    public function setStatus(Request $request, $id)
    {
        $authUser = $request->get('auth_user');
        $user     = User::where('id', $id)
            ->where('company_id', $authUser->company_id)
            ->firstOrFail();

        $user->update(['is_active' => $request->input('isActive') ?? $request->input('is_active')]);
        return response()->json(['success' => true]);
    }

    public function updatePassword(UpdatePasswordRequest $request, $id)
    {
        $authUser = $request->get('auth_user');
        $user     = User::where('id', $id)
            ->where('company_id', $authUser->company_id)
            ->firstOrFail();

        $user->update(['password' => $request->validated()['password']]);
        return response()->json(['success' => true]);
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Http/Controllers/Api/UserController.php
git commit -m "fix: add company ownership check to user update, setStatus, updatePassword"
```

---

## Task 6: Fix PartyController — Ownership Checks

**Files:**
- Modify: `app/Http/Controllers/Api/PartyController.php`

- [ ] **Step 1: Fix update and destroy methods**

In `update()`, replace:
```php
$party = Party::findOrFail($id);
```
With:
```php
$user  = $request->get('auth_user');
$party = Party::where('id', $id)
    ->where('company_id', $user->company_id)
    ->firstOrFail();
```

In `destroy()`, the method signature is `public function destroy($id)`. Change to `public function destroy(Request $request, $id)` and replace:
```php
$party = Party::findOrFail($id);
```
With:
```php
$user  = $request->get('auth_user');
$party = Party::where('id', $id)
    ->where('company_id', $user->company_id)
    ->firstOrFail();
```

- [ ] **Step 2: Commit**

```bash
git add app/Http/Controllers/Api/PartyController.php
git commit -m "fix: add company ownership check to party update and destroy"
```

---

## Task 7: Fix PaymentController — Ownership Check on destroy

**Files:**
- Modify: `app/Http/Controllers/Api/PaymentController.php`

- [ ] **Step 1: Fix destroy method**

Change signature from `public function destroy($id)` to `public function destroy(Request $request, $id)` and replace:
```php
$payment = Payment::findOrFail($id);
```
With:
```php
$user    = $request->get('auth_user');
$payment = Payment::where('id', $id)
    ->where('company_id', $user->company_id)
    ->firstOrFail();
```

- [ ] **Step 2: Commit**

```bash
git add app/Http/Controllers/Api/PaymentController.php
git commit -m "fix: add company ownership check to payment destroy"
```

---

## Task 8: Fix ProductController — Ownership Checks on update, destroy, adjustStock

**Files:**
- Modify: `app/Http/Controllers/Api/ProductController.php`

- [ ] **Step 1: Fix update method**

`update(UpdateProductRequest $request, $id)` — replace:
```php
$product = Product::findOrFail($id);
```
With:
```php
$user    = $request->get('auth_user');
$product = Product::where('id', $id)
    ->where('company_id', $user->company_id)
    ->firstOrFail();
```

- [ ] **Step 2: Fix destroy method**

Change `public function destroy($id)` to `public function destroy(Request $request, $id)` and replace:
```php
$product = Product::findOrFail($id);
```
With:
```php
$user    = $request->get('auth_user');
$product = Product::where('id', $id)
    ->where('company_id', $user->company_id)
    ->firstOrFail();
```

- [ ] **Step 3: Fix adjustStock method**

Replace:
```php
$product = Product::findOrFail($productId);
```
With:
```php
$product = Product::where('id', $productId)
    ->where('company_id', $user->company_id)
    ->firstOrFail();
```

(Note: `$user` is already set at the top of `adjustStock`: `$user = $request->get('auth_user');`)

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/Api/ProductController.php
git commit -m "fix: add company ownership check to product update, destroy, adjustStock"
```

---

## Task 9: Fix SyncService — Scope Settings + EntityTypes + BusinessCategories

**Files:**
- Modify: `app/Services/SyncService.php`

- [ ] **Step 1: Fix getCoreData — scope settings by company_id**

In `getCoreData()`, replace:
```php
$currencySetting      = Setting::where('key', 'currency')->first();
$invoiceFormatSetting = Setting::where('key', 'invoice_format')->first();
$jobCardModeSetting   = Setting::where('key', 'job_card_mode')->first();
```
With:
```php
$currencySetting      = Setting::where('company_id', $coId)->where('key', 'currency')->first();
$invoiceFormatSetting = Setting::where('company_id', $coId)->where('key', 'invoice_format')->first();
$jobCardModeSetting   = Setting::where('company_id', $coId)->where('key', 'job_card_mode')->first();
```

- [ ] **Step 2: Fix getMasterData — scope entityTypes + businessCategories**

In `getMasterData()`, replace:
```php
$entityTypes        = EntityType::all();
$businessCategories = BusinessCategory::all();
```
With:
```php
$entityTypes        = $this->scopedQuery(EntityType::query(), $isSuper, $coId);
$businessCategories = $this->scopedQuery(BusinessCategory::query(), $isSuper, $coId);
```

- [ ] **Step 3: Commit**

```bash
git add app/Services/SyncService.php
git commit -m "fix: scope settings, entityTypes, businessCategories by company_id in SyncService"
```

---

## Task 10: Tests — Cross-Tenant Ownership Verification

**Files:**
- Create: `tests/Feature/TenantIsolationTest.php`

- [ ] **Step 1: Create test file**

```php
<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CustomRole;
use App\Models\EntityType;
use App\Models\BusinessCategory;
use App\Models\Party;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Setting;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;

class TenantIsolationTest extends ApiTestCase
{
    private \App\Models\Company $otherCompany;
    private string $otherToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->otherCompany = $this->createCompany(['name' => 'Other Company', 'id' => Str::uuid()]);
        $otherUser = $this->createAdminUser($this->otherCompany, [
            'username' => 'otheradmin',
            'id'       => Str::uuid(),
        ]);
        $this->otherToken = $this->loginAndGetToken($otherUser);
    }

    // ── Settings ──────────────────────────────────────────────────────────────

    #[Test]
    public function settings_are_scoped_per_company(): void
    {
        $this->putJson('/api/settings/currency', ['currency' => 'USD'], $this->auth());
        $this->putJson('/api/settings/currency', ['currency' => 'EUR'], $this->auth($this->otherToken));

        $this->assertDatabaseHas('settings', ['company_id' => $this->company->id,      'key' => 'currency', 'value' => 'USD']);
        $this->assertDatabaseHas('settings', ['company_id' => $this->otherCompany->id, 'key' => 'currency', 'value' => 'EUR']);
    }

    // ── Category ──────────────────────────────────────────────────────────────

    #[Test]
    public function cannot_delete_another_tenants_category(): void
    {
        $category = Category::create([
            'id'         => 'CAT-' . Str::random(9),
            'company_id' => $this->otherCompany->id,
            'name'       => 'Other Cat',
        ]);

        $response = $this->deleteJson('/api/categories/' . $category->id, [], $this->auth());

        $response->assertStatus(404);
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    // ── UOM ───────────────────────────────────────────────────────────────────

    #[Test]
    public function cannot_delete_another_tenants_uom(): void
    {
        $uom = UnitOfMeasure::create([
            'id'         => 'UOM-' . Str::random(9),
            'company_id' => $this->otherCompany->id,
            'name'       => 'KG',
        ]);

        $response = $this->deleteJson('/api/uoms/' . $uom->id, [], $this->auth());

        $response->assertStatus(404);
        $this->assertDatabaseHas('units_of_measure', ['id' => $uom->id]);
    }

    // ── EntityType ────────────────────────────────────────────────────────────

    #[Test]
    public function entity_types_are_created_with_company_id(): void
    {
        $response = $this->postJson('/api/entity-types', ['name' => 'Individual'], $this->auth());

        $response->assertStatus(201);
        $this->assertDatabaseHas('entity_types', [
            'company_id' => $this->company->id,
            'name'       => 'Individual',
        ]);
    }

    #[Test]
    public function cannot_delete_another_tenants_entity_type(): void
    {
        $et = EntityType::create([
            'id'         => 'ET-' . Str::random(9),
            'company_id' => $this->otherCompany->id,
            'name'       => 'Corporate',
        ]);

        $response = $this->deleteJson('/api/entity-types/' . $et->id, [], $this->auth());

        $response->assertStatus(404);
        $this->assertDatabaseHas('entity_types', ['id' => $et->id]);
    }

    // ── BusinessCategory ──────────────────────────────────────────────────────

    #[Test]
    public function business_categories_are_created_with_company_id(): void
    {
        $response = $this->postJson('/api/business-categories', ['name' => 'Retail'], $this->auth());

        $response->assertStatus(201);
        $this->assertDatabaseHas('business_categories', [
            'company_id' => $this->company->id,
            'name'       => 'Retail',
        ]);
    }

    #[Test]
    public function cannot_delete_another_tenants_business_category(): void
    {
        $bc = BusinessCategory::create([
            'id'         => 'BC-' . Str::random(9),
            'company_id' => $this->otherCompany->id,
            'name'       => 'Wholesale',
        ]);

        $response = $this->deleteJson('/api/business-categories/' . $bc->id, [], $this->auth());

        $response->assertStatus(404);
        $this->assertDatabaseHas('business_categories', ['id' => $bc->id]);
    }

    // ── Role ──────────────────────────────────────────────────────────────────

    #[Test]
    public function cannot_update_another_tenants_role(): void
    {
        $role = CustomRole::create([
            'id'          => 'ROLE-' . Str::random(9),
            'company_id'  => $this->otherCompany->id,
            'name'        => 'Other Role',
            'permissions' => [],
        ]);

        $response = $this->putJson('/api/roles/' . $role->id, ['name' => 'Hacked'], $this->auth());

        $response->assertStatus(404);
        $this->assertDatabaseHas('custom_roles', ['id' => $role->id, 'name' => 'Other Role']);
    }

    #[Test]
    public function cannot_delete_another_tenants_role(): void
    {
        $role = CustomRole::create([
            'id'          => 'ROLE-' . Str::random(9),
            'company_id'  => $this->otherCompany->id,
            'name'        => 'Delete Target',
            'permissions' => [],
        ]);

        $response = $this->deleteJson('/api/roles/' . $role->id, [], $this->auth());

        $response->assertStatus(404);
        $this->assertDatabaseHas('custom_roles', ['id' => $role->id]);
    }

    // ── User ──────────────────────────────────────────────────────────────────

    #[Test]
    public function cannot_update_another_tenants_user(): void
    {
        $otherUser = User::create([
            'id'          => 'user-' . Str::random(9),
            'username'    => 'victim_user',
            'name'        => 'Victim',
            'password'    => 'secret123',
            'system_role' => 'Standard User',
            'company_id'  => $this->otherCompany->id,
            'is_active'   => true,
        ]);

        $response = $this->putJson('/api/users/' . $otherUser->id, ['name' => 'Hacked'], $this->auth());

        $response->assertStatus(404);
        $this->assertDatabaseHas('users', ['id' => $otherUser->id, 'name' => 'Victim']);
    }

    #[Test]
    public function cannot_change_another_tenants_user_password(): void
    {
        $otherUser = User::create([
            'id'          => 'user-' . Str::random(9),
            'username'    => 'victim_pwd',
            'name'        => 'Victim Pwd',
            'password'    => 'original_password',
            'system_role' => 'Standard User',
            'company_id'  => $this->otherCompany->id,
            'is_active'   => true,
        ]);

        $response = $this->putJson('/api/users/' . $otherUser->id . '/password',
            ['password' => 'newpassword123', 'password_confirmation' => 'newpassword123'],
            $this->auth()
        );

        $response->assertStatus(404);
    }

    // ── Party ─────────────────────────────────────────────────────────────────

    #[Test]
    public function cannot_update_another_tenants_party(): void
    {
        $party = Party::create([
            'id'         => 'PT-' . Str::random(9),
            'company_id' => $this->otherCompany->id,
            'name'       => 'Other Party',
            'type'       => 'Customer',
        ]);

        $response = $this->putJson('/api/parties/' . $party->id,
            ['name' => 'Hacked', 'type' => 'Customer'],
            $this->auth()
        );

        $response->assertStatus(404);
        $this->assertDatabaseHas('parties', ['id' => $party->id, 'name' => 'Other Party']);
    }

    #[Test]
    public function cannot_delete_another_tenants_party(): void
    {
        $party = Party::create([
            'id'         => 'PT-' . Str::random(9),
            'company_id' => $this->otherCompany->id,
            'name'       => 'Delete Target',
            'type'       => 'Customer',
        ]);

        $response = $this->deleteJson('/api/parties/' . $party->id, [], $this->auth());

        $response->assertStatus(404);
        $this->assertDatabaseHas('parties', ['id' => $party->id]);
    }

    // ── Payment ───────────────────────────────────────────────────────────────

    #[Test]
    public function cannot_delete_another_tenants_payment(): void
    {
        $party = Party::create([
            'id'         => 'PT-' . Str::random(9),
            'company_id' => $this->otherCompany->id,
            'name'       => 'Other Vendor',
            'type'       => 'Vendor',
        ]);
        $payment = Payment::create([
            'id'             => 'PAY-' . Str::random(9),
            'company_id'     => $this->otherCompany->id,
            'party_id'       => $party->id,
            'amount'         => 1000,
            'payment_method' => 'Cash',
            'type'           => 'Payment',
            'date'           => now()->getTimestampMs(),
        ]);

        $response = $this->deleteJson('/api/payments/' . $payment->id, [], $this->auth());

        $response->assertStatus(404);
        $this->assertDatabaseHas('payments', ['id' => $payment->id]);
    }

    // ── Product ───────────────────────────────────────────────────────────────

    #[Test]
    public function cannot_update_another_tenants_product(): void
    {
        $product = Product::create([
            'id'          => 'PRD-' . Str::random(9),
            'company_id'  => $this->otherCompany->id,
            'name'        => 'Other Product',
            'sku'         => 'SKU-OTHER-1',
            'item_number' => 'ITM-00001',
            'uom'         => 'Pcs',
            'unit_price'  => 100,
            'unit_cost'   => 60,
        ]);

        $response = $this->putJson('/api/products/' . $product->id,
            ['name' => 'Hacked Product', 'sku' => 'SKU-OTHER-1', 'uom' => 'Pcs', 'unitPrice' => 100, 'unitCost' => 60],
            $this->auth()
        );

        $response->assertStatus(404);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Other Product']);
    }

    #[Test]
    public function cannot_delete_another_tenants_product(): void
    {
        $product = Product::create([
            'id'          => 'PRD-' . Str::random(9),
            'company_id'  => $this->otherCompany->id,
            'name'        => 'Delete Target',
            'sku'         => 'SKU-OTHER-2',
            'item_number' => 'ITM-00002',
            'uom'         => 'Pcs',
            'unit_price'  => 100,
            'unit_cost'   => 60,
        ]);

        $response = $this->deleteJson('/api/products/' . $product->id, [], $this->auth());

        $response->assertStatus(404);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }
}
```

- [ ] **Step 2: Run tests to verify they all fail (before fixes applied, if running incrementally)**

```bash
/c/xampp/php/php artisan test tests/Feature/TenantIsolationTest.php --verbose
```

Expected after all fixes: all tests PASS.

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/TenantIsolationTest.php
git commit -m "test: add cross-tenant ownership isolation tests"
```

---

## Task 11: Final Verification

- [ ] **Step 1: Run full test suite**

```bash
/c/xampp/php/php artisan test --verbose
```

Expected: All tests pass, zero failures.

- [ ] **Step 2: Verify migrations are clean**

```bash
/c/xampp/php/php artisan migrate:status
```

Expected: All migrations show `Ran` status.

---

## Self-Review

### Spec Coverage
| Issue | Task |
|-------|------|
| Settings global (currency etc.) | Task 3 (SettingsController) + Task 9 (SyncService) + Task 1 (migration) |
| Client-supplied companyId in category/UOM | Task 3 |
| Category/UOM delete without ownership | Task 3 |
| Role update/delete without ownership | Task 4 |
| User update/password without ownership | Task 5 |
| Party update/delete without ownership | Task 6 |
| Payment delete without ownership | Task 7 |
| Product update/delete without ownership | Task 8 |
| EntityType tenant-specific | Task 1 (migration) + Task 2 (model) + Task 3 (controller) + Task 9 (sync) |
| BusinessCategory tenant-specific | Task 1 (migration) + Task 2 (model) + Task 3 (controller) + Task 9 (sync) |

All issues covered. ✅
