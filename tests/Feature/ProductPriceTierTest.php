<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\ApiTestCase;

class ProductPriceTierTest extends ApiTestCase
{
    use RefreshDatabase;

    #[Test]
    public function product_price_tiers_table_exists(): void
    {
        $this->assertTrue(
            Schema::hasTable('product_price_tiers'),
            'product_price_tiers table should exist'
        );
    }

    #[Test]
    public function product_has_many_price_tiers(): void
    {
        $category = \App\Models\Category::create([
            'id'        => 'CAT-test00001',
            'company_id' => $this->company->id,
            'name'      => 'Test Category',
        ]);

        $product = \App\Models\Product::create([
            'id'           => 'PRD-test00001',
            'company_id'   => $this->company->id,
            'sku'          => 'TEST-SKU-001',
            'name'         => 'Widget',
            'type'         => 'Product',
            'uom'          => 'pcs',
            'category_id'  => $category->id,
            'unit_cost'    => 10,
            'unit_price'   => 20,
            'current_stock' => 0,
        ]);

        \App\Models\ProductPriceTier::create([
            'id'         => 'PPT-test00001',
            'product_id' => $product->id,
            'company_id' => $this->company->id,
            'category'   => 'Wholesale',
            'price'      => 15,
        ]);

        $product->refresh();
        $this->assertCount(1, $product->priceTiers);
        $this->assertEquals('Wholesale', $product->priceTiers->first()->category);
    }

    private function makeProduct(): \App\Models\Product
    {
        app(\App\Services\DocumentSequenceService::class)->ensureSequencesExist($this->company->id);

        $category = \App\Models\Category::create([
            'id'         => 'CAT-pt-' . uniqid(),
            'company_id' => $this->company->id,
            'name'       => 'TestCategory-' . uniqid(),
        ]);

        $res = $this->postJson('/api/products', [
            'name'         => 'TestProduct-' . uniqid(),
            'type'         => 'Product',
            'uom'          => 'pcs',
            'categoryId'   => $category->id,
            'unitCost'     => 10,
            'unitPrice'    => 20,
            'initialStock' => 0,
        ], $this->auth());
        $this->assertNotNull($res->json('id'), 'Product creation failed: ' . $res->getContent());
        return \App\Models\Product::find($res->json('id'));
    }

    #[Test]
    public function price_tier_can_be_created(): void
    {
        $product = $this->makeProduct();

        $res = $this->postJson("/api/products/{$product->id}/price-tiers", [
            'category' => 'Wholesale', 'price' => 15,
        ], $this->auth());

        $res->assertStatus(201)
            ->assertJsonPath('category', 'Wholesale')
            ->assertJsonPath('price', 15);

        $this->assertDatabaseHas('product_price_tiers', [
            'product_id' => $product->id, 'category' => 'Wholesale', 'price' => 15,
        ]);
    }

    #[Test]
    public function duplicate_category_tier_is_rejected(): void
    {
        $product = $this->makeProduct();

        $this->postJson("/api/products/{$product->id}/price-tiers", [
            'category' => 'Wholesale', 'price' => 15,
        ], $this->auth());

        $res = $this->postJson("/api/products/{$product->id}/price-tiers", [
            'category' => 'Wholesale', 'price' => 12,
        ], $this->auth());

        $res->assertStatus(422);
    }

    #[Test]
    public function price_tier_can_be_updated(): void
    {
        $product = $this->makeProduct();

        $createRes = $this->postJson("/api/products/{$product->id}/price-tiers", [
            'category' => 'VIP', 'price' => 10,
        ], $this->auth());
        $tierId = $createRes->json('id');

        $res = $this->putJson("/api/products/{$product->id}/price-tiers/{$tierId}", [
            'price' => 8,
        ], $this->auth());

        $res->assertStatus(200)->assertJsonPath('price', 8);
        $this->assertDatabaseHas('product_price_tiers', ['id' => $tierId, 'price' => 8]);
    }

    #[Test]
    public function price_tier_can_be_deleted(): void
    {
        $product = $this->makeProduct();

        $createRes = $this->postJson("/api/products/{$product->id}/price-tiers", [
            'category' => 'Retail', 'price' => 18,
        ], $this->auth());
        $tierId = $createRes->json('id');

        $res = $this->deleteJson("/api/products/{$product->id}/price-tiers/{$tierId}", [], $this->auth());
        $res->assertStatus(200)->assertJsonPath('success', true);
        $this->assertDatabaseMissing('product_price_tiers', ['id' => $tierId]);
    }
}
