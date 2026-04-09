<?php

namespace Tests\Feature;

use App\Models\Party;
use App\Models\Product;
use App\Services\DocumentSequenceService;

class PurchaseTest extends ApiTestCase
{
    private array $vendor;
    private array $product;

    protected function setUp(): void
    {
        parent::setUp();

        app(DocumentSequenceService::class)->ensureSequencesExist($this->company->id);

        $this->vendor  = $this->createParty('Vendor', ['name' => 'ABC Suppliers']);
        $this->product = $this->createProduct([
            'name'         => 'Sugar 1kg',
            'unitCost'     => 120.00,
            'unitPrice'    => 180.00,
            'initialStock' => 0,
        ]);
    }

    /** @test */
    public function can_create_a_purchase_order(): void
    {
        $response = $this->postJson('/api/purchases', [
            'vendorId' => $this->vendor['id'],
            'items'    => [
                [
                    'productId' => $this->product['id'],
                    'quantity'  => 50,
                    'unitCost'  => 120.00,
                ],
            ],
        ], $this->auth());

        $response->assertStatus(201)
                 ->assertJsonStructure(['id', 'poNo', 'status', 'items']);

        $this->assertDatabaseHas('purchase_orders', [
            'company_id' => $this->company->id,
            'status'     => 'Draft',
        ]);
    }

    /** @test */
    public function receiving_a_po_increases_stock(): void
    {
        // Create PO
        $poResponse = $this->postJson('/api/purchases', [
            'vendorId' => $this->vendor['id'],
            'items'    => [
                [
                    'productId' => $this->product['id'],
                    'quantity'  => 30,
                    'unitCost'  => 120.00,
                ],
            ],
        ], $this->auth());

        $poId = $poResponse->json('id');
        $stockBefore = Product::find($this->product['id'])->current_stock;

        // Receive the PO
        $response = $this->putJson('/api/purchases/' . $poId . '/receive', [
            'items' => [
                [
                    'productId' => $this->product['id'],
                    'quantity'  => 30,
                    'unitCost'  => 120.00,
                ],
            ],
            'notes' => 'First delivery',
        ], $this->auth());

        $response->assertOk();

        $stockAfter = Product::find($this->product['id'])->current_stock;
        $this->assertEquals($stockBefore + 30, $stockAfter);
    }

    /** @test */
    public function receiving_a_po_creates_inventory_ledger_entry(): void
    {
        $poResponse = $this->postJson('/api/purchases', [
            'vendorId' => $this->vendor['id'],
            'items'    => [
                [
                    'productId' => $this->product['id'],
                    'quantity'  => 20,
                    'unitCost'  => 120.00,
                ],
            ],
        ], $this->auth());

        $poId = $poResponse->json('id');

        $this->putJson('/api/purchases/' . $poId . '/receive', [], $this->auth());

        $this->assertDatabaseHas('inventory_ledger', [
            'product_id'       => $this->product['id'],
            'transaction_type' => 'Purchase',
        ]);
    }

    /** @test */
    public function can_create_a_purchase_return(): void
    {
        // Create + receive PO first
        $poResponse = $this->postJson('/api/purchases', [
            'vendorId' => $this->vendor['id'],
            'items'    => [
                [
                    'productId' => $this->product['id'],
                    'quantity'  => 10,
                    'unitCost'  => 120.00,
                ],
            ],
        ], $this->auth());

        $poId  = $poResponse->json('id');
        $poNo  = $poResponse->json('poNo');

        $this->putJson('/api/purchases/' . $poId . '/receive', [], $this->auth());

        // Return some items
        $returnResponse = $this->postJson('/api/purchases/return', [
            'poId'   => $poNo,
            'reason' => 'Damaged on arrival',
            'items'  => [
                [
                    'productId' => $this->product['id'],
                    'quantity'  => 3,
                ],
            ],
        ], $this->auth());

        $returnResponse->assertStatus(201)
                       ->assertJsonStructure(['id', 'returnNo']);
    }

    /** @test */
    public function purchase_return_reduces_stock(): void
    {
        $poResponse = $this->postJson('/api/purchases', [
            'vendorId' => $this->vendor['id'],
            'items'    => [
                [
                    'productId' => $this->product['id'],
                    'quantity'  => 10,
                    'unitCost'  => 120.00,
                ],
            ],
        ], $this->auth());

        $poId = $poResponse->json('id');
        $poNo = $poResponse->json('poNo');

        $this->putJson('/api/purchases/' . $poId . '/receive', [], $this->auth());

        $stockAfterReceive = Product::find($this->product['id'])->current_stock;

        $this->postJson('/api/purchases/return', [
            'poId'   => $poNo,
            'reason' => 'Wrong items',
            'items'  => [['productId' => $this->product['id'], 'quantity' => 5]],
        ], $this->auth());

        $stockAfterReturn = Product::find($this->product['id'])->current_stock;
        $this->assertEquals($stockAfterReceive - 5, $stockAfterReturn);
    }

    /** @test */
    public function purchase_order_requires_vendor_id(): void
    {
        $response = $this->postJson('/api/purchases', [
            'items' => [
                ['productId' => $this->product['id'], 'quantity' => 5, 'unitCost' => 100],
            ],
        ], $this->auth());

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['vendorId']);
    }
}
