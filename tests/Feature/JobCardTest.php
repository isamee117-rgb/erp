<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryCostLayer;
use App\Models\JobCard;
use App\Models\Product;
use App\Services\DocumentSequenceService;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;

class JobCardTest extends ApiTestCase
{
    private Product $product;
    private Product $serviceProduct;
    private array $customer;

    protected function setUp(): void
    {
        parent::setUp();
        app(DocumentSequenceService::class)->ensureSequencesExist($this->company->id);

        // Create a category (needed because category_id is NOT NULL on products)
        $category = Category::create([
            'id'         => 'CAT-' . Str::random(9),
            'company_id' => $this->company->id,
            'name'       => 'Test Category',
        ]);

        // Create products directly to avoid the product API's category_id NOT NULL issue
        $this->product = Product::create([
            'id'            => 'PRD-' . Str::random(9),
            'company_id'    => $this->company->id,
            'sku'           => 'SKU-OIL-FILTER',
            'item_number'   => 'ITM-00001',
            'name'          => 'Oil Filter',
            'type'          => 'Product',
            'uom'           => '',
            'category_id'   => $category->id,
            'current_stock' => 50,
            'reorder_level' => 5,
            'unit_cost'     => 300.00,
            'unit_price'    => 500.00,
        ]);

        // Add a cost layer so COGS calculation works on finalize
        InventoryCostLayer::create([
            'id'                 => 'ICL-' . Str::random(9),
            'company_id'         => $this->company->id,
            'product_id'         => $this->product->id,
            'quantity'           => 50,
            'unit_cost'          => 300.00,
            'remaining_quantity' => 50,
            'reference_id'       => 'OPENING',
            'reference_type'     => 'Adjustment',
        ]);

        $this->serviceProduct = Product::create([
            'id'            => 'PRD-' . Str::random(9),
            'company_id'    => $this->company->id,
            'sku'           => 'SKU-OIL-SERVICE',
            'item_number'   => 'ITM-00002',
            'name'          => 'Oil Change Service',
            'type'          => 'Service',
            'uom'           => '',
            'category_id'   => $category->id,
            'current_stock' => 0,
            'reorder_level' => 0,
            'unit_cost'     => 0,
            'unit_price'    => 800.00,
        ]);

        $this->customer = $this->createParty('Customer', [
            'name'  => 'Ahmed Ali',
            'phone' => '03001234567',
        ]);
    }

    #[Test]
    public function can_create_a_job_card(): void
    {
        $response = $this->postJson('/api/job-cards', [
            'customerName'     => 'Ahmed Ali',
            'vehicleRegNumber' => 'ABC-123',
        ], $this->auth());

        $response->assertStatus(201)
                 ->assertJsonStructure(['id', 'jobCardNo', 'status', 'vehicleRegNumber']);

        $this->assertDatabaseHas('job_cards', [
            'company_id'         => $this->company->id,
            'status'             => 'open',
            'vehicle_reg_number' => 'ABC-123',
        ]);
    }

    #[Test]
    public function can_update_job_card_header(): void
    {
        $card = $this->createJobCard();

        $response = $this->putJson("/api/job-cards/{$card['id']}", [
            'liftNumber'    => 'Lift-3',
            'makeModelYear' => 'Toyota Corolla 2020',
        ], $this->auth());

        $response->assertStatus(200);
        $this->assertDatabaseHas('job_cards', [
            'id'              => $card['id'],
            'lift_number'     => 'Lift-3',
            'make_model_year' => 'Toyota Corolla 2020',
        ]);
    }

    #[Test]
    public function can_add_part_to_job_card(): void
    {
        $card = $this->createJobCard();

        $response = $this->postJson("/api/job-cards/{$card['id']}/items", [
            'itemType'  => 'part',
            'productId' => $this->product->id,
            'quantity'  => 2,
            'unitPrice' => 500.00,
            'discount'  => 0,
        ], $this->auth());

        $response->assertStatus(201)
                 ->assertJson(['itemType' => 'part', 'quantity' => 2]);

        $this->assertDatabaseHas('job_card_items', [
            'job_card_id' => $card['id'],
            'item_type'   => 'part',
            'product_id'  => $this->product->id,
            'quantity'    => 2,
        ]);
    }

    #[Test]
    public function can_add_service_to_job_card(): void
    {
        $card = $this->createJobCard();

        $response = $this->postJson("/api/job-cards/{$card['id']}/items", [
            'itemType'  => 'service',
            'productId' => $this->serviceProduct->id,
            'quantity'  => 1,
            'unitPrice' => 800.00,
            'discount'  => 0,
        ], $this->auth());

        $response->assertStatus(201)
                 ->assertJson(['itemType' => 'service']);
    }

    #[Test]
    public function cannot_add_service_product_as_part(): void
    {
        $card = $this->createJobCard();

        $response = $this->postJson("/api/job-cards/{$card['id']}/items", [
            'itemType'  => 'part',
            'productId' => $this->serviceProduct->id,
            'quantity'  => 1,
            'unitPrice' => 800.00,
        ], $this->auth());

        $response->assertStatus(422);
    }

    #[Test]
    public function totals_recalculate_after_adding_items(): void
    {
        $card = $this->createJobCard();

        $this->postJson("/api/job-cards/{$card['id']}/items", [
            'itemType'  => 'part',
            'productId' => $this->product->id,
            'quantity'  => 2,
            'unitPrice' => 500.00,
            'discount'  => 0,
        ], $this->auth());

        $this->postJson("/api/job-cards/{$card['id']}/items", [
            'itemType'  => 'service',
            'productId' => $this->serviceProduct->id,
            'quantity'  => 1,
            'unitPrice' => 800.00,
            'discount'  => 0,
        ], $this->auth());

        $this->assertDatabaseHas('job_cards', [
            'id'                => $card['id'],
            'parts_subtotal'    => 1000.00,
            'services_subtotal' => 800.00,
            'grand_total'       => 1800.00,
        ]);
    }

    #[Test]
    public function finalize_deducts_part_stock(): void
    {
        $stockBefore = $this->product->current_stock;
        $card = $this->createJobCard();

        $this->postJson("/api/job-cards/{$card['id']}/items", [
            'itemType'  => 'part',
            'productId' => $this->product->id,
            'quantity'  => 3,
            'unitPrice' => 500.00,
        ], $this->auth());

        $this->postJson("/api/job-cards/{$card['id']}/finalize", [], $this->auth())
             ->assertStatus(200)
             ->assertJson(['status' => 'closed']);

        $this->assertEquals($stockBefore - 3, Product::find($this->product->id)->current_stock);
        $this->assertDatabaseHas('job_cards', ['id' => $card['id'], 'status' => 'closed']);
    }

    #[Test]
    public function finalize_updates_customer_odometer(): void
    {
        $card = $this->createJobCard(['customerId' => $this->customer['id']]);

        $this->putJson("/api/job-cards/{$card['id']}", [
            'currentOdometer' => 85000,
        ], $this->auth());

        $this->postJson("/api/job-cards/{$card['id']}/items", [
            'itemType'  => 'service',
            'productId' => $this->serviceProduct->id,
            'quantity'  => 1,
            'unitPrice' => 800.00,
        ], $this->auth());

        $this->postJson("/api/job-cards/{$card['id']}/finalize", [], $this->auth());

        $this->assertDatabaseHas('parties', [
            'id'                    => $this->customer['id'],
            'last_odometer_reading' => 85000,
        ]);
    }

    #[Test]
    public function cannot_finalize_empty_job_card(): void
    {
        $card = $this->createJobCard();

        $this->postJson("/api/job-cards/{$card['id']}/finalize", [], $this->auth())
             ->assertStatus(422);
    }

    #[Test]
    public function can_discard_open_job_card(): void
    {
        $card = $this->createJobCard();

        $this->deleteJson("/api/job-cards/{$card['id']}", [], $this->auth())
             ->assertStatus(200);

        $this->assertDatabaseMissing('job_cards', ['id' => $card['id']]);
    }

    #[Test]
    public function closed_cards_appear_in_history(): void
    {
        $card = $this->createJobCard();

        $this->postJson("/api/job-cards/{$card['id']}/items", [
            'itemType'  => 'service',
            'productId' => $this->serviceProduct->id,
            'quantity'  => 1,
            'unitPrice' => 800.00,
        ], $this->auth());

        $this->postJson("/api/job-cards/{$card['id']}/finalize", [], $this->auth());

        $this->getJson('/api/job-cards/history', $this->auth())
             ->assertStatus(200)
             ->assertJsonFragment(['id' => $card['id']]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function createJobCard(array $overrides = []): array
    {
        $response = $this->postJson('/api/job-cards', array_merge([
            'customerName'     => 'Walk-in Customer',
            'vehicleRegNumber' => 'TEST-001',
        ], $overrides), $this->auth());

        return $response->json();
    }
}
