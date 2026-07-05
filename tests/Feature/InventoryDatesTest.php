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
}
