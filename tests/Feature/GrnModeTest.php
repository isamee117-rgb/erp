<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\DocumentSequenceService;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;

class GrnModeTest extends ApiTestCase
{
    private array $vendor;
    private array $product;

    protected function setUp(): void
    {
        parent::setUp();
        app(DocumentSequenceService::class)->ensureSequencesExist($this->company->id);
        $this->vendor  = $this->createParty('Vendor');
        $this->product = $this->createProduct();
    }

    private function setGrn(bool $enabled): void
    {
        $this->putJson('/api/settings/grn-mode', ['grnEnabled' => $enabled], $this->auth())->assertOk();
    }

    private function createPO(array $itemOverrides = [], int $qty = 10, float $cost = 50): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/purchases', [
            'vendorId' => $this->vendor['id'],
            'items'    => [array_merge([
                'productId' => $this->product['id'],
                'quantity'  => $qty,
                'unitCost'  => $cost,
            ], $itemOverrides)],
        ], $this->auth());
    }

    #[Test]
    public function grn_mode_setting_is_saved(): void
    {
        $this->setGrn(false);
        $this->assertDatabaseHas('settings', [
            'company_id' => $this->company->id,
            'key'        => 'grn_enabled',
            'value'      => '0',
        ]);
    }

    #[Test]
    public function sync_core_returns_grn_setting(): void
    {
        $this->setGrn(false);
        $this->getJson('/api/sync/core', $this->auth())
            ->assertOk()
            ->assertJson(['grnEnabled' => false]);
    }

    #[Test]
    public function sync_core_defaults_grn_enabled_true(): void
    {
        $this->getJson('/api/sync/core', $this->auth())
            ->assertOk()
            ->assertJson(['grnEnabled' => true]);
    }

    #[Test]
    public function grn_off_receives_po_immediately(): void
    {
        $this->setGrn(false);
        $stockBefore   = Product::find($this->product['id'])->current_stock;
        $vendorBalBefore = \App\Models\Party::find($this->vendor['id'])->current_balance;

        $resp = $this->createPO([], 10, 50);
        $resp->assertOk();
        // Auto-receive goes through receiveOrder(), which refreshes the PO
        // (clearing wasRecentlyCreated => 200 not 201). The journal warning
        // (no AccountMapping configured in tests — pre-existing, out of scope)
        // forces the response to wrap under "data", so verify final status via DB.
        $this->assertDatabaseHas('purchase_orders', [
            'company_id' => $this->company->id,
            'status'     => 'Received',
        ]);

        $this->assertEquals($stockBefore + 10, Product::find($this->product['id'])->current_stock);
        $this->assertEquals($vendorBalBefore + 500, \App\Models\Party::find($this->vendor['id'])->current_balance);

        $this->assertDatabaseHas('inventory_ledger', [
            'company_id'       => $this->company->id,
            'product_id'       => $this->product['id'],
            'transaction_type' => 'Purchase_Receive',
            'quantity_change'  => 10,
        ]);
        $this->assertDatabaseHas('inventory_cost_layers', [
            'company_id' => $this->company->id,
            'product_id' => $this->product['id'],
        ]);
    }

    #[Test]
    public function grn_on_leaves_po_draft_with_no_stock_change(): void
    {
        // Default is GRN ON — do not change the setting.
        $stockBefore = Product::find($this->product['id'])->current_stock;

        // A freshly-created Draft PO is never touched by receiveOrder()/refresh(),
        // so Laravel's ResourceResponse reports 201 (wasRecentlyCreated), matching
        // the existing "can create a purchase order" convention in PurchaseTest.
        $this->createPO([], 10, 50)->assertStatus(201)->assertJsonPath('status', 'Draft');

        $this->assertEquals($stockBefore, Product::find($this->product['id'])->current_stock);
        $this->assertDatabaseMissing('inventory_ledger', [
            'company_id'       => $this->company->id,
            'product_id'       => $this->product['id'],
            'transaction_type' => 'Purchase_Receive',
        ]);
    }

    #[Test]
    public function grn_off_stores_batch_and_dates(): void
    {
        $this->setGrn(false);
        $this->createPO([
            'batchNo'    => 'LOT-9',
            'mfgDate'    => '2026-01-01',
            'expiryDate' => '2027-01-01',
        ], 5, 20)->assertOk();

        $this->assertDatabaseHas('purchase_receive_items', [
            'product_id'  => $this->product['id'],
            'batch_no'    => 'LOT-9',
            'mfg_date'    => '2026-01-01',
            'expiry_date' => '2027-01-01',
        ]);
    }

    #[Test]
    public function po_create_rejects_expiry_before_mfg(): void
    {
        $this->setGrn(false);
        $this->createPO([
            'mfgDate'    => '2026-06-01',
            'expiryDate' => '2026-05-01',
        ], 5, 20)->assertStatus(422);
    }

    #[Test]
    public function grn_setting_is_company_scoped(): void
    {
        $this->setGrn(false); // this company: GRN OFF

        $otherCo    = $this->createCompany(['name' => 'Other Co']);
        $otherAdmin = $this->createAdminUser($otherCo, ['username' => 'otheradmin']);
        $otherToken = $this->loginAndGetToken($otherAdmin);
        app(DocumentSequenceService::class)->ensureSequencesExist($otherCo->id);

        // createParty()/createProduct() always scope company_id from the auth
        // token's user (see PartyController/ProductController::store), so a
        // company_id override in the request payload has no effect. Create
        // the vendor/product for the other company directly via the model,
        // matching the factory pattern used by createCompany()/createAdminUser().
        $vendorB = \App\Models\Party::create([
            'id'         => 'PT-' . Str::random(9),
            'company_id' => $otherCo->id,
            'code'       => 'V-OTHER',
            'type'       => 'Vendor',
            'name'       => 'Other Vendor',
        ]);
        $productB = Product::create([
            'id'          => 'PRD-' . Str::random(9),
            'company_id'  => $otherCo->id,
            'sku'         => 'SKU-OTHER',
            'name'        => 'Other Product',
            'type'        => 'Product',
            'uom'         => 'pcs',
            'category_id' => null,
        ]);

        // Other company never set grn_enabled → default ON → PO stays Draft.
        // (201, not 200 — see note in grn_on_leaves_po_draft_with_no_stock_change.)
        $this->postJson('/api/purchases', [
            'vendorId' => $vendorB->id,
            'items'    => [['productId' => $productB->id, 'quantity' => 3, 'unitCost' => 10]],
        ], $this->auth($otherToken))->assertStatus(201)->assertJsonPath('status', 'Draft');
    }
}
