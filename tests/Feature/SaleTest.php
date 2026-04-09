<?php

namespace Tests\Feature;

use App\Models\Party;
use App\Models\Product;
use App\Models\SaleOrder;
use App\Services\DocumentSequenceService;
use Illuminate\Support\Str;

class SaleTest extends ApiTestCase
{
    private array $product;
    private array $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure document sequences exist for this company
        app(DocumentSequenceService::class)->ensureSequencesExist($this->company->id);

        // Create a product via API
        $this->product  = $this->createProduct([
            'name'        => 'Rice 5kg',
            'unitPrice'   => 500.00,
            'unitCost'    => 350.00,
            'initialStock' => 100,
        ]);

        // Create a customer
        $this->customer = $this->createParty('Customer', ['name' => 'Walk-in Customer']);
    }

    /** @test */
    public function can_create_a_cash_sale(): void
    {
        $response = $this->postJson('/api/sales', [
            'customerId'    => $this->customer['id'],
            'paymentMethod' => 'Cash',
            'items'         => [
                [
                    'productId' => $this->product['id'],
                    'quantity'  => 2,
                    'discount'  => 0,
                ],
            ],
        ], $this->auth());

        $response->assertStatus(201)
                 ->assertJsonStructure(['id', 'invoiceNo', 'totalAmount', 'items']);

        $this->assertDatabaseHas('sale_orders', [
            'company_id'     => $this->company->id,
            'payment_method' => 'Cash',
        ]);
    }

    /** @test */
    public function sale_reduces_product_stock(): void
    {
        $productBefore = Product::find($this->product['id']);
        $stockBefore   = $productBefore->current_stock;

        $this->postJson('/api/sales', [
            'customerId'    => null,
            'paymentMethod' => 'Cash',
            'items'         => [
                [
                    'productId' => $this->product['id'],
                    'quantity'  => 3,
                    'discount'  => 0,
                ],
            ],
        ], $this->auth());

        $productAfter = Product::find($this->product['id']);
        $this->assertEquals($stockBefore - 3, $productAfter->current_stock);
    }

    /** @test */
    public function sale_creates_inventory_ledger_entry(): void
    {
        $this->postJson('/api/sales', [
            'customerId'    => null,
            'paymentMethod' => 'Cash',
            'items'         => [
                [
                    'productId' => $this->product['id'],
                    'quantity'  => 1,
                    'discount'  => 0,
                ],
            ],
        ], $this->auth());

        $this->assertDatabaseHas('inventory_ledger', [
            'product_id'       => $this->product['id'],
            'transaction_type' => 'Sale',
            'quantity_change'  => -1,
        ]);
    }

    /** @test */
    public function credit_sale_increases_customer_balance(): void
    {
        $partyBefore = Party::find($this->customer['id']);
        $balanceBefore = (float) $partyBefore->current_balance;

        $this->postJson('/api/sales', [
            'customerId'    => $this->customer['id'],
            'paymentMethod' => 'Credit',
            'items'         => [
                [
                    'productId' => $this->product['id'],
                    'quantity'  => 2,
                    'discount'  => 0,
                ],
            ],
        ], $this->auth());

        $partyAfter = Party::find($this->customer['id']);
        $this->assertGreaterThan($balanceBefore, (float) $partyAfter->current_balance);
    }

    /** @test */
    public function sale_requires_at_least_one_item(): void
    {
        $response = $this->postJson('/api/sales', [
            'customerId'    => null,
            'paymentMethod' => 'Cash',
            'items'         => [],
        ], $this->auth());

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['items']);
    }

    /** @test */
    public function can_create_a_sale_return(): void
    {
        // First create a sale
        $saleResponse = $this->postJson('/api/sales', [
            'customerId'    => $this->customer['id'],
            'paymentMethod' => 'Cash',
            'items'         => [
                [
                    'productId' => $this->product['id'],
                    'quantity'  => 5,
                    'discount'  => 0,
                ],
            ],
        ], $this->auth());

        $invoiceNo = $saleResponse->json('invoiceNo');

        // Return 2 items
        $returnResponse = $this->postJson('/api/sales/return', [
            'saleId' => $invoiceNo,
            'reason' => 'Defective items',
            'items'  => [
                [
                    'productId' => $this->product['id'],
                    'quantity'  => 2,
                ],
            ],
        ], $this->auth());

        $returnResponse->assertStatus(201)
                       ->assertJsonStructure(['id', 'returnNo', 'totalAmount']);
    }

    /** @test */
    public function sale_return_restores_stock(): void
    {
        // Create sale
        $saleResponse = $this->postJson('/api/sales', [
            'customerId'    => null,
            'paymentMethod' => 'Cash',
            'items'         => [
                [
                    'productId' => $this->product['id'],
                    'quantity'  => 4,
                    'discount'  => 0,
                ],
            ],
        ], $this->auth());

        $invoiceNo = $saleResponse->json('invoiceNo');
        $stockAfterSale = Product::find($this->product['id'])->current_stock;

        // Return 2 items
        $this->postJson('/api/sales/return', [
            'saleId' => $invoiceNo,
            'reason' => 'Customer request',
            'items'  => [
                [
                    'productId' => $this->product['id'],
                    'quantity'  => 2,
                ],
            ],
        ], $this->auth());

        $stockAfterReturn = Product::find($this->product['id'])->current_stock;
        $this->assertEquals($stockAfterSale + 2, $stockAfterReturn);
    }

    /** @test */
    public function sale_return_for_nonexistent_sale_returns_404(): void
    {
        $response = $this->postJson('/api/sales/return', [
            'saleId' => 'FAKE-INV-9999',
            'items'  => [['productId' => $this->product['id'], 'quantity' => 1]],
        ], $this->auth());

        $response->assertStatus(404);
    }
}
