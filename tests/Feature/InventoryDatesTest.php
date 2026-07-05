<?php

namespace Tests\Feature;

use App\Services\DocumentSequenceService;
use PHPUnit\Framework\Attributes\Test;

class InventoryDatesTest extends ApiTestCase
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

    private function enableInventoryDates(int $alertDays = 30): void
    {
        $this->putJson('/api/settings/inventory-dates', [
            'expiryDateEnabled' => true,
            'mfgDateEnabled'    => true,
            'expiryAlertDays'   => $alertDays,
        ], $this->auth())->assertOk();
    }

    private function receiveWithDates(array $itemOverrides = []): \Illuminate\Testing\TestResponse
    {
        $po = $this->postJson('/api/purchases', [
            'vendorId' => $this->vendor['id'],
            'items'    => [['productId' => $this->product['id'], 'quantity' => 10, 'unitCost' => 50]],
        ], $this->auth());

        return $this->putJson('/api/purchases/' . $po->json('id') . '/receive', [
            'items' => [array_merge([
                'productId' => $this->product['id'],
                'quantity'  => 10,
                'unitCost'  => 50,
            ], $itemOverrides)],
        ], $this->auth());
    }

    #[Test]
    public function inventory_dates_settings_are_saved(): void
    {
        $this->enableInventoryDates(45);
        $this->assertDatabaseHas('settings', ['company_id' => $this->company->id, 'key' => 'expiry_date_enabled', 'value' => '1']);
        $this->assertDatabaseHas('settings', ['company_id' => $this->company->id, 'key' => 'mfg_date_enabled', 'value' => '1']);
        $this->assertDatabaseHas('settings', ['company_id' => $this->company->id, 'key' => 'expiry_alert_days', 'value' => '45']);
    }

    #[Test]
    public function sync_core_returns_inventory_date_settings(): void
    {
        $this->enableInventoryDates(45);
        $this->getJson('/api/sync/core', $this->auth())
            ->assertOk()
            ->assertJson([
                'expiryDateEnabled' => true,
                'mfgDateEnabled'    => true,
                'expiryAlertDays'   => 45,
            ]);
    }

    #[Test]
    public function sync_core_defaults_when_settings_missing(): void
    {
        $this->getJson('/api/sync/core', $this->auth())
            ->assertOk()
            ->assertJson([
                'expiryDateEnabled' => false,
                'mfgDateEnabled'    => false,
                'expiryAlertDays'   => 30,
            ]);
    }

    #[Test]
    public function invalid_alert_days_is_rejected(): void
    {
        $this->putJson('/api/settings/inventory-dates', [
            'expiryDateEnabled' => true,
            'mfgDateEnabled'    => false,
            'expiryAlertDays'   => 0,
        ], $this->auth())->assertStatus(422);
    }

    #[Test]
    public function receive_stores_batch_and_dates(): void
    {
        $this->receiveWithDates([
            'batchNo'    => 'LOT-001',
            'mfgDate'    => '2026-01-01',
            'expiryDate' => '2027-01-01',
        ])->assertOk();

        $this->assertDatabaseHas('purchase_receive_items', [
            'product_id'  => $this->product['id'],
            'batch_no'    => 'LOT-001',
            'mfg_date'    => '2026-01-01',
            'expiry_date' => '2027-01-01',
        ]);
    }

    #[Test]
    public function receive_without_batch_fields_still_works(): void
    {
        $this->receiveWithDates()->assertOk();

        $this->assertDatabaseHas('purchase_receive_items', [
            'product_id' => $this->product['id'],
            'batch_no'   => null,
            'mfg_date'   => null,
        ]);
    }

    #[Test]
    public function expiry_before_mfg_date_is_rejected(): void
    {
        $this->receiveWithDates([
            'mfgDate'    => '2026-06-01',
            'expiryDate' => '2026-05-01',
        ])->assertStatus(422);
    }
}
