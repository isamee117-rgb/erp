<?php

namespace Tests\Feature;

use App\Models\Category;
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
        $this->putJson('/api/field-settings/brand_name', [
            'entity_type' => 'product',
            'is_enabled'  => true,
        ], $this->auth());

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
        $category = Category::create([
            'id'         => 'CAT-' . Str::random(9),
            'company_id' => $this->company->id,
            'name'       => 'Test Category',
        ]);

        Product::create([
            'id'            => 'PRD-' . Str::random(9),
            'company_id'    => $this->company->id,
            'sku'           => 'SKU-' . Str::random(6),
            'name'          => 'Test Product',
            'type'          => 'Product',
            'uom'           => 'pcs',
            'category_id'   => $category->id,
            'brand_name'    => 'Nike',
            'current_stock' => 0,
            'reorder_level' => 0,
            'unit_cost'     => 0,
            'unit_price'    => 0,
        ]);

        $this->putJson('/api/field-settings/brand_name', [
            'entity_type' => 'product',
            'is_enabled'  => true,
        ], $this->auth());

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
            'id'                 => 'PT-' . Str::random(9),
            'company_id'         => $this->company->id,
            'code'               => 'C-001',
            'type'               => 'Customer',
            'name'               => 'John',
            'vehicle_reg_number' => 'ABC-123',
            'current_balance'    => 0,
            'opening_balance'    => 0,
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
