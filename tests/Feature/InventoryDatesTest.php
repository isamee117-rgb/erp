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

    #[Test]
    public function expiry_report_classifies_batches(): void
    {
        $this->enableInventoryDates(30);
        $this->receiveWithDates(['batchNo' => 'EXP',  'expiryDate' => now()->subDays(5)->toDateString()]);
        $this->receiveWithDates(['batchNo' => 'SOON', 'expiryDate' => now()->addDays(10)->toDateString()]);
        $this->receiveWithDates(['batchNo' => 'OK',   'expiryDate' => now()->addDays(90)->toDateString()]);

        $resp = $this->getJson('/api/reports/expiry', $this->auth());
        $resp->assertOk()
             ->assertJsonPath('summary.expired', 1)
             ->assertJsonPath('summary.expiringSoon', 1)
             ->assertJsonPath('summary.ok', 1)
             ->assertJsonPath('summary.alertDays', 30);

        $rows = collect($resp->json('data'))->keyBy('batchNo');
        $this->assertEquals('expired',       $rows['EXP']['status']);
        $this->assertEquals('expiring_soon', $rows['SOON']['status']);
        $this->assertEquals('ok',            $rows['OK']['status']);
    }

    #[Test]
    public function expiry_report_status_filter_limits_rows(): void
    {
        $this->enableInventoryDates(30);
        $this->receiveWithDates(['batchNo' => 'EXP', 'expiryDate' => now()->subDays(5)->toDateString()]);
        $this->receiveWithDates(['batchNo' => 'OK',  'expiryDate' => now()->addDays(90)->toDateString()]);

        $this->getJson('/api/reports/expiry?status=expired', $this->auth())
             ->assertOk()
             ->assertJsonCount(1, 'data')
             ->assertJsonPath('data.0.batchNo', 'EXP');
    }

    #[Test]
    public function expiry_report_is_company_scoped(): void
    {
        $this->receiveWithDates(['batchNo' => 'MINE', 'expiryDate' => now()->addDays(10)->toDateString()]);

        $otherCo    = $this->createCompany(['name' => 'Other Co']);
        $otherAdmin = $this->createAdminUser($otherCo, ['username' => 'otheradmin']);
        $otherToken = $this->loginAndGetToken($otherAdmin);

        $this->getJson('/api/reports/expiry', $this->auth($otherToken))
             ->assertOk()
             ->assertJsonCount(0, 'data');
    }

    #[Test]
    public function expiry_summary_returns_counts_with_default_threshold(): void
    {
        // No settings saved — alertDays must default to 30
        $this->receiveWithDates(['expiryDate' => now()->subDay()->toDateString()]);

        $this->getJson('/api/reports/expiry-summary', $this->auth())
             ->assertOk()
             ->assertJsonPath('expired', 1)
             ->assertJsonPath('expiringSoon', 0)
             ->assertJsonPath('alertDays', 30);
    }

    #[Test]
    public function expiry_report_rejects_invalid_status(): void
    {
        $this->getJson('/api/reports/expiry?status=bogus', $this->auth())
             ->assertStatus(422);
    }
}
