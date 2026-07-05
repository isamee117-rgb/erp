<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\DocumentSequenceService;
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
}
