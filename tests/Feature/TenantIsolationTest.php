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
            'description' => '',
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
            'description' => '',
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
            'code'       => 'CUST-OTHER-1',
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
            'code'       => 'CUST-OTHER-2',
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
            'code'       => 'VEND-OTHER-1',
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
            'type'        => 'Standard',
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
            'type'        => 'Standard',
            'uom'         => 'Pcs',
            'unit_price'  => 100,
            'unit_cost'   => 60,
        ]);

        $response = $this->deleteJson('/api/products/' . $product->id, [], $this->auth());

        $response->assertStatus(404);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }
}
