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
}
