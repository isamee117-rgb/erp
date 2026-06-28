<?php

namespace Tests\Feature;

use App\Services\DocumentSequenceService;

class ReportDataTest extends ApiTestCase
{
    private array $product;
    private array $customer;

    protected function setUp(): void
    {
        parent::setUp();
        app(DocumentSequenceService::class)->ensureSequencesExist($this->company->id);
        $this->product  = $this->createProduct([
            'name' => 'Rice 5kg', 'unitPrice' => 500.00, 'unitCost' => 350.00, 'initialStock' => 100,
        ]);
        $this->customer = $this->createParty('Customer', ['name' => 'ACME Traders']);
    }

    private function makeSale(int $qty = 2): array
    {
        return $this->postJson('/api/sales', [
            'customerId'    => $this->customer['id'],
            'paymentMethod' => 'Cash',
            'items'         => [['productId' => $this->product['id'], 'quantity' => $qty, 'discount' => 0]],
        ], $this->auth())->json();
    }

    private function range(): string
    {
        $from = now()->startOfDay()->toDateString();
        $to   = now()->endOfDay()->toDateString();
        return "from={$from}&to={$to}";
    }

    private function makePurchaseOrder(): array
    {
        $vendor = $this->createParty('Vendor', ['name' => 'Supplier Co']);
        return $this->postJson('/api/purchases', [
            'vendorId' => $vendor['id'],
            'items'    => [['productId' => $this->product['id'], 'quantity' => 10, 'unitCost' => 300]],
        ], $this->auth())->json();
    }

    /** @test */
    public function detailed_sales_requires_from_and_to(): void
    {
        $this->getJson('/api/reports/detailed-sales', $this->auth())
             ->assertStatus(422);
    }

    /** @test */
    public function detailed_sales_rejects_to_before_from(): void
    {
        $this->getJson('/api/reports/detailed-sales?from=2026-02-01&to=2026-01-01', $this->auth())
             ->assertStatus(422);
    }

    /** @test */
    public function detailed_sales_returns_scoped_paginated_rows_with_summary(): void
    {
        $this->makeSale();
        $this->makeSale();

        $res = $this->getJson('/api/reports/detailed-sales?' . $this->range() . '&page=1&perPage=1', $this->auth());

        $res->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'customerId', 'paymentMethod', 'totalAmount', 'items', 'returns', 'returnStatus', 'createdAt']],
                'pagination' => ['page', 'perPage', 'total', 'lastPage'],
                'summary' => ['totalInvoices', 'grandTotal', 'totalReturns', 'netTotal'],
            ]);

        $this->assertSame(2, $res->json('pagination.total'));
        $this->assertSame(2, $res->json('pagination.lastPage'));
        $this->assertCount(1, $res->json('data'));            // perPage=1
        $this->assertSame(2, $res->json('summary.totalInvoices'));
    }

    /** @test */
    public function detailed_sales_export_returns_all_rows_without_pagination(): void
    {
        $this->makeSale();
        $this->makeSale();

        $res = $this->getJson('/api/reports/detailed-sales?' . $this->range() . '&export=1', $this->auth());

        $res->assertStatus(200);
        $this->assertNull($res->json('pagination'));
        $this->assertCount(2, $res->json('data'));
    }

    /** @test */
    public function detailed_sales_export_rejects_range_over_one_year(): void
    {
        $this->getJson('/api/reports/detailed-sales?from=2024-01-01&to=2026-01-01&export=1', $this->auth())
             ->assertStatus(422);
    }

    /** @test */
    public function detailed_sales_forbidden_for_super_admin(): void
    {
        $super = $this->createSuperAdmin();
        $token = $this->loginAndGetToken($super);

        $this->getJson('/api/reports/detailed-sales?' . $this->range(), $this->auth($token))
             ->assertStatus(403);
    }

    /** @test */
    public function detailed_sales_excludes_other_company_sales(): void
    {
        $this->makeSale();

        $otherCompany = $this->createCompany(['name' => 'Other Co']);
        $otherAdmin   = $this->createAdminUser($otherCompany, ['username' => 'otheradmin']);
        $otherToken   = $this->loginAndGetToken($otherAdmin);

        $res = $this->getJson('/api/reports/detailed-sales?' . $this->range(), $this->auth($otherToken));
        $res->assertStatus(200);
        $this->assertSame(0, $res->json('pagination.total'));
    }

    /** @test */
    public function detailed_purchase_requires_dates_and_returns_rows(): void
    {
        $this->getJson('/api/reports/detailed-purchase', $this->auth())->assertStatus(422);

        $this->makePurchaseOrder();
        $res = $this->getJson('/api/reports/detailed-purchase?' . $this->range(), $this->auth());

        $res->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'vendorId', 'status', 'totalAmount', 'items']],
                'pagination' => ['page', 'perPage', 'total', 'lastPage'],
                'summary' => ['totalOrders', 'grandTotal'],
            ]);
        $this->assertSame(1, $res->json('pagination.total'));
    }

    /** @test */
    public function sales_returns_requires_dates(): void
    {
        $this->getJson('/api/reports/sales-returns', $this->auth())->assertStatus(422);
    }

    /** @test */
    public function sales_returns_returns_envelope(): void
    {
        $res = $this->getJson('/api/reports/sales-returns?' . $this->range(), $this->auth());
        $res->assertStatus(200)
            ->assertJsonStructure(['data', 'pagination' => ['page', 'perPage', 'total', 'lastPage'], 'summary' => ['totalReturns', 'grandTotal']]);
    }

    /** @test */
    public function purchase_returns_requires_dates(): void
    {
        $this->getJson('/api/reports/purchase-returns', $this->auth())->assertStatus(422);
    }

    /** @test */
    public function purchase_returns_returns_envelope(): void
    {
        $res = $this->getJson('/api/reports/purchase-returns?' . $this->range(), $this->auth());
        $res->assertStatus(200)
            ->assertJsonStructure(['data', 'pagination' => ['page', 'perPage', 'total', 'lastPage'], 'summary' => ['totalReturns', 'grandTotal']]);
    }
}
