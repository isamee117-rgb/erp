<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductUomConversion;
use App\Models\UnitOfMeasure;
use App\Services\DocumentSequenceService;
use Illuminate\Support\Str;

class UomConversionTest extends ApiTestCase
{
    private array $product;
    private UnitOfMeasure $uomPiece;
    private UnitOfMeasure $uomBox;
    private array $vendor;

    protected function setUp(): void
    {
        parent::setUp();

        app(DocumentSequenceService::class)->ensureSequencesExist($this->company->id);

        // category_id is NOT NULL with FK — create one directly so createProduct() works
        $category = Category::create([
            'id'         => 'CAT-' . Str::random(9),
            'company_id' => $this->company->id,
            'name'       => 'Test Category',
        ]);

        $this->uomPiece = UnitOfMeasure::create([
            'id'         => 'UOM-' . Str::random(9),
            'company_id' => $this->company->id,
            'name'       => 'Piece',
        ]);

        $this->uomBox = UnitOfMeasure::create([
            'id'         => 'UOM-' . Str::random(9),
            'company_id' => $this->company->id,
            'name'       => 'Box',
        ]);

        $this->product = $this->createProduct([
            'name'         => 'Soap Bar',
            'categoryId'   => $category->id,
            'unitCost'     => 50.00,
            'unitPrice'    => 80.00,
            'initialStock' => 120,
        ]);

        // phone:null fails 'sometimes|string' — send a real string
        $this->vendor = $this->createParty('Vendor', ['name' => 'Soap Factory', 'phone' => '0300-0000000']);
    }

    // ── CRUD ─────────────────────────────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function can_add_a_uom_conversion(): void
    {
        $response = $this->postJson(
            '/api/products/' . $this->product['id'] . '/uom-conversions',
            ['uomId' => $this->uomBox->id, 'multiplier' => 12],
            $this->auth()
        );

        $response->assertStatus(201)
                 ->assertJsonStructure(['id', 'productId', 'uomId', 'uomName', 'multiplier',
                                        'isDefaultPurchaseUnit', 'isDefaultSalesUnit'])
                 ->assertJsonFragment(['uomName' => 'Box', 'multiplier' => 12.0]);

        $this->assertDatabaseHas('product_uom_conversions', [
            'product_id' => $this->product['id'],
            'uom_id'     => $this->uomBox->id,
            'multiplier' => 12,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function can_list_uom_conversions_for_a_product(): void
    {
        $this->postJson(
            '/api/products/' . $this->product['id'] . '/uom-conversions',
            ['uomId' => $this->uomBox->id, 'multiplier' => 12],
            $this->auth()
        );

        $response = $this->getJson(
            '/api/products/' . $this->product['id'] . '/uom-conversions',
            $this->auth()
        );

        $response->assertOk()
                 ->assertJsonCount(1)
                 ->assertJsonFragment(['uomName' => 'Box']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function can_update_a_uom_conversion_multiplier(): void
    {
        $createResp = $this->postJson(
            '/api/products/' . $this->product['id'] . '/uom-conversions',
            ['uomId' => $this->uomBox->id, 'multiplier' => 10],
            $this->auth()
        );
        $cid = $createResp->json('id');

        $updateResp = $this->putJson(
            '/api/products/' . $this->product['id'] . '/uom-conversions/' . $cid,
            ['multiplier' => 12],
            $this->auth()
        );

        $updateResp->assertOk()
                   ->assertJsonFragment(['multiplier' => 12.0]);

        $this->assertDatabaseHas('product_uom_conversions', [
            'id'         => $cid,
            'multiplier' => 12,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function can_delete_a_uom_conversion(): void
    {
        $createResp = $this->postJson(
            '/api/products/' . $this->product['id'] . '/uom-conversions',
            ['uomId' => $this->uomBox->id, 'multiplier' => 12],
            $this->auth()
        );
        $cid = $createResp->json('id');

        $this->deleteJson(
            '/api/products/' . $this->product['id'] . '/uom-conversions/' . $cid,
            [],
            $this->auth()
        )->assertOk();

        $this->assertDatabaseMissing('product_uom_conversions', ['id' => $cid]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function duplicate_uom_conversion_is_rejected(): void
    {
        $this->postJson(
            '/api/products/' . $this->product['id'] . '/uom-conversions',
            ['uomId' => $this->uomBox->id, 'multiplier' => 12],
            $this->auth()
        );

        $response = $this->postJson(
            '/api/products/' . $this->product['id'] . '/uom-conversions',
            ['uomId' => $this->uomBox->id, 'multiplier' => 6],
            $this->auth()
        );

        $response->assertStatus(422)
                 ->assertJsonFragment(['error' => 'A conversion for this UOM already exists on this product']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function zero_or_negative_multiplier_is_rejected(): void
    {
        $response = $this->postJson(
            '/api/products/' . $this->product['id'] . '/uom-conversions',
            ['uomId' => $this->uomBox->id, 'multiplier' => 0],
            $this->auth()
        );

        $response->assertStatus(422);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function missing_uom_id_is_rejected(): void
    {
        $response = $this->postJson(
            '/api/products/' . $this->product['id'] . '/uom-conversions',
            ['multiplier' => 12],
            $this->auth()
        );

        $response->assertStatus(422);
    }

    // ── Default flags ─────────────────────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function can_set_default_purchase_unit(): void
    {
        $r1  = $this->postJson(
            '/api/products/' . $this->product['id'] . '/uom-conversions',
            ['uomId' => $this->uomBox->id, 'multiplier' => 12],
            $this->auth()
        );
        $r2  = $this->postJson(
            '/api/products/' . $this->product['id'] . '/uom-conversions',
            ['uomId' => $this->uomPiece->id, 'multiplier' => 1],
            $this->auth()
        );
        $cid1 = $r1->json('id');
        $cid2 = $r2->json('id');

        $this->putJson(
            '/api/products/' . $this->product['id'] . '/uom-conversions/' . $cid1,
            ['isDefaultPurchaseUnit' => true],
            $this->auth()
        )->assertOk();

        $this->assertDatabaseHas('product_uom_conversions',
            ['id' => $cid1, 'is_default_purchase_unit' => 1]);

        // Switching default to cid2 must clear cid1
        $this->putJson(
            '/api/products/' . $this->product['id'] . '/uom-conversions/' . $cid2,
            ['isDefaultPurchaseUnit' => true],
            $this->auth()
        )->assertOk();

        $this->assertDatabaseHas('product_uom_conversions',
            ['id' => $cid1, 'is_default_purchase_unit' => 0]);
        $this->assertDatabaseHas('product_uom_conversions',
            ['id' => $cid2, 'is_default_purchase_unit' => 1]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function can_set_default_sales_unit(): void
    {
        $r1  = $this->postJson(
            '/api/products/' . $this->product['id'] . '/uom-conversions',
            ['uomId' => $this->uomBox->id, 'multiplier' => 12],
            $this->auth()
        );
        $r2  = $this->postJson(
            '/api/products/' . $this->product['id'] . '/uom-conversions',
            ['uomId' => $this->uomPiece->id, 'multiplier' => 1],
            $this->auth()
        );
        $cid1 = $r1->json('id');
        $cid2 = $r2->json('id');

        $this->putJson(
            '/api/products/' . $this->product['id'] . '/uom-conversions/' . $cid1,
            ['isDefaultSalesUnit' => true],
            $this->auth()
        )->assertOk();

        // Switching default to cid2 must clear cid1
        $this->putJson(
            '/api/products/' . $this->product['id'] . '/uom-conversions/' . $cid2,
            ['isDefaultSalesUnit' => true],
            $this->auth()
        )->assertOk();

        $this->assertDatabaseHas('product_uom_conversions',
            ['id' => $cid1, 'is_default_sales_unit' => 0]);
        $this->assertDatabaseHas('product_uom_conversions',
            ['id' => $cid2, 'is_default_sales_unit' => 1]);
    }

    // ── Sales with UOM ────────────────────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function sale_with_uom_deducts_base_units_from_stock(): void
    {
        // Box = 12 Pieces
        $this->postJson(
            '/api/products/' . $this->product['id'] . '/uom-conversions',
            ['uomId' => $this->uomBox->id, 'multiplier' => 12],
            $this->auth()
        );

        $stockBefore = Product::find($this->product['id'])->current_stock;
        $customer    = $this->createParty('Customer', ['name' => 'Retail Shop', 'phone' => '0300-0000000']);

        // Sell 3 Boxes → 36 base units should be deducted
        $this->postJson('/api/sales', [
            'customerId'    => $customer['id'],
            'paymentMethod' => 'Cash',
            'items'         => [[
                'productId' => $this->product['id'],
                'quantity'  => 3,
                'uomId'     => $this->uomBox->id,
                'discount'  => 0,
            ]],
        ], $this->auth())->assertStatus(201);

        $stockAfter = Product::find($this->product['id'])->current_stock;
        $this->assertEquals($stockBefore - 36, $stockAfter);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function sale_with_uom_records_base_qty_in_ledger(): void
    {
        $this->postJson(
            '/api/products/' . $this->product['id'] . '/uom-conversions',
            ['uomId' => $this->uomBox->id, 'multiplier' => 12],
            $this->auth()
        );

        $customer = $this->createParty('Customer', ['name' => 'Retail Shop', 'phone' => '0300-0000000']);

        // Sell 2 Boxes → ledger should record -24
        $this->postJson('/api/sales', [
            'customerId'    => $customer['id'],
            'paymentMethod' => 'Cash',
            'items'         => [[
                'productId' => $this->product['id'],
                'quantity'  => 2,
                'uomId'     => $this->uomBox->id,
                'discount'  => 0,
            ]],
        ], $this->auth())->assertStatus(201);

        $this->assertDatabaseHas('inventory_ledger', [
            'product_id'       => $this->product['id'],
            'transaction_type' => 'Sale',
            'quantity_change'  => -24,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function sale_with_uom_prices_at_uom_level(): void
    {
        // Box = 12 Pieces; base unit_price = 80; price per box = 80 × 12 = 960
        $this->postJson(
            '/api/products/' . $this->product['id'] . '/uom-conversions',
            ['uomId' => $this->uomBox->id, 'multiplier' => 12],
            $this->auth()
        );

        $customer = $this->createParty('Customer', ['name' => 'Retail Shop', 'phone' => '0300-0000000']);

        $response = $this->postJson('/api/sales', [
            'customerId'    => $customer['id'],
            'paymentMethod' => 'Cash',
            'items'         => [[
                'productId' => $this->product['id'],
                'quantity'  => 1,
                'uomId'     => $this->uomBox->id,
                'discount'  => 0,
            ]],
        ], $this->auth())->assertStatus(201);

        // 1 Box × (80 × 12) = 960
        $this->assertEquals(960.0, (float) $response->json('totalAmount'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function sale_with_no_uom_id_uses_base_unit(): void
    {
        $stockBefore = Product::find($this->product['id'])->current_stock;
        $customer    = $this->createParty('Customer', ['name' => 'Walk-in', 'phone' => '0300-0000000']);

        // No uomId → multiplier = 1 → quantity equals base quantity
        $this->postJson('/api/sales', [
            'customerId'    => $customer['id'],
            'paymentMethod' => 'Cash',
            'items'         => [[
                'productId' => $this->product['id'],
                'quantity'  => 5,
                'discount'  => 0,
            ]],
        ], $this->auth())->assertStatus(201);

        $stockAfter = Product::find($this->product['id'])->current_stock;
        $this->assertEquals($stockBefore - 5, $stockAfter);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function sale_return_with_uom_restores_base_units(): void
    {
        $this->postJson(
            '/api/products/' . $this->product['id'] . '/uom-conversions',
            ['uomId' => $this->uomBox->id, 'multiplier' => 12],
            $this->auth()
        );

        $customer = $this->createParty('Customer', ['name' => 'Retail Shop', 'phone' => '0300-0000000']);

        // Sell 5 Boxes (60 base units)
        $saleResp = $this->postJson('/api/sales', [
            'customerId'    => $customer['id'],
            'paymentMethod' => 'Cash',
            'items'         => [[
                'productId' => $this->product['id'],
                'quantity'  => 5,
                'uomId'     => $this->uomBox->id,
                'discount'  => 0,
            ]],
        ], $this->auth());

        $invoiceNo      = $saleResp->json('invoiceNo');
        $stockAfterSale = Product::find($this->product['id'])->current_stock;

        // Return 2 Boxes → 24 base units restored
        $this->postJson('/api/sales/return', [
            'saleId' => $invoiceNo,
            'reason' => 'Damaged boxes',
            'items'  => [[
                'productId' => $this->product['id'],
                'quantity'  => 2,
            ]],
        ], $this->auth())->assertStatus(201);

        $stockAfterReturn = Product::find($this->product['id'])->current_stock;
        $this->assertEquals($stockAfterSale + 24, $stockAfterReturn);
    }

    // ── Purchases with UOM ────────────────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function purchase_order_stores_uom_on_item(): void
    {
        $this->postJson(
            '/api/products/' . $this->product['id'] . '/uom-conversions',
            ['uomId' => $this->uomBox->id, 'multiplier' => 12],
            $this->auth()
        );

        $this->postJson('/api/purchases', [
            'vendorId' => $this->vendor['id'],
            'items'    => [[
                'productId' => $this->product['id'],
                'quantity'  => 10,
                'unitCost'  => 600.00,
                'uomId'     => $this->uomBox->id,
            ]],
        ], $this->auth())->assertStatus(201);

        $this->assertDatabaseHas('purchase_items', [
            'product_id'     => $this->product['id'],
            'uom_id'         => $this->uomBox->id,
            'uom_multiplier' => 12,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function receiving_po_with_uom_increases_stock_by_base_qty(): void
    {
        // Box = 12 Pieces
        $this->postJson(
            '/api/products/' . $this->product['id'] . '/uom-conversions',
            ['uomId' => $this->uomBox->id, 'multiplier' => 12],
            $this->auth()
        );

        $poResp = $this->postJson('/api/purchases', [
            'vendorId' => $this->vendor['id'],
            'items'    => [[
                'productId' => $this->product['id'],
                'quantity'  => 5,
                'unitCost'  => 600.00,
                'uomId'     => $this->uomBox->id,
            ]],
        ], $this->auth());

        $poId        = $poResp->json('id');
        $stockBefore = Product::find($this->product['id'])->current_stock;

        // Receive 5 Boxes → 60 base units added
        $this->putJson('/api/purchases/' . $poId . '/receive', [
            'items' => [[
                'productId' => $this->product['id'],
                'quantity'  => 5,
                'unitCost'  => 600.00,
            ]],
        ], $this->auth())->assertOk();

        $stockAfter = Product::find($this->product['id'])->current_stock;
        $this->assertEquals($stockBefore + 60, $stockAfter);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function receiving_po_with_uom_records_base_qty_in_ledger(): void
    {
        $this->postJson(
            '/api/products/' . $this->product['id'] . '/uom-conversions',
            ['uomId' => $this->uomBox->id, 'multiplier' => 12],
            $this->auth()
        );

        $poResp = $this->postJson('/api/purchases', [
            'vendorId' => $this->vendor['id'],
            'items'    => [[
                'productId' => $this->product['id'],
                'quantity'  => 3,
                'unitCost'  => 600.00,
                'uomId'     => $this->uomBox->id,
            ]],
        ], $this->auth());

        $poId = $poResp->json('id');

        $this->putJson('/api/purchases/' . $poId . '/receive', [
            'items' => [[
                'productId' => $this->product['id'],
                'quantity'  => 3,
                'unitCost'  => 600.00,
            ]],
        ], $this->auth())->assertOk();

        // 3 Boxes × 12 = 36 base units
        $this->assertDatabaseHas('inventory_ledger', [
            'product_id'       => $this->product['id'],
            'transaction_type' => 'Purchase_Receive',
            'quantity_change'  => 36,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function purchase_return_with_uom_deducts_base_units_from_stock(): void
    {
        $this->postJson(
            '/api/products/' . $this->product['id'] . '/uom-conversions',
            ['uomId' => $this->uomBox->id, 'multiplier' => 12],
            $this->auth()
        );

        $poResp = $this->postJson('/api/purchases', [
            'vendorId' => $this->vendor['id'],
            'items'    => [[
                'productId' => $this->product['id'],
                'quantity'  => 10,
                'unitCost'  => 600.00,
                'uomId'     => $this->uomBox->id,
            ]],
        ], $this->auth());

        $poId = $poResp->json('id');
        $poNo = $poResp->json('poNo');

        $this->putJson('/api/purchases/' . $poId . '/receive', [], $this->auth());

        $stockAfterReceive = Product::find($this->product['id'])->current_stock;

        // Return 2 Boxes → 24 base units deducted
        $this->postJson('/api/purchases/return', [
            'poId'   => $poNo,
            'reason' => 'Defective boxes',
            'items'  => [[
                'productId' => $this->product['id'],
                'quantity'  => 2,
            ]],
        ], $this->auth())->assertStatus(201);

        $stockAfterReturn = Product::find($this->product['id'])->current_stock;
        $this->assertEquals($stockAfterReceive - 24, $stockAfterReturn);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function receiving_po_with_uom_computes_cost_per_base_unit(): void
    {
        // Box = 12 Pieces; cost per box = 600; cost per piece = 50
        $this->postJson(
            '/api/products/' . $this->product['id'] . '/uom-conversions',
            ['uomId' => $this->uomBox->id, 'multiplier' => 12],
            $this->auth()
        );

        $poResp = $this->postJson('/api/purchases', [
            'vendorId' => $this->vendor['id'],
            'items'    => [[
                'productId' => $this->product['id'],
                'quantity'  => 1,
                'unitCost'  => 600.00,
                'uomId'     => $this->uomBox->id,
            ]],
        ], $this->auth());

        $poId = $poResp->json('id');

        $this->putJson('/api/purchases/' . $poId . '/receive', [
            'items' => [[
                'productId' => $this->product['id'],
                'quantity'  => 1,
                'unitCost'  => 600.00,
            ]],
        ], $this->auth())->assertOk();

        // Moving-average cost: 600 / 12 = 50 per base unit
        $product = Product::find($this->product['id']);
        $this->assertEquals(50.0, round((float) $product->unit_cost, 2));
    }

    // ── Sync includes uomConversions ──────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function sync_master_includes_uom_conversions_on_products(): void
    {
        $this->postJson(
            '/api/products/' . $this->product['id'] . '/uom-conversions',
            ['uomId' => $this->uomBox->id, 'multiplier' => 12],
            $this->auth()
        );

        $response = $this->getJson('/api/sync/master', $this->auth());

        $response->assertOk();

        $products      = $response->json('products');
        $syncedProduct = collect($products)->firstWhere('id', $this->product['id']);

        $this->assertNotNull($syncedProduct);
        $this->assertArrayHasKey('uomConversions', $syncedProduct);
        $this->assertCount(1, $syncedProduct['uomConversions']);
        $this->assertEquals('Box', $syncedProduct['uomConversions'][0]['uomName']);
        $this->assertEquals(12.0, $syncedProduct['uomConversions'][0]['multiplier']);
    }
}
