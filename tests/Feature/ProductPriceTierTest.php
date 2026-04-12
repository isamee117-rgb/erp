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
}
